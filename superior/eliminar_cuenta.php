<?php

/** Elimina únicamente la cuenta indicada y sus antecedentes. No modifica respaldos SQL. */
function eliminarCuentaCompleta(mysqli $db, int $id, string $raiz): int
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
        if (!$consultar("SELECT id FROM usuarios WHERE id=? FOR UPDATE")) {
            throw new RuntimeException("La cuenta ya no existe.");
        }
        $post = $consultar("SELECT id FROM postulaciones WHERE usuario_id=? FOR UPDATE");
        $rends = $consultar(
            "SELECT r.id,r.devolucion_ruta,r.comprobante_devolucion_ruta FROM rendiciones r LEFT JOIN postulaciones p ON p.id=r.postulacion_id LEFT JOIN accesos_rut a ON a.id=r.acceso_rut_id WHERE COALESCE(p.usuario_id,a.id)=? FOR UPDATE",
        );
        $ids = [
            "usuarios" => [$id],
            "postulaciones" => array_column($post, "id"),
            "rendiciones" => array_column($rends, "id"),
        ];
        $rutas = [];
        foreach (
            ["documentos", "datos_personales", "datos_educacionales", "datos_bancarios"]
            as $tabla
        ) {
            $filas = $consultar(
                "SELECT t.* FROM `$tabla` t JOIN postulaciones p ON p.id=t.postulacion_id WHERE p.usuario_id=?",
            );
            $ids[$tabla] = array_column($filas, "id");
            if ($tabla === "documentos") {
                $rutas = array_merge($rutas, array_column($filas, "ruta_archivo"));
            }
        }
        $gastos = $consultar(
            "SELECT g.id,g.ruta_archivo FROM rendicion_gastos g JOIN rendiciones r ON r.id=g.rendicion_id LEFT JOIN postulaciones p ON p.id=r.postulacion_id LEFT JOIN accesos_rut a ON a.id=r.acceso_rut_id WHERE COALESCE(p.usuario_id,a.id)=?",
        );
        $ids["rendicion_gastos"] = array_column($gastos, "id");
        $rutas = array_merge($rutas, array_column($gastos, "ruta_archivo"));
        foreach ($rends as $r) {
            $rutas[] = $r["devolucion_ruta"];
            $rutas[] = $r["comprobante_devolucion_ruta"];
        }
        $ids["accesos_rut"] = array_column(
            $consultar("SELECT id FROM accesos_rut WHERE id=?"),
            "id",
        );
        $ids["datos_rendicion_usuario"] = array_column(
            $consultar(
                "SELECT d.id FROM datos_rendicion_usuario d JOIN accesos_rut a ON a.id=d.acceso_rut_id WHERE a.id=?",
            ),
            "id",
        );
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
                    "No se pudo validar la ubicación de un documento. No se eliminó la cuenta.",
                );
            }
            $archivos[$fisico] = true;
        }
        // Incluir archivos residuales en las carpetas exclusivas de esta cuenta.
        $propias = [];
        foreach ($post as $p) {
            $propias[] = $raiz . "/documentos/" . (int) $p["id"];
        }
        foreach ($rends as $r) {
            $propias[] = $raiz . "/documentos/rendiciones/" . (int) $r["id"];
        }
        foreach ($propias as $dir) {
            if (!file_exists($dir)) {
                continue;
            }
            $real = realpath($dir);
            if (
                !$real ||
                !$base ||
                is_link($dir) ||
                !str_starts_with(strtolower($real), strtolower($base . DIRECTORY_SEPARATOR))
            ) {
                throw new RuntimeException("Carpeta de documentos no válida.");
            }
            $iterador = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($real, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterador as $f) {
                $ubicacion = $f->getRealPath();
                if (
                    $f->isLink() ||
                    !$ubicacion ||
                    !str_starts_with(
                        strtolower($ubicacion),
                        strtolower($base . DIRECTORY_SEPARATOR),
                    )
                ) {
                    throw new RuntimeException(
                        "No se pudo validar la ubicación de un documento. No se eliminó la cuenta.",
                    );
                }
                if ($f->isDir()) {
                    $carpetas[] = $ubicacion;
                } elseif ($f->isFile()) {
                    $archivos[$ubicacion] = true;
                } else {
                    throw new RuntimeException("No se pudo validar un archivo de la cuenta.");
                }
            }
            $carpetas[] = $real;
        }
        // Conservar cualquier archivo que también esté referenciado por otra cuenta o proceso.
        $restantes = $db->query(
            "SELECT ruta_archivo ruta FROM documentos WHERE postulacion_id NOT IN (" .
                implode(",", array_map("intval", $ids["postulaciones"] ?: [0])) .
                ") UNION SELECT ruta_archivo FROM rendicion_gastos WHERE rendicion_id NOT IN (" .
                implode(",", array_map("intval", $ids["rendiciones"] ?: [0])) .
                ") UNION SELECT devolucion_ruta FROM rendiciones WHERE id NOT IN (" .
                implode(",", array_map("intval", $ids["rendiciones"] ?: [0])) .
                ") UNION SELECT comprobante_devolucion_ruta FROM rendiciones WHERE id NOT IN (" .
                implode(",", array_map("intval", $ids["rendiciones"] ?: [0])) .
                ") UNION SELECT instrucciones_ruta FROM procesos_beca",
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
                    "No se pudo retirar un documento. La cuenta se conserva.",
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
                "datos_rendicion_usuario",
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
        foreach (["postulaciones", "rendiciones"] as $tabla) {
            $s = $db->prepare(
                "UPDATE `$tabla` SET administrador_revision_id=NULL WHERE administrador_revision_id=?",
            );
            $s->bind_param("i", $id);
            $s->execute();
            $s->close();
        }
        $s = $db->prepare("DELETE FROM usuarios WHERE id=?");
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
