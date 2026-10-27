<?php
require_once __DIR__ . "/../config/aviso.php";

/** Exportación protegida por rol y token de sesión. Los informes PDF y Excel comparten el mismo contenido y el resumen financiero agrega gastos y devoluciones sin duplicarlos. */

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";
require_once "../vendor/autoload.php";
require_once __DIR__ . "/resumen_financiero.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    !in_array($_SESSION["rol"], ["administrador", "superior"], true)
) {
    http_response_code(403);
    mostrarAvisoAccesoBecas("No tienes permisos para realizar esta acción.");
}

function eArchivo(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function textoEstadoPostulacion(?string $estado): string
{
    return match ($estado) {
        "pendiente" => "Pendiente",

        "en_revision" => "En revisión",

        "beneficiado" => "Beneficiado",

        "no_beneficiado" => "No beneficiado",

        default => ucfirst(str_replace("_", " ", (string) $estado)),
    };
}

function textoEstadoRendicion(?string $estado): string
{
    return match ($estado) {
        "enviada", "en_revision" => "En revisión",

        "aprobada" => "Aprobada",

        "rechazada" => "Rechazada",

        default => ucfirst(str_replace("_", " ", (string) $estado)),
    };
}

function claseEstadoArchivo(?string $estado): string
{
    $estado = strtolower((string) $estado);
    if (in_array($estado, ["rechazada", "no_beneficiado"], true)) {
        return "estado-rechazado";
    }
    if (in_array($estado, ["pendiente", "en_revision", "enviada"], true)) {
        return "estado-en-revision";
    }
    return "estado-aprobado";
}

function estiloEstadoArchivo(string $clase): string
{
    return match ($clase) {
        "estado-aprobado" => "display:inline-block;padding:5px 9px;border-radius:999px;font-weight:700;color:#166534;background:#dcfce7",
        "estado-rechazado" => "display:inline-block;padding:5px 9px;border-radius:999px;font-weight:700;color:#b91c1c;background:#fee2e2",
        default => "display:inline-block;padding:5px 9px;border-radius:999px;font-weight:700;color:#9a3412;background:#ffedd5",
    };
}

function obtenerDescargador(mysqli $conn): array
{
    $idUsuario = (int) ($_SESSION["usuario_id"] ?? 0);
    $consulta = $conn->prepare(
        "SELECT nombres, apellido_paterno, apellido_materno, correo, rol FROM usuarios WHERE id=? LIMIT 1",
    );
    $consulta->bind_param("i", $idUsuario);
    $consulta->execute();
    $usuario = $consulta->get_result()->fetch_assoc() ?: [];
    $consulta->close();

    $nombre = trim(implode(" ", array_filter([
        $usuario["nombres"] ?? "",
        $usuario["apellido_paterno"] ?? "",
        $usuario["apellido_materno"] ?? "",
    ])));
    return [
        "nombre" => $nombre !== "" ? $nombre : ($usuario["correo"] ?? "Usuario sin nombre"),
        "rol" => $usuario["rol"] ?? ($_SESSION["rol"] ?? "Sin rol"),
    ];
}

function obtenerPostulantes(mysqli $conn): array
{
    require_once __DIR__ . "/../config/proceso_activo.php";
    $procesoDescargaId = idProcesoActivoBeca($conn);

    $sql = "
        SELECT

            dp.rut,
            dp.nombres, dp.apellido_paterno, dp.apellido_materno,
            dp.direccion,
            dp.telefono,
            dp.correo_electronico,

            p.estado

        FROM postulaciones p

        INNER JOIN datos_personales dp
            ON dp.postulacion_id = p.id
        WHERE p.proceso_id=$procesoDescargaId

        ORDER BY
            p.fecha_postulacion DESC,
            p.id DESC
    ";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        throw new RuntimeException("No fue posible consultar los postulantes.");
    }

    return $resultado->fetch_all(MYSQLI_ASSOC);
}

function obtenerRendiciones(mysqli $conn): array
{
    require_once __DIR__ . "/../config/proceso_activo.php";
    $procesoDescargaId = idProcesoActivoBeca($conn);

    $sql = "
        SELECT

            dp.rut,
            dp.nombres, dp.apellido_paterno, dp.apellido_materno,
            dp.direccion,
            dp.telefono,
            dp.correo_electronico,

            r.estado

        FROM rendiciones r

        LEFT JOIN postulaciones p ON p.id = r.postulacion_id

        INNER JOIN personas_rendicion dp ON dp.rendicion_id = r.id

        WHERE COALESCE(r.proceso_id,p.proceso_id)=$procesoDescargaId AND r.estado IN (
            'enviada',
            'en_revision',
            'aprobada',
            'rechazada'
        )

        ORDER BY
            r.fecha_envio DESC,
            r.id DESC
    ";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        throw new RuntimeException("No fue posible consultar las rendiciones.");
    }

    return $resultado->fetch_all(MYSQLI_ASSOC);
}

function generarTabla(string $titulo, array $datos, string $tipo): string
{
    $html =
        '

        <section class="grupo">

            <div class="grupo-encabezado">

                <h2>' .
        eArchivo($titulo) .
        '
                </h2>

                <span>
                    ' .
        count($datos) .
        '
                    registros
                </span>

            </div>

            <table>

                <thead>

                    <tr>

                        <th class="numero">
                            N°
                        </th>

                        <th class="rut">
                            RUT
                        </th>

                        <th class="nombre">Nombre completo</th><th class="direccion">
                            Dirección
                        </th>

                        <th class="telefono">
                            Teléfono
                        </th>

                        <th class="correo">
                            Correo electrónico
                        </th>

                        <th class="estado">
                            Estado
                        </th>

                    </tr>

                </thead>

                <tbody>
    ';

    if (empty($datos)) {
        $html .= '

            <tr>

                <td colspan="7" class="sin-registros">

                    No existen registros.

                </td>

            </tr>
        ';
    } else {
        $numero = 1;

        foreach ($datos as $fila) {
            $fila["nombre_completo"] = implode(
                " ",
                array_filter(
                    [
                        trim((string) ($fila["nombres"] ?? "")),
                        trim((string) ($fila["apellido_paterno"] ?? "")),
                        trim((string) ($fila["apellido_materno"] ?? "")),
                    ],
                    static fn($parte) => $parte !== "",
                ),
            );
            if ($fila["nombre_completo"] === "") {
                $fila["nombre_completo"] = "-";
            }

            if ($tipo === "postulantes") {
                $estado = textoEstadoPostulacion($fila["estado"] ?? null);
            } else {
                $estado = textoEstadoRendicion($fila["estado"] ?? null);
            }
            $claseEstado = claseEstadoArchivo($fila["estado"] ?? null);
            $html .=
                '

                <tr>

                    <td>

                        ' .
                $numero .
                ".-" .
                '

                    </td>

                    <td>

                        ' .
                eArchivo($fila["rut"] ?? "-") .
                '

                    </td>

                    <td>

                        ' .
                eArchivo($fila["nombre_completo"] ?? "-") .
                "</td><td>" .
                eArchivo($fila["direccion"] ?? "-") .
                '

                    </td>

                    <td>

                        ' .
                eArchivo($fila["telefono"] ?? "-") .
                '

                    </td>

                    <td>

                        ' .
                eArchivo($fila["correo_electronico"] ?? "-") .
                '

                    </td>

                    <td>

                        <span class="estado-texto ' .
                $claseEstado .
                '" style="' .
                estiloEstadoArchivo($claseEstado) .
                '">

                            ' .
                eArchivo($estado) .
                '

                        </span>

                    </td>

                </tr>
            ';

            $numero++;
        }
    }

    $html .= '

                </tbody>

            </table>

        </section>
    ';

    return preg_replace_callback(
        "/>\s+|\s+</u",
        static fn($coincidencia) => str_starts_with($coincidencia[0], ">") ? ">" : "<",
        $html,
    );
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: archivos_descargar.php");

    exit();
}

$tokenRecibido = (string) ($_POST["token"] ?? "");

$tokenSesion = (string) ($_SESSION["token_archivos_descargar"] ?? "");

if ($tokenSesion === "" || !hash_equals($tokenSesion, $tokenRecibido)) {
    $_SESSION["archivo_error"] = "La solicitud no es válida.";

    header("Location: archivos_descargar.php");

    exit();
}

$tipoDatos = trim((string) ($_POST["tipo_datos"] ?? ""));

$tiposPermitidos = ["postulantes", "rendiciones", "ambos", "resumen_financiero"];

if (!in_array($tipoDatos, $tiposPermitidos, true)) {
    $_SESSION["archivo_error"] = "Selecciona qué información deseas descargar.";

    header("Location: archivos_descargar.php");

    exit();
}

$formato = trim((string) ($_POST["formato"] ?? ""));

if (!in_array($formato, ["pdf", "excel"], true)) {
    $_SESSION["archivo_error"] = "Selecciona PDF o Excel.";

    header("Location: archivos_descargar.php");

    exit();
}

try {
    $descargador = obtenerDescargador($conn);
    $fechaGeneracion = date("d-m-Y H:i:s");
    $postulantes = [];
    $rendiciones = [];

    if ($tipoDatos === "postulantes" || $tipoDatos === "ambos") {
        $postulantes = obtenerPostulantes($conn);
    }

    if ($tipoDatos === "rendiciones" || $tipoDatos === "ambos") {
        $rendiciones = obtenerRendiciones($conn);
    }

    $contenido =
        $tipoDatos === "resumen_financiero"
            ? generarResumenFinanciero(obtenerResumenFinanciero($conn))
            : "";

    if ($tipoDatos === "postulantes" || $tipoDatos === "ambos") {
        $contenido .= generarTabla("Postulantes", $postulantes, "postulantes");
    }

    if ($tipoDatos === "rendiciones" || $tipoDatos === "ambos") {
        $contenido .= generarTabla(
            "Personas que realizaron rendición",
            $rendiciones,
            "rendiciones",
        );
    }
    $html =
        '
<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <style>

        @page {

            margin:
                28px
                34px
                38px
                34px;

        }

        * {

            box-sizing:
                border-box;

        }

        body {

            margin:
                0;

            font-family:
                DejaVu Sans,
                Arial,
                sans-serif;

            color:
                #1f2937;

            font-size:
                10px;

            line-height:
                1.4;

        }

        .cabecera {

            margin-bottom:
                26px;

            padding-bottom:
                14px;

            border-bottom:
                2px solid #1d4ed8;

        }

        .cabecera h1 {

            margin:
                0 0 5px;

            color:
                #1d4ed8;

            font-size:
                21px;

        }

        .cabecera p {

            margin:
                0;

            color:
                #64748b;

            font-size:
                9px;

        }

        .grupo {

            margin-bottom:
                28px;

        }

        .grupo + .grupo {

            page-break-before:
                always;

        }

        .grupo-encabezado {

            margin-bottom:
                10px;

        }

        .grupo-encabezado h2 {

            display:
                inline-block;

            margin:
                0;

            color:
                #111827;

            font-size:
                15px;

        }

        .grupo-encabezado span {

            margin-left:
                8px;

            color:
                #64748b;

            font-size:
                9px;

        }

        table {

            width:
                100%;

            border-collapse:
                collapse;

            table-layout:
                fixed;

        }

        .formato-excel table {

            width:
                auto;

            table-layout:
                auto;

        }

        thead {

            display:
                table-header-group;

        }

        tr {

            page-break-inside:
                avoid;

        }

        th,
        td {
            height: auto;
            line-height: 1.3;
            white-space: normal;

            border:
                1px solid #d7dee8;

            padding:
                7px 6px;

            vertical-align:
                middle;

            word-wrap:
                break-word;

            overflow-wrap:
                break-word;

        }

        th {

            color:
                #1e3a8a;

            background:
                #dbeafe;

            font-size:
                9px;

            font-weight:
                700;

            text-align:
                left;

        }

        td {

            color:
                #334155;

            background:
                #ffffff;

            font-size:
                9px;

        }

        tbody tr:nth-child(even) td {

            background:
                #f8fafc;

        }

        .numero {

            width:
                4%;

            text-align:
                center;

        }

        .rut {

            width:
                11%;

        }

        .nombre { width: 22%; }

        .direccion {

            width:
                18%;

        }

        .telefono {

            width:
                10%;

        }

        .correo {

            width:
                23%;

        }

        .estado {

            width:
                12%;

        }

        .estado-texto {
            display:
                inline-block;
            padding:
                5px 9px;
            border-radius:
                999px;
            font-weight:
                700;
        }

        .estado-aprobado {
            color:
                #166534;
            background:
                #dcfce7;
        }

        .estado-rechazado {
            color:
                #b91c1c;
            background:
                #fee2e2;
        }

        .estado-en-revision {
            color:
                #9a3412;
            background:
                #ffedd5;
        }

        .metadatos-exportacion {
            margin-bottom:
                24px;
            padding:
                14px 16px;
            border:
                1px solid #cbd5e1;
            background:
                #f8fafc;
        }

        .metadatos-exportacion p {
            margin:
                0 0 8px;
            color:
                #334155;
        }

        .metadatos-exportacion p:last-child {
            margin-bottom:
                0;
        }

        .sin-registros {

            padding:
                20px;

            color:
                #64748b;

            text-align:
                center;

        }

    </style>

</head>

<body class="formato-' .
        eArchivo($formato) .
        '">

    <div class="cabecera">

        <h1>

            Becas Estudiantiles

        </h1>

        <p>

            Exportación administrativa

        </p>

    </div>

    <div class="metadatos-exportacion">
        <p><strong>Fecha y hora de generación:</strong><br>' .
        eArchivo($fechaGeneracion) .
        '</p>
        <p><strong>Descargado por:</strong> ' .
        eArchivo($descargador["nombre"]) .
        '<br><strong>Rol:</strong> ' .
        eArchivo($descargador["rol"]) .
        '</p>
    </div>

    ' .
        $contenido .
        '

</body>

</html>
';

    if ($formato === "excel") {
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");

        header(
            'Content-Disposition: attachment; filename="becas_' .
                $tipoDatos .
                "_" .
                date("Y-m-d_H-i") .
                '.xls"',
        );

        header("Cache-Control: max-age=0");

        echo "\xEF\xBB\xBF";

        echo $html;

        exit();
    }
    $options = new Options();
    $options->set("defaultFont", "DejaVu Sans");
    $options->set("isRemoteEnabled", false);
    $options = new Options();
    $options->set("defaultFont", "DejaVu Sans");
    $options->set("isRemoteEnabled", false);
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html, "UTF-8");

    $dompdf->setPaper("A4", "landscape");

    $dompdf->render();

    $canvas = $dompdf->getCanvas();

    $fontMetrics = $dompdf->getFontMetrics();

    $font = $fontMetrics->getFont("DejaVu Sans", "normal");

    $canvas->page_text(690, 565, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 7, [100, 116, 139]);

    $nombreArchivo = "becas_" . $tipoDatos . "_" . date("Y-m-d_H-i") . ".pdf";

    $dompdf->stream($nombreArchivo, [
        "Attachment" => true,
    ]);

    exit();
} catch (Throwable $e) {
    $_SESSION["archivo_error"] = $e->getMessage();

    header("Location: archivos_descargar.php");

    exit();
}
