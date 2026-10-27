<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

date_default_timezone_set("America/Santiago");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["administrador", "superior"], true)) {
    header("Location: ../index.php");
    exit();
}

$postulacionId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$postulacionId) {
    header("Location: postulaciones.php");
    exit();
}

$sql = "
    SELECT

        p.id,
        p.folio,
        p.estado,
        p.monto_asignado,
        p.monto_primer_semestre,
        p.monto_segundo_semestre,
        p.observacion_admin,
        p.fecha_postulacion,
        p.fecha_resultado,
        p.fecha_revision,
        p.administrador_revision_id,
        TRIM(CONCAT_WS(' ', revisor.nombres, revisor.apellido_paterno, revisor.apellido_materno)) AS nombre_revisor,

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

    LEFT JOIN usuarios revisor
        ON revisor.id = p.administrador_revision_id

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

    WHERE p.id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar la postulación.");
}

$stmt->bind_param("i", $postulacionId);

$stmt->execute();

$postulacion = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$postulacion) {
    header("Location: postulaciones.php");
    exit();
}

$stmt = $conn->prepare(
    "
        SELECT

            id,
            tipo_documento,
            nombre_archivo_original,
            estado_validacion,
            observacion,
            fecha_subida,
            fecha_validacion

        FROM documentos

        WHERE postulacion_id = ?

        ORDER BY id
        ",
);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar los documentos.");
}

$stmt->bind_param("i", $postulacionId);

$stmt->execute();

$resultadoDocumentos = $stmt->get_result();

$documentos = [];

while ($fila = $resultadoDocumentos->fetch_assoc()) {
    $documentos[$fila["tipo_documento"]] = $fila;
}

$stmt->close();

$banco = $postulacion["banco_nombre"] ?: $postulacion["banco_otro"];

if (!$banco) {
    $banco = "-";
}

$mensajeExito = $_SESSION["admin_exito"] ?? null;

$mensajeError = $_SESSION["admin_error"] ?? null;

unset($_SESSION["admin_exito"], $_SESSION["admin_error"]);

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? "", ENT_QUOTES, "UTF-8");
}

function fechaHoraRevision(?string $valor): string
{
    if (empty($valor)) {
        return "-";
    }

    $timestamp = strtotime($valor);

    return $timestamp === false ? "-" : date("d/m/Y H:i:s", $timestamp);
}

function nombreDocumento(string $tipo): string
{
    return match ($tipo) {
        "registro_social" => "Registro Social de Hogares",
        "acreditacion_gratuidad" => "Acreditación de gratuidad",
        "carnet" => "Carnet de identidad",

        "alumno_regular" => "Certificado de alumno regular",

        "certificado_notas" => "Concentración de notas",

        default => "Documento",
    };
}

function textoEstado(string $estado): string
{
    return match ($estado) {
        "pendiente" => "Pendiente",

        "en_revision" => "En revisión",

        "beneficiado" => "Beneficiado",

        "no_beneficiado" => "No beneficiado",

        default => $estado,
    };
}

function textoEstadoDocumento(?string $estado): string
{
    return match ($estado) {
        "validado" => "Aceptado",

        "rechazado" => "Rechazado",

        default => "Pendiente",
    };
}

require_once __DIR__ . "/../vistas/administrador/detalle_postulacion.php";
