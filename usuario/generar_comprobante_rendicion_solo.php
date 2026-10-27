<?php

/**
 * Genera el comprobante PDF a partir de los datos guardados de la rendición.
 */

require_once __DIR__ . "/../config/sesion.php";

date_default_timezone_set("America/Santiago");

require_once "../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if (
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "usuario" ||
    !isset($_SESSION["acceso_rut_id"]) ||
    !isset($_SESSION["rut"])
) {
    http_response_code(403);
    exit("No tienes permisos para acceder a este documento.");
}

$accesoRutId = (int) $_SESSION["acceso_rut_id"];

$procesoSolo = procesoSoloRendicionActivo($conn);
if (!$procesoSolo || !usuarioHabilitadoSoloRendicion($conn, (int) $procesoSolo["id"], $accesoRutId)) {
    http_response_code(403);
    exit("No tienes permisos para acceder a este documento.");
}

if ($accesoRutId <= 0) {
    http_response_code(403);
    exit("Sesión no válida.");
}

$modo = $_GET["modo"] ?? "ver";

if (!in_array($modo, ["ver", "descargar"], true)) {
    $modo = "ver";
}

function escR(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function dineroR(mixed $valor): string
{
    return "$" . number_format((float) ($valor ?? 0), 0, ",", ".");
}

function fechaR(?string $valor, bool $hora = false): string
{
    if (empty($valor)) {
        return "-";
    }

    $timestamp = strtotime($valor);

    if ($timestamp === false) {
        return "-";
    }

    return date($hora ? "d/m/Y H:i:s" : "d/m/Y", $timestamp);
}

function categoriaR(?string $categoria, ?string $categoriaOtro = null): string
{
    if ($categoria === "otro" && !empty($categoriaOtro)) {
        return "Otro: " . $categoriaOtro;
    }

    return match ($categoria) {
        "arancel" => "Arancel",

        "matricula" => "Matrícula",

        "materiales" => "Materiales de estudio",

        "vestuario" => "Vestuario",

        "alimentacion" => "Alimentación",

        "otro" => "Otro",

        default => "-",
    };
}

$stmt = $conn->prepare(
    "
        SELECT

            r.id,
            r.estado,
            r.anio_cursa,
            r.fecha_envio,
            r.fecha_revision,
            r.observaciones,
            r.monto_anual,
            r.monto_devuelto,
            r.fecha_deposito_devolucion,
            r.numero_comprobante_devolucion,
            ar.rut,
            dru.nombres,
            dru.apellido_paterno,
            dru.apellido_materno,
            dru.fecha_nacimiento,
            dru.direccion,
            dru.telefono,
            dru.correo_electronico,
            dru.carrera,
            dru.nombre_establecimiento,
            dru.region,
            dru.comuna,
            dru.tiene_gratuidad,
            dru.promedio_ultimo_semestre,
            pb.nombre AS proceso_nombre

        FROM rendiciones r

        INNER JOIN accesos_rut ar
            ON ar.id = r.acceso_rut_id

        INNER JOIN datos_rendicion_usuario dru
            ON dru.acceso_rut_id = r.acceso_rut_id

        LEFT JOIN procesos_beca pb
            ON pb.id = r.proceso_id

        WHERE r.acceso_rut_id = ?
        AND r.proceso_id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    exit("No fue posible preparar el comprobante.");
}

$procesoSoloId = (int) $procesoSolo["id"];

$stmt->bind_param("ii", $accesoRutId, $procesoSoloId);

$stmt->execute();

$datos = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$datos) {
    http_response_code(404);
    exit("No existe una rendición para generar el comprobante.");
}

if (
    !in_array(
        $datos["estado"],
        ["enviada", "en_revision", "aprobada", "rechazada"],
        true,
    )
) {
    http_response_code(403);
    exit("La rendición todavía no ha sido enviada.");
}

$stmt = $conn->prepare(
    "
        SELECT

            fecha_gasto,
            categoria,
            categoria_otro,
            numero_documento,
            monto,
            nombre_archivo_original

        FROM rendicion_gastos

        WHERE rendicion_id = ?

        ORDER BY
            fecha_gasto,
            id
        ",
);

if (!$stmt) {
    exit("No fue posible consultar los gastos.");
}

$rendicionId = (int) $datos["id"];

$stmt->bind_param("i", $rendicionId);

$stmt->execute();

$resultado = $stmt->get_result();

$primer = [];
$segundo = [];
$anual = [];

while ($gasto = $resultado->fetch_assoc()) {
    $semestreGasto = (string) ($gasto["semestre"] ?? "");

    if ($semestreGasto === "primer_semestre") {
        $primer[] = $gasto;
    } elseif ($semestreGasto === "segundo_semestre") {
        $segundo[] = $gasto;
    } else {
        $anual[] = $gasto;
    }
}

$stmt->close();

$totalPrimer = 0.0;
$totalSegundo = 0.0;
$totalAnual = 0.0;

foreach ($primer as $gasto) {
    $totalPrimer += (float) $gasto["monto"];
}

foreach ($segundo as $gasto) {
    $totalSegundo += (float) $gasto["monto"];
}

foreach ($anual as $gasto) {
    $totalAnual += (float) $gasto["monto"];
}

$totalGeneral = $totalAnual > 0 ? $totalAnual : $totalPrimer + $totalSegundo;

$montoAnual = (float) ($datos["monto_anual"] ?? 0);

$montoPrimer = (float) ($datos["monto_primer_semestre"] ?? 0);

$montoSegundo = (float) ($datos["monto_segundo_semestre"] ?? 0);

$montoTotal = $montoAnual > 0 ? $montoAnual : $montoPrimer + $montoSegundo;

$montoDevuelto = (float) ($datos["monto_devuelto"] ?? 0);

$saldoDevolucionCalculado = max(0, $montoTotal - $totalGeneral);

$hayDevolucion = $montoDevuelto > 0;

$gratuidad = $datos["tiene_gratuidad"] === "si" ? "Sí" : "No";

function filasGastosAnualesR(array $gastos): string
{
    if (empty($gastos)) {
        return '
            <tr>
                <td colspan="5">
                    Sin gastos registrados.
                </td>
            </tr>
        ';
    }

    $html = "";

    foreach ($gastos as $indice => $gasto) {
        $html .=
            '
            <tr>
                <td>' .
            ($indice + 1) .
            '</td>

                <td>' .
            escR(fechaR($gasto["fecha_gasto"] ?? null)) .
            '</td>

                <td>' .
            escR(
                categoriaR(
                    $gasto["categoria"] ?? null,
                    $gasto["categoria_otro"] ?? null,
                ),
            ) .
            '</td>

                <td>' .
            escR($gasto["numero_documento"] ?? "-") .
            '</td>

                <td>' .
            escR(dineroR($gasto["monto"] ?? 0)) .
            '</td>

            </tr>
        ';
    }

    return $html;
}

$filasAnual = filasGastosAnualesR($anual);

$nombreCompleto = trim(
    $datos["nombres"] .
        " " .
        $datos["apellido_paterno"] .
        " " .
        $datos["apellido_materno"],
);

$html =
    '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>

    @page {
        margin: 30px 35px;
    }

    body {
        font-family:
            DejaVu Sans,
            sans-serif;

        font-size:
            10px;

        color:
            #1f2937;
    }

    .encabezado {
        background:
            #1d4ed8;

        color:
            white;

        padding:
            20px;

        margin-bottom:
            20px;
    }

    .encabezado h1 {
        margin:
            0 0 5px 0;

        font-size:
            20px;
    }

    .titulo {
        margin-top:
            18px;

        margin-bottom:
            8px;

        color:
            #1d4ed8;

        font-size:
            13px;

        font-weight:
            bold;

        border-bottom:
            2px solid #dbeafe;

        padding-bottom:
            4px;
    }

    table {
        width:
            100%;

        border-collapse:
            collapse;

        margin-bottom:
            10px;
    }

    th,
    td {
        border:
            1px solid #dbe2ea;

        padding:
            6px;

        vertical-align:
            top;
    }

    th {
        background:
            #eff6ff;

        color:
            #1e3a8a;

        font-weight:
            bold;
    }

    .etiqueta {
        width:
            32%;

        background:
            #f8fafc;

        font-weight:
            bold;
    }

    .total {
        font-weight:
            bold;

        color:
            #1e3a8a;
    }

    .pie {
        margin-top:
            25px;

        text-align:
            center;

        font-size:
            8px;

        color:
            #64748b;
    }

</style>
</head>

<body>

<div class="encabezado">

    <h1>
        Comprobante de rendición
    </h1>

    <div>
        Becas Estudiantiles
    </div>

    <div>
        Fecha de envío:
        ' .
    escR(fechaR($datos["fecha_envio"], true)) .
    '
    </div>

</div>

<div class="titulo">
    Beneficiario
</div>

<table>

    <tr>
        <td class="etiqueta">
            Nombre
        </td>
        <td>' .
    escR($nombreCompleto) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            RUT
        </td>
        <td>' .
    escR($datos["rut"]) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Correo
        </td>
        <td>' .
    escR($datos["correo_electronico"]) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Teléfono
        </td>
        <td>' .
    escR($datos["telefono"]) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Dirección
        </td>
        <td>' .
    escR($datos["direccion"]) .
    '</td>
    </tr>

</table>

<div class="titulo">
    Antecedentes educacionales
</div>

<table>

    <tr>
        <td class="etiqueta">
            Carrera
        </td>
        <td>' .
    escR($datos["carrera"]) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Establecimiento
        </td>
        <td>' .
    escR($datos["nombre_establecimiento"]) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Región
        </td>
        <td>' .
    escR($datos["region"]) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Comuna
        </td>
        <td>' .
    escR($datos["comuna"]) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Gratuidad
        </td>
        <td>' .
    escR($gratuidad) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Promedio último semestre
        </td>
        <td>' .
    escR(
        number_format((float) $datos["promedio_ultimo_semestre"], 2, ",", "."),
    ) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Año que cursa
        </td>
        <td>' .
    escR($datos["anio_cursa"]) .
    '</td>
    </tr>

</table>

<div class="titulo">
    Monto recibido
</div>

<table>

    <tr>
        <td class="etiqueta">
            Monto recibido anual
        </td>
        <td class="total">' .
    escR(dineroR($montoTotal)) .
    '</td>
    </tr>

</table>

<div class="titulo">
    Gastos de la rendición anual
</div>

<table>

    <thead>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Categoría</th>
            <th>N° documento</th>
            
            <th>Monto</th>
        </tr>
    </thead>

    <tbody>
        ' .
    $filasAnual .
    '
    </tbody>

</table>

<table>

    <tr>
        <td class="etiqueta">
            Total gastos
        </td>
        <td class="total">' .
    escR(dineroR($totalGeneral)) .
    '</td>
    </tr>

</table>

<div class="titulo">
    Devolución de saldo
</div>

<table>

    <tr>
        <td class="etiqueta">
            Monto recibido anual
        </td>
        <td>' .
    escR(dineroR($montoTotal)) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Total gastos
        </td>
        <td>' .
    escR(dineroR($totalGeneral)) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Saldo que corresponde devolver
        </td>
        <td class="total">' .
    escR(dineroR($saldoDevolucionCalculado)) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            ¿Realizó devolución?
        </td>
        <td>' .
    ($hayDevolucion ? "Sí" : "No") .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Monto efectivamente devuelto
        </td>
        <td>' .
    escR(dineroR($montoDevuelto)) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            Fecha de devolución
        </td>
        <td>' .
    escR(fechaR($datos["fecha_deposito_devolucion"] ?? null)) .
    '</td>
    </tr>

    <tr>
        <td class="etiqueta">
            N° comprobante
        </td>
        <td>' .
    escR($datos["numero_comprobante_devolucion"] ?? "-") .
    '</td>
    </tr>

</table>

<div class="pie">
    © ' .
    date("Y") .
    ' Becas Estudiantiles
</div>

</body>
</html>
';

$options = new Options();

$options->set("isRemoteEnabled", false);

$options->set("defaultFont", "DejaVu Sans");

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html, "UTF-8");

$dompdf->setPaper("A4", "portrait");

$dompdf->render();

$descargar = $modo === "descargar";

$dompdf->stream("comprobante_rendicion.pdf", ["Attachment" => $descargar]);

exit();
