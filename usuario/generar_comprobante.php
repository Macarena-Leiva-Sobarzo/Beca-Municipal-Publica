<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION["usuario_id"])) {
    http_response_code(403);

    mostrarAvisoAccesoBecas("Sesión no válida.");
}

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "usuario") {
    http_response_code(403);

    mostrarAvisoAccesoBecas("No tienes permisos para acceder a este documento.");
}

$usuario_id = (int) $_SESSION["usuario_id"];

$modo = $_GET["modo"] ?? "ver";

if (!in_array($modo, ["ver", "descargar"], true)) {
    $modo = "ver";
}

$sql = "
    SELECT

        p.id,
        p.estado,
        p.fecha_postulacion,

        dp.nombres,
        dp.apellido_paterno,
        dp.apellido_materno,
        dp.rut,
        dp.fecha_nacimiento,
        dp.direccion,
        dp.telefono,
        dp.correo_electronico,

        de.carrera,
        de.nombre_establecimiento,
        de.region,
        de.comuna,
        de.tiene_gratuidad,
        de.promedio_ultimo_semestre,

        db.numero_cuenta,
        db.banco_otro,

        b.nombre AS banco_nombre,

        tc.nombre AS tipo_cuenta_nombre

    FROM postulaciones p

    INNER JOIN procesos_beca pb
        ON pb.id = p.proceso_id

    INNER JOIN datos_personales dp
        ON dp.postulacion_id = p.id

    INNER JOIN datos_educacionales de
        ON de.postulacion_id = p.id

    INNER JOIN datos_bancarios db
        ON db.postulacion_id = p.id

    LEFT JOIN bancos b
        ON b.id = db.banco_id

    INNER JOIN tipos_cuenta tc
        ON tc.id = db.tipo_cuenta_id

    WHERE p.usuario_id = ?
        AND pb.activo = 1

    ORDER BY p.id DESC

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible preparar el comprobante.");
}

$stmt->bind_param("i", $usuario_id);

$stmt->execute();

$resultado = $stmt->get_result();

$datos = $resultado->fetch_assoc();

$stmt->close();

if (!$datos) {
    http_response_code(404);

    mostrarAvisoBecas("No existe una postulación para generar el comprobante.");
}

if ($datos["estado"] === "pendiente") {
    http_response_code(403);

    mostrarAvisoBecas("La postulación todavía no ha sido enviada.");
}

$stmt = $conn->prepare(
    "
        SELECT
            tipo_documento,
            estado_validacion
        FROM documentos
        WHERE postulacion_id = ?
        ",
);

$stmt->bind_param("i", $datos["id"]);

$stmt->execute();

$resultadoDocumentos = $stmt->get_result();

$documentos = [];

while ($documento = $resultadoDocumentos->fetch_assoc()) {
    $documentos[$documento["tipo_documento"]] = $documento["estado_validacion"];
}

$stmt->close();

function esc(?string $valor): string
{
    return htmlspecialchars($valor ?? "", ENT_QUOTES, "UTF-8");
}

function mostrarFecha(?string $fecha): string
{
    if (empty($fecha)) {
        return "-";
    }

    $timestamp = strtotime($fecha);

    if ($timestamp === false) {
        return esc($fecha);
    }

    return date("d/m/Y", $timestamp);
}

$banco = $datos["banco_nombre"] ?: $datos["banco_otro"];

$banco = $banco ?: "-";

$gratuidad = $datos["tiene_gratuidad"] === "si" ? "Sí" : "No";

$estadoCarnet = isset($documentos["carnet"]) ? "ADJUNTADO" : "NO ADJUNTADO";

$estadoAlumno = isset($documentos["alumno_regular"]) ? "ADJUNTADO" : "NO ADJUNTADO";

$estadoNotas = isset($documentos["certificado_notas"]) ? "ADJUNTADO" : "NO ADJUNTADO";

$estadoRegistroSocial = isset($documentos["registro_social"]) ? "ADJUNTADO" : "NO ADJUNTADO";
$estadoGratuidadDocumento = isset($documentos["acreditacion_gratuidad"])
    ? "ADJUNTADO"
    : "NO ADJUNTADO";
$fechaPostulacion = "-";

if (!empty($datos["fecha_postulacion"])) {
    $timestamp = strtotime($datos["fecha_postulacion"]);

    if ($timestamp !== false) {
        $fechaPostulacion = date("d/m/Y H:i", $timestamp);
    }
}

$html =
    '

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<style>

    @page {
        margin: 35px 45px;
    }


    * {
        box-sizing: border-box;
    }


    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #1f2937;
        line-height: 1.5;
    }


    .encabezado {
        background: #1d4ed8;
        color: #ffffff;
        padding: 24px;
        margin-bottom: 25px;
        border-radius: 8px;
    }


    .encabezado h1 {
        margin: 0 0 6px 0;
        font-size: 22px;
    }


    .encabezado p {
        margin: 0;
        font-size: 11px;
    }


    .fecha {
        margin-top: 12px;
        font-size: 10px;
    }


    .titulo-seccion {
        font-size: 14px;
        color: #1d4ed8;
        margin-top: 22px;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 2px solid #dbeafe;
    }


    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }


    td {
        border: 1px solid #e5e7eb;
        padding: 8px;
        vertical-align: top;
    }


    .etiqueta {
        width: 35%;
        background: #f8fafc;
        font-weight: bold;
    }


    .documentos {
        width: 100%;
        margin-top: 5px;
    }


    .documentos td {
        padding: 10px;
    }


    .adjuntado {
        color: #166534;
        font-weight: bold;
    }


    .pie {
        margin-top: 30px;
        padding-top: 12px;
        border-top: 1px solid #d1d5db;
        color: #6b7280;
        font-size: 9px;
        text-align: center;
    }


    .aviso {
        margin-top: 20px;
        background: #eff6ff;
        padding: 12px;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
    }

</style>

</head>

<body>


<div class="encabezado">

    <h1>
        Comprobante de postulación
    </h1>

    <p>
        Becas Estudiantiles
    </p>

    <div class="fecha">
        Fecha de envío:
        ' .
    esc($fechaPostulacion) .
    '
    </div>

</div>


<div class="titulo-seccion">
    Identificación del postulante
</div>


<table>

    <tr>

        <td class="etiqueta">
            Nombres
        </td>

        <td>
            ' .
    esc($datos["nombres"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Apellido paterno
        </td>

        <td>
            ' .
    esc($datos["apellido_paterno"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Apellido materno
        </td>

        <td>
            ' .
    esc($datos["apellido_materno"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            RUT
        </td>

        <td>
            ' .
    esc($datos["rut"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Fecha de nacimiento
        </td>

        <td>
            ' .
    esc(mostrarFecha($datos["fecha_nacimiento"])) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Dirección
        </td>

        <td>
            ' .
    esc($datos["direccion"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Teléfono
        </td>

        <td>
            ' .
    esc($datos["telefono"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Correo electrónico
        </td>

        <td>
            ' .
    esc($datos["correo_electronico"]) .
    '
        </td>

    </tr>

</table>


<div class="titulo-seccion">
    Antecedentes educacionales
</div>


<table>

    <tr>

        <td class="etiqueta">
            Carrera
        </td>

        <td>
            ' .
    esc($datos["carrera"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Establecimiento
        </td>

        <td>
            ' .
    esc($datos["nombre_establecimiento"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Región
        </td>

        <td>
            ' .
    esc($datos["region"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Comuna
        </td>

        <td>
            ' .
    esc($datos["comuna"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Gratuidad
        </td>

        <td>
            ' .
    esc($gratuidad) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Promedio último semestre
        </td>

        <td>
            ' .
    esc(number_format((float) $datos["promedio_ultimo_semestre"], 2, ",", ".")) .
    '
        </td>

    </tr>

</table>


<div class="titulo-seccion">
    Datos bancarios
</div>


<table>

    <tr>

        <td class="etiqueta">
            Banco
        </td>

        <td>
            ' .
    esc($banco) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Tipo de cuenta
        </td>

        <td>
            ' .
    esc($datos["tipo_cuenta_nombre"]) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Número de cuenta
        </td>

        <td>
            ' .
    esc($datos["numero_cuenta"]) .
    '
        </td>

    </tr>

</table>


<div class="titulo-seccion">
    Documentos
</div>


<table class="documentos">

    <tr>

        <td class="etiqueta">
            Carnet de identidad
        </td>

        <td class="adjuntado">
            ' .
    esc($estadoCarnet) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Alumno regular
        </td>

        <td class="adjuntado">
            ' .
    esc($estadoAlumno) .
    '
        </td>

    </tr>


    <tr>

        <td class="etiqueta">
            Certificado de notas
        </td>

        <td class="adjuntado">
            ' .
    esc($estadoNotas) .
    '
        </td>

    </tr>

<tr><td class="etiqueta">Registro Social de Hogares</td><td class="adjuntado">' .
    esc($estadoRegistroSocial) .
    '</td></tr>
<tr><td class="etiqueta">Acreditación de gratuidad</td><td class="adjuntado">' .
    esc($estadoGratuidadDocumento) .
    '</td></tr></table>


<div class="aviso">

    Este documento acredita que la postulación fue
    recibida correctamente por la plataforma.
    El resultado de la beca deberá consultarse
    posteriormente desde la sección Resultado.

</div>


<div class="pie">

    © <?= date("Y") ?> Becas Estudiantiles

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

$dompdf->stream("comprobante_postulacion.pdf", [
    "Attachment" => $descargar,
]);

exit();
