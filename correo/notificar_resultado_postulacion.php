<?php

/** Compone el resultado y controla su envío: solo decisiones publicadas, con reserva para evitar duplicados. Un transporte simulado permite probar sin enviar correos reales. */

declare(strict_types=1);

require_once __DIR__ . "/../config/reglas_beca.php";

function contenidoCorreoPostulacion(
    string $nombre,
    string $estado,
    string $observacion,
    ?string $monto,
    array $documentos,
): string {
    $aceptada = $estado === "beneficiado";
    $lineas = [
        "Hola $nombre,",
        "",
        "Resultado de tu postulación: " . ($aceptada ? "APROBADA" : "RECHAZADA") . ".",
    ];
    $lineas[] = $aceptada
        ? "Monto de la beca asignada: " .
            ($monto !== null
                ? '$' . number_format((float) $monto, 0, ",", ".") . "."
                : "No informado; consulta a la entidad administradora.")
        : 'Monto de la beca asignada: $0. No se asignó una beca a esta postulación.';
    $lineas[] = "";
    $lineas[] = "DETALLE DE LOS DOCUMENTOS PRESENTADOS";
    $tipos = [
        "carnet" => "Cédula de identidad",
        "alumno_regular" => "Certificado de alumno regular",
        "certificado_notas" => "Certificado de notas",
        "registro_social" => "Registro Social de Hogares",
        "acreditacion_gratuidad" => "Acreditación de gratuidad",
    ];
    $estados = [
        "validado" => "Aprobado",
        "rechazado" => "Rechazado",
        "pendiente" => "Pendiente de revisión",
    ];
    foreach ($documentos as $documento) {
        $lineas[] = "";
        $lineas[] =
            ($tipos[$documento["tipo_documento"]] ?? $documento["tipo_documento"]) .
            ": " .
            ($estados[$documento["estado_validacion"]] ?? "Sin estado informado");
        $lineas[] = "Archivo: " . $documento["nombre_archivo_original"];
        $lineas[] =
            "Observación: " .
            (trim((string) ($documento["observacion"] ?? "")) ?: "Sin observaciones registradas.");
    }
    if (!$documentos) {
        $lineas[] = "No hay documentos registrados para esta postulación.";
    }
    if (trim($observacion) !== "") {
        $lineas[] = "";
        $lineas[] = "Observación general: " . $observacion;
    }
    $lineas[] = "";
    $lineas[] = $aceptada
        ? "Podrás realizar la rendición durante el plazo establecido en el portal."
        : "No tienes habilitada la rendición de esta beca.";
    $lineas[] = "Puedes consultar el resultado en tu cuenta del portal de Becas Estudiantiles.";
    return implode("\n", $lineas);
}
function contenidoHtmlCorreoPostulacion(
    string $nombre,
    string $estado,
    string $observacion,
    ?string $monto,
    array $documentos,
): string {
    $esc = static fn($valor) => htmlspecialchars(
        (string) $valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8",
    );
    $aceptada = $estado === "beneficiado";
    $estadoTexto = $aceptada ? "APROBADA" : "RECHAZADA";
    $nombreHtml = $esc($nombre ?: "Postulante");
    $montoHtml = $aceptada
        ? ($monto !== null
            ? '$' . number_format((float) $monto, 0, ",", ".") . ""
            : "No informado; consulta a la entidad administradora.")
        : '$0. No se asignó una beca a esta postulación.';
    $tipos = [
        "carnet" => "Cédula de identidad",
        "alumno_regular" => "Certificado de alumno regular",
        "certificado_notas" => "Certificado de notas",
        "registro_social" => "Registro Social de Hogares",
        "acreditacion_gratuidad" => "Acreditación de gratuidad",
    ];
    $estados = [
        "validado" => "ACEPTADO",
        "rechazado" => "RECHAZADO",
        "pendiente" => "PENDIENTE DE REVISIÓN",
    ];
    $filas = "";
    foreach ($documentos as $documento) {
        $tipo = $esc($tipos[$documento["tipo_documento"]] ?? $documento["tipo_documento"]);
        $archivo = $esc($documento["nombre_archivo_original"] ?? "");
        $revision = $documento["estado_validacion"] ?? "pendiente";
        $etiqueta = $estados[$revision] ?? "SIN ESTADO INFORMADO";
        $color =
            $revision === "validado"
                ? "#16a34a"
                : ($revision === "rechazado"
                    ? "#dc2626"
                    : "#92400e");
        $nota = nl2br(
            $esc(
                trim((string) ($documento["observacion"] ?? "")) ?:
                "Sin observaciones registradas.",
            ),
        );
        $filas .= "<div style='border:1px solid #e5e7eb;border-radius:12px;padding:15px;margin-bottom:15px;background:#ffffff;overflow-wrap:anywhere;'>
            <p style='margin:5px 0;'><strong>Documento:</strong> {$tipo}</p>
            <p style='margin:5px 0;'><strong>Archivo:</strong> {$archivo}</p>
            <p style='margin:5px 0;'><strong>Estado:</strong> <span style='font-weight:bold;color:{$color};'>{$etiqueta}</span></p>
            <p style='margin:5px 0;'><strong>Observación:</strong> {$nota}</p></div>";
    }
    if (!$documentos) {
        $filas = "<p>No hay documentos registrados para esta postulación.</p>";
    }
    $observacionHtml = nl2br($esc(trim($observacion) ?: "No se registró una observación general."));
    $siguiente = $aceptada
        ? "Podrás realizar la rendición durante el plazo establecido en el portal."
        : "No tienes habilitada la rendición de esta beca.";
    return "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Resultado de tu postulación</title></head>
    <body style='margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;'>
    <table role='presentation' width='100%' cellpadding='0' cellspacing='0'><tr><td align='center' style='padding:20px;'>
    <table role='presentation' width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;'>
    <tr><td style='background:#1d4ed8;padding:25px;text-align:center;color:#ffffff;'><h2 style='margin:0;font-size:24px;'>Becas Estudiantiles</h2></td></tr>
    <tr><td style='padding:25px;'>
    <p style='font-size:16px;'>Estimado/a <strong>{$nombreHtml}</strong></p><p style='font-size:16px;'>Tu postulación a la beca ha sido revisada.</p>
    <div style='background:#f1f5f9;border-radius:10px;padding:20px;text-align:center;margin:20px 0;'>
    <p style='margin:0;font-size:16px;font-weight:bold;'>Resultado de la postulación</p>
    <p style='margin:10px 0 0;font-size:24px;font-weight:bold;color:#1d4ed8;'>{$estadoTexto}</p>
    <p style='font-size:16px;'><strong>Monto de la beca asignada:</strong><br>{$montoHtml}</p></div>
    <h3>Detalle de la documentación</h3>{$filas}
    <h3 style='margin-top:30px;'>Observación general</h3>
    <div style='background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:15px;font-size:15px;'>{$observacionHtml}</div>
    <p style='font-size:15px;'>{$siguiente}</p><p style='font-size:15px;'>Puedes consultar el resultado en tu cuenta del portal de Becas Estudiantiles.</p>
    <p style='margin-top:30px;font-size:14px;color:#6b7280;'>Este correo fue enviado automáticamente por el sistema de Becas Estudiantiles.<br>No respondas a este mensaje.</p>
    <p style='font-size:15px;'>Saludos,<br><strong>Becas Estudiantiles</strong></p>
    </td></tr></table></td></tr></table></body></html>";
}

function enviarCorreoResultadoPostulacion(
    string $correo,
    string $nombre,
    string $estado,
    string $observacion,
    ?string $monto,
    array $documentos = [],
): bool {
    require_once __DIR__ . "/correo.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = CORREO_SMTP_HOST;
    $mail->Port = CORREO_SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = CORREO_SMTP_USUARIO;
    $mail->Password = CORREO_SMTP_PASSWORD;
    $mail->SMTPSecure = CORREO_SMTP_ENCRIPTACION;
    $mail->CharSet = "UTF-8";
    $mail->setFrom(CORREO_REMITENTE, CORREO_NOMBRE_REMITENTE);
    $mail->addAddress($correo, $nombre);
    $mail->Subject = "Resultado de tu postulación a la beca";
    $mail->isHTML(true);
    $mail->Body = contenidoHtmlCorreoPostulacion(
        $nombre,
        $estado,
        $observacion,
        $monto,
        $documentos,
    );
    $mail->AltBody = contenidoCorreoPostulacion(
        $nombre,
        $estado,
        $observacion,
        $monto,
        $documentos,
    );
    return $mail->send();
}

function notificarResultadoPostulacion(mysqli $conn, int $id, ?callable $transportar = null): string
{
    $bloqueado = false;
    $clave = "beca_postulacion_" . $id;
    try {
        $stmt = $conn->prepare("SELECT GET_LOCK(?, 0)");
        $stmt->bind_param("s", $clave);
        $stmt->execute();
        $bloqueado = (int) $stmt->get_result()->fetch_row()[0] === 1;
        $stmt->close();
        if (!$bloqueado) {
            return "en_proceso";
        }
        $stmt = $conn->prepare('SELECT p.*, pb.fecha_resultados, d.correo_electronico, d.nombres, d.apellido_paterno, d.apellido_materno
            FROM postulaciones p INNER JOIN procesos_beca pb ON pb.id = p.proceso_id
            INNER JOIN datos_personales d ON d.postulacion_id = p.id WHERE p.id = ? LIMIT 1');
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (
            !$p ||
            !resultadoBecaPublicado($p) ||
            !in_array($p["estado"], ["beneficiado", "no_beneficiado"], true)
        ) {
            return "pendiente";
        }
        if (!filter_var($p["correo_electronico"], FILTER_VALIDATE_EMAIL)) {
            return "error";
        }
        $stmt = $conn->prepare(
            "SELECT estado FROM correos_resultado_postulacion WHERE postulacion_id = ?",
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $registro = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($registro && $registro["estado"] === "enviado") {
            return "ya_enviado";
        }
        if ($registro && $registro["estado"] === "en_proceso") {
            return "revisar_entrega";
        }
        $stmt = $conn->prepare('SELECT tipo_documento, nombre_archivo_original, estado_validacion, observacion
            FROM documentos WHERE postulacion_id = ? ORDER BY id ASC');
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $documentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO correos_resultado_postulacion (postulacion_id, estado) VALUES (?, 'en_proceso')
            ON DUPLICATE KEY UPDATE estado = 'en_proceso'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $transportar ??= "enviarCorreoResultadoPostulacion";
        $enviado = $transportar(
            $p["correo_electronico"],
            trim(
                implode(
                    " ",
                    array_filter(
                        array_map("trim", [
                            $p["nombres"] ?? "",
                            $p["apellido_paterno"] ?? "",
                            $p["apellido_materno"] ?? "",
                        ]),
                    ),
                ),
            ),
            $p["estado"],
            (string) ($p["observacion_admin"] ?? ""),
            isset($p["monto_asignado"]) ? (string) $p["monto_asignado"] : null,
            $documentos,
        );
        $estado = $enviado ? "enviado" : "pendiente";
        $stmt = $conn->prepare(
            "UPDATE correos_resultado_postulacion SET estado = ? WHERE postulacion_id = ?",
        );
        $stmt->bind_param("si", $estado, $id);
        $stmt->execute();
        $stmt->close();
        return $enviado ? "enviado" : "error";
    } catch (Throwable $e) {
        // Una entrega incierta se revisa antes de reintentar para evitar duplicados.
        error_log("Error al notificar postulación " . $id . ": " . $e->getMessage());
        return "error";
    } finally {
        if ($bloqueado) {
            $stmt = $conn->prepare("SELECT RELEASE_LOCK(?)");
            $stmt->bind_param("s", $clave);
            $stmt->execute();
            $stmt->close();
        }
    }
}
