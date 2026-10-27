<?php

/** Elimina un proceso y sus antecedentes; conserva las cuentas de sus postulantes.
 * Los documentos se retiran temporalmente para poder restaurarlos si falla la transacción. */
function eliminarProcesoCompleto(mysqli $db, int $id, string $raiz): int
{
    $movidos = [];
    $carpetas = [];
    $consultar = function (string $sql) use ($db, $id): array {
        $s = $db->prepare($sql);
        $s->bind_param("i", $id);
        $s->execute();
        $r = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        $s->close();
        return $r;
    };
    $db->begin_transaction();
    try {
        if (!$consultar("SELECT id FROM procesos_beca WHERE id=? FOR UPDATE")) {
            throw new RuntimeException("El proceso ya no existe.");
        }
        $post = $consultar("SELECT id FROM postulaciones WHERE proceso_id=? FOR UPDATE");
        $rends = $consultar(
            "SELECT r.id,r.devolucion_ruta,r.comprobante_devolucion_ruta FROM rendiciones r WHERE r.proceso_id=? FOR UPDATE",
        );
        $ids = [
            "postulaciones" => array_column($post, "id"),
            "rendiciones" => array_column($rends, "id"),
        ];
        $rutas = array_column(
            $consultar("SELECT instrucciones_ruta FROM procesos_beca WHERE id=?"),
            "instrucciones_ruta",
        );
        foreach (
            ["documentos", "datos_personales", "datos_educacionales", "datos_bancarios"]
            as $tabla
        ) {
            $filas = $consultar(
                "SELECT t.* FROM `$tabla` t JOIN postulaciones p ON p.id=t.postulacion_id WHERE p.proceso_id=?",
            );
            $ids[$tabla] = array_column($filas, "id");
            if ($tabla === "documentos") {
                $rutas = array_merge($rutas, array_column($filas, "ruta_archivo"));
            }
        }
        $gastos = $consultar(
            "SELECT g.id,g.ruta_archivo FROM rendicion_gastos g JOIN rendiciones r ON r.id=g.rendicion_id WHERE r.proceso_id=?",
        );
        $ids["rendicion_gastos"] = array_column($gastos, "id");
        $rutas = array_merge($rutas, array_column($gastos, "ruta_archivo"));
        foreach ($rends as $r) {
            $rutas[] = $r["devolucion_ruta"];
            $rutas[] = $r["comprobante_devolucion_ruta"];
        }
        $base = realpath($raiz . "/documentos");
        $archivos = [];
        foreach (array_unique(array_filter($rutas)) as $ruta) {
            $fisico = realpath($raiz . "/" . $ruta);
            if ($fisico === false) {
                continue;
            }
            if (
                !$base ||
                !str_starts_with(strtolower($fisico), strtolower($base . DIRECTORY_SEPARATOR)) ||
                !is_file($fisico) ||
                is_link($raiz . "/" . $ruta)
            ) {
                throw new RuntimeException(
                    "No se pudo validar la ubicación de un documento. No se eliminó el proceso.",
                );
            }
            $archivos[$fisico] = true;
        }
        // Solo retirar archivos registrados: no recorrer carpetas que puedan contener
        // documentos sin vínculo comprobado con este proceso.
        // Conservar cualquier archivo que también esté referenciado por otro proceso.
        $restantes = $db->query(
            "SELECT ruta_archivo ruta FROM documentos WHERE postulacion_id NOT IN (" .
                implode(",", array_map("intval", $ids["postulaciones"] ?: [0])) .
                ") UNION SELECT ruta_archivo FROM rendicion_gastos WHERE rendicion_id NOT IN (" .
                implode(",", array_map("intval", $ids["rendiciones"] ?: [0])) .
                ") UNION SELECT devolucion_ruta FROM rendiciones WHERE id NOT IN (" .
                implode(",", array_map("intval", $ids["rendiciones"] ?: [0])) .
                ") UNION SELECT comprobante_devolucion_ruta FROM rendiciones WHERE id NOT IN (" .
                implode(",", array_map("intval", $ids["rendiciones"] ?: [0])) .
                ") UNION SELECT instrucciones_ruta FROM procesos_beca WHERE id<>" .
                (int) $id,
        );
        foreach ($restantes as $r) {
            if ($r["ruta"]) {
                $real = realpath($raiz . "/" . $r["ruta"]);
                if ($real) {
                    unset($archivos[$real]);
                }
            }
        }
        foreach (array_keys($archivos) as $original) {
            $temporal = dirname($original) . "/.eliminando-" . bin2hex(random_bytes(16));
            if (!rename($original, $temporal)) {
                throw new RuntimeException(
                    "No se pudo retirar un documento. El proceso se conserva.",
                );
            }
            $movidos[] = [$original, $temporal];
        }
        // Eliminar hijos antes que padres, independientemente de su estado de revisión.
        // Así también funcionan las instalaciones con claves foráneas sin ON DELETE CASCADE.
        foreach (
            [
                "rendicion_gastos",
                "rendiciones",
                "documentos",
                "datos_personales",
                "datos_educacionales",
                "datos_bancarios",
            ]
            as $tabla
        ) {
            if ($ids[$tabla]) {
                $db->query(
                    "DELETE FROM `$tabla` WHERE id IN (" .
                        implode(",", array_map("intval", $ids[$tabla])) .
                        ")",
                );
            }
        }
        if ($ids["postulaciones"]) {
            $lista = implode(",", array_map("intval", $ids["postulaciones"]));
            $db->query(
                "DELETE FROM correos_resultado_postulacion WHERE postulacion_id IN (" .
                    $lista .
                    ")",
            );
            $db->query("DELETE FROM postulaciones WHERE id IN (" . $lista . ")");
        }

        $s = $db->prepare("DELETE FROM requisitos_beca WHERE proceso_id=?");
        $s->bind_param("i", $id);
        $s->execute();
        $s->close();
        $s = $db->prepare("DELETE FROM procesos_beca WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        $s->close();
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        foreach (array_reverse($movidos) as [$original, $temporal]) {
            if (!rename($temporal, $original)) {
                error_log("Error restaurando documento: " . $original);
            }
        }
        throw $e;
    }
    $pendientes = 0;
    foreach ($movidos as [$original, $temporal]) {
        if (!unlink($temporal)) {
            $pendientes++;
            error_log("Archivo pendiente de borrado: " . $temporal);
        }
    }
    foreach ($carpetas as $dir) {
        if (is_dir($dir) && count(scandir($dir)) === 2) {
            @rmdir($dir);
        }
    }
    return $pendientes;
}
