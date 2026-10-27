<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . "/../vendor/autoload.php";

require_once __DIR__ . "/../config/entorno.php";

// Credenciales privadas por servidor.
define("CORREO_SMTP_HOST", configuracionBecas("BECAS_SMTP_HOST"));
define("CORREO_SMTP_PORT", (int) configuracionBecas("BECAS_SMTP_PORT"));
define("CORREO_SMTP_USUARIO", configuracionBecas("BECAS_SMTP_USER"));
define("CORREO_SMTP_PASSWORD", configuracionBecas("BECAS_SMTP_PASSWORD"));
define("CORREO_SMTP_ENCRIPTACION", configuracionBecas("BECAS_SMTP_ENCRYPTION"));

define("CORREO_REMITENTE", configuracionBecas("BECAS_MAIL_FROM"));
define("CORREO_NOMBRE_REMITENTE", configuracionBecas("BECAS_MAIL_NAME"));

function enviarCorreoResultadoRendicion(
    string $destinatario,
    string $nombreDestinatario,
    string $estado,
    array $documentos,
    ?array $devolucion,
    string $observacionGeneral,
): bool {
    $destinatario = trim($destinatario);

    if ($destinatario === "" || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        error_log("Correo de rendición inválido: " . $destinatario);

        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();

        $mail->Host = CORREO_SMTP_HOST;

        $mail->SMTPAuth = true;

        $mail->Username = CORREO_SMTP_USUARIO;

        $mail->Password = CORREO_SMTP_PASSWORD;

        $mail->Port = CORREO_SMTP_PORT;

        if (CORREO_SMTP_ENCRIPTACION === "ssl") {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->CharSet = "UTF-8";

        $mail->Encoding = "base64";

        $mail->setFrom(CORREO_REMITENTE, CORREO_NOMBRE_REMITENTE);

        $mail->addAddress(
            $destinatario,
            $nombreDestinatario !== "" ? $nombreDestinatario : "Postulante",
        );

        $estadoTexto = $estado === "aprobada" ? "APROBADA" : "RECHAZADA";

        $tituloEstado = $estado === "aprobada" ? "Rendición aprobada" : "Rendición rechazada";

        $filasDocumentos = "";

        foreach ($documentos as $documento) {
            $tipo = htmlspecialchars(
                (string) ($documento["tipo_documento"] ?? "Documento"),
                ENT_QUOTES,
                "UTF-8",
            );

            $numero = htmlspecialchars(
                (string) ($documento["numero_documento"] ?? ""),
                ENT_QUOTES,
                "UTF-8",
            );

            $estadoDocumento =
                ($documento["estado_validacion"] ?? "") === "validado" ? "ACEPTADO" : "RECHAZADO";

            $observacion = trim((string) ($documento["observacion_validacion"] ?? ""));

            $observacionHtml =
                $observacion !== ""
                    ? nl2br(htmlspecialchars($observacion, ENT_QUOTES, "UTF-8"))
                    : "Sin observación.";

            $colorEstado = $estadoDocumento === "ACEPTADO" ? "#16a34a" : "#dc2626";

            $filasDocumentos .= "

            <div style='

                border:1px solid #e5e7eb;
                border-radius:12px;
                padding:15px;
                margin-bottom:15px;
                background:#ffffff;
            '>

                <p style='margin:5px 0;'>

                    <strong>
                    Documento:
                    </strong>

                    {$tipo}

                </p>

                <p style='margin:5px 0;'>

                    <strong>
                    N.º documento:
                    </strong>

                    {$numero}

                </p>



                <p style='margin:5px 0;'>

                    <strong>
                    Estado:
                    </strong>

                    <span style='

                        font-weight:bold;

                        color:{$colorEstado};

                    '>

                        {$estadoDocumento}

                    </span>


                </p>

                <p style='margin:5px 0;'>

                    <strong>
                    Observación:
                    </strong>

                    <br>

                    {$observacionHtml}

                </p>


            </div>

            ";
        }

        $devolucionHtml = "";

        if (is_array($devolucion)) {
            $estadoDevolucion =
                ($devolucion["estado"] ?? "") === "validado" ? "ACEPTADO" : "RECHAZADO";

            $observacionDevolucion = trim((string) ($devolucion["observacion"] ?? ""));

            $observacionDevolucionHtml =
                $observacionDevolucion !== ""
                    ? nl2br(htmlspecialchars($observacionDevolucion, ENT_QUOTES, "UTF-8"))
                    : "Sin observación.";

            $colorDevolucion = $estadoDevolucion === "ACEPTADO" ? "#16a34a" : "#dc2626";

            $devolucionHtml = "

    <h3 style='

        margin-top:30px;
        color:#1f2937;

    '>

        Comprobante de devolución

    </h3>



    <div style='

        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:15px;
        background:#ffffff;
    '>

        <p style='margin:5px 0;'>

            <strong>

            Estado:

            </strong>


            <span style='

                font-weight:bold;

                color:{$colorDevolucion};

            '>

                {$estadoDevolucion}

            </span>

        </p>

        <p style='margin:5px 0;'>

            <strong>

            Observación:

            </strong>

            <br>

            {$observacionDevolucionHtml}

        </p>

    </div>

    ";
        }

        $observacionGeneralHtml =
            trim($observacionGeneral) !== ""
                ? nl2br(htmlspecialchars($observacionGeneral, ENT_QUOTES, "UTF-8"))
                : "No se registró una observación general.";

        $nombreHtml = htmlspecialchars(
            $nombreDestinatario !== "" ? $nombreDestinatario : "Postulante",

            ENT_QUOTES,

            "UTF-8",
        );

        $mail->isHTML(true);

        $mail->Subject = "Resultado de tu rendición - Becas Estudiantiles";

        $mail->Body = "

<!DOCTYPE html>

<html lang='es'>

<head>

<meta charset='UTF-8'>

<meta name='viewport' content='width=device-width, initial-scale=1.0'>

<title>

{$tituloEstado}

</title>

</head>

<body style='

margin:0;

padding:0;

background:#f5f7fb;

font-family:Arial,Helvetica,sans-serif;

color:#1f2937;

'>

<table width='100%' cellpadding='0' cellspacing='0'>

<tr>

<td align='center' style='padding:20px;'>

<table width='600' cellpadding='0' cellspacing='0' style='

max-width:600px;

width:100%;

background:#ffffff;

border-radius:12px;

overflow:hidden;

'>

<tr>

<td style='

background:#1d4ed8;

padding:25px;

text-align:center;

color:#ffffff;

'>

<h2 style='

margin:0;

font-size:24px;

'>

Becas Estudiantiles

</h2>

</td>

</tr>

<tr>

<td style='

padding:25px;

'>

<p style='font-size:16px;'>

Estimado/a

<strong>

{$nombreHtml}

</strong>

</p>

<p style='font-size:16px;'>

Tu rendición de beca ha sido revisada.

</p>

<div style='

background:#f1f5f9;

border-radius:10px;

padding:20px;

text-align:center;

margin:20px 0;

'>

<p style='

margin:0;

font-size:16px;

font-weight:bold;

'>

Resultado de la rendición

</p>

<p style='

margin:10px 0 0 0;

font-size:24px;

font-weight:bold;

color:#1d4ed8;

'>

{$estadoTexto}

</p>

</div>

<h3>

Detalle de la documentación

</h3>

{$filasDocumentos}

{$devolucionHtml}

<h3 style='margin-top:30px;'>

Observación general

</h3>

<div style='

background:#f8fafc;

border:1px solid #e5e7eb;

border-radius:10px;

padding:15px;

font-size:15px;

'>

{$observacionGeneralHtml}

</div>

<p style='

margin-top:30px;

font-size:14px;

color:#6b7280;

'>

Este correo fue enviado automáticamente por el sistema de Becas Estudiantiles.

<br>

No respondas a este mensaje.

</p>

<p style='font-size:15px;'>

Saludos,

<br>

<strong>

Becas Estudiantiles

</strong>

</p>

</td>

</tr>

</table>

</td>

</tr>

</table>

</body>

</html>

";

        $mail->AltBody =
            "Resultado de tu rendición: " .
            $estadoTexto .
            "\n\n" .
            "Detalle de la documentación:\n";

        foreach ($documentos as $documento) {
            $estadoDocumento =
                ($documento["estado_validacion"] ?? "") === "validado" ? "ACEPTADO" : "RECHAZADO";

            $mail->AltBody .=
                "- " .
                ($documento["tipo_documento"] ?? "Documento") .
                " | " .
                $estadoDocumento .
                " | Observación: " .
                (trim((string) ($documento["observacion_validacion"] ?? "")) !== ""
                    ? $documento["observacion_validacion"]
                    : "Sin observación.") .
                "\n";
        }

        if (is_array($devolucion)) {
            $mail->AltBody .=
                "\nComprobante de devolución:\n" .
                "Estado: " .
                (($devolucion["estado"] ?? "") === "validado" ? "ACEPTADO" : "RECHAZADO") .
                "\n";

            if (trim((string) ($devolucion["observacion"] ?? "")) !== "") {
                $mail->AltBody .= "Observación: " . $devolucion["observacion"] . "\n";
            }
        }

        if (trim($observacionGeneral) !== "") {
            $mail->AltBody .= "\nObservación general:\n" . $observacionGeneral . "\n";
        }

        $mail->send();

        return true;
    } catch (Exception $e) {
        error_log(
            "Error enviando correo de resultado de rendición: " .
                $mail->ErrorInfo .
                " | " .
                $e->getMessage(),
        );

        return false;
    }
}
