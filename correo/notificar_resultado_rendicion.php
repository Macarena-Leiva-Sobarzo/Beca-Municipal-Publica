<?php

/** Notifica únicamente rendiciones revisadas cuya fecha de publicación ya llegó. El estado de envío diferencia pendiente, reservado y enviado para evitar duplicados. */

function notificarResultadoRendicion(mysqli $conn, int $id, ?callable $transportar = null): string
{
    $reservado = false;
    try {
        $ahora = (new DateTimeImmutable("now", new DateTimeZone("America/Santiago")))->format(
            "Y-m-d H:i:s",
        );
        $stmt = $conn->prepare("SELECT r.*, pb.fecha_resultados_rendicion,
                d.correo_electronico, d.nombres, d.apellido_paterno, d.apellido_materno
            FROM rendiciones r
            LEFT JOIN postulaciones p ON p.id = r.postulacion_id
            INNER JOIN procesos_beca pb ON pb.id = COALESCE(p.proceso_id, r.proceso_id)
            INNER JOIN personas_rendicion d ON d.rendicion_id = r.id
            WHERE r.id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$r) {
            return "pendiente";
        }
        if ((int) $r["correo_resultado_enviado"] === 1) {
            return "ya_enviado";
        }
        if ((int) $r["correo_resultado_enviado"] === 2) {
            return "en_proceso";
        }
        if (
            !in_array($r["estado"], ["aprobada", "rechazada"], true) ||
            empty($r["fecha_resultados_rendicion"]) ||
            $r["fecha_resultados_rendicion"] > $ahora
        ) {
            return "pendiente";
        }

        $destinatario = trim((string) ($r["correo_electronico"] ?? ""));
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("El destinatario no tiene un correo válido.");
        }
        $nombre = trim(
            implode(
                " ",
                array_filter([$r["nombres"], $r["apellido_paterno"], $r["apellido_materno"]]),
            ),
        );
        $stmt = $conn->prepare('SELECT tipo_documento, numero_documento, monto,
            estado_validacion, observacion_validacion FROM rendicion_gastos WHERE rendicion_id = ? ORDER BY id ASC');
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $documentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $devolucion = null;
        if (
            !empty($r["monto_devuelto"]) ||
            !empty($r["devolucion_ruta"]) ||
            !empty($r["numero_comprobante_devolucion"])
        ) {
            $devolucion = [
                "estado" => $r["devolucion_estado_validacion"] ?? "pendiente",
                "observacion" => $r["devolucion_observacion_validacion"] ?? "",
            ];
        }
        if ($transportar === null) {
            require_once __DIR__ . "/correo.php";
            $transportar = "enviarCorreoResultadoRendicion";
        }

        $stmt = $conn->prepare("UPDATE rendiciones r
            LEFT JOIN postulaciones p ON p.id = r.postulacion_id
            INNER JOIN procesos_beca pb ON pb.id = COALESCE(p.proceso_id, r.proceso_id)
            SET r.correo_resultado_enviado = 2
            WHERE r.id = ? AND r.correo_resultado_enviado = 0
                AND r.estado = ? AND r.estado IN ('aprobada', 'rechazada')
                AND pb.fecha_resultados_rendicion IS NOT NULL AND pb.fecha_resultados_rendicion <= ?");
        $stmt->bind_param("iss", $id, $r["estado"], $ahora);
        $stmt->execute();
        $reservado = $stmt->affected_rows === 1;
        $stmt->close();
        if (!$reservado) {
            return "pendiente";
        }

        $enviado = $transportar(
            $destinatario,
            $nombre,
            $r["estado"],
            $documentos,
            $devolucion,
            (string) ($r["observaciones"] ?? ""),
        );
        $marca = $enviado ? 1 : 0;
        $stmt = $conn->prepare(
            "UPDATE rendiciones SET correo_resultado_enviado = ? WHERE id = ? AND correo_resultado_enviado = 2",
        );
        $stmt->bind_param("ii", $marca, $id);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            throw new RuntimeException("No se pudo registrar el resultado del envío.");
        }
        $stmt->close();
        return $enviado ? "enviado" : "error";
    } catch (Throwable $e) {
        error_log(
            "Correo de resultado, rendición " .
                $id .
                ": " .
                $e->getMessage() .
                ($reservado ? " Reserva conservada: comprobar entrega antes de reintentar." : ""),
        );
        return "error";
    }
}
