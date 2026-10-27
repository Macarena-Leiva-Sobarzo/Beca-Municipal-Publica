<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

/** @var mysqli $conn */

date_default_timezone_set("America/Santiago");

$zonaHoraria = new DateTimeZone("America/Santiago");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");

    exit();
}

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "usuario") {
    header("Location: ../index.php");

    exit();
}

$usuario_id = (int) $_SESSION["usuario_id"];

// Usar el mismo proceso activo que la pantalla de postulación.
$resultadoProceso = $conn->query(
    "SELECT id, nombre, fecha_inicio FROM procesos_beca WHERE activo = 1 ORDER BY id DESC LIMIT 1",
);
$proceso = $resultadoProceso ? $resultadoProceso->fetch_assoc() : null;
$procesoId = (int) ($proceso["id"] ?? 0);

$sql = "
    SELECT

        p.id,
        p.proceso_id,
        p.estado,
        p.monto_asignado,

        p.observacion_admin,

        p.fecha_resultado,

        pb.nombre AS proceso_nombre,
        pb.fecha_resultados

    FROM postulaciones p

    INNER JOIN procesos_beca pb
        ON pb.id = p.proceso_id

    WHERE p.usuario_id = ? AND p.proceso_id = ?

    ORDER BY p.id DESC

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar la postulación.");
}

$stmt->bind_param("ii", $usuario_id, $procesoId);

$stmt->execute();

$resultado = $stmt->get_result();

$postulacion = $resultado->fetch_assoc();

$stmt->close();

require_once __DIR__ . "/../config/restriccion_postulacion.php";
$restriccionPostulacion =
    !$postulacion && $proceso ? restriccionPostulacion($conn, $usuario_id, $proceso) : null;

$fechaResultados = null;

$resultadosDisponibles = false;

$fechaResultadosConfigurada = false;

$ahora = new DateTime("now", $zonaHoraria);

if ($postulacion && !empty($postulacion["fecha_resultados"])) {
    $fechaResultados = DateTime::createFromFormat(
        "Y-m-d H:i:s",
        $postulacion["fecha_resultados"],
        $zonaHoraria,
    );

    if ($fechaResultados instanceof DateTime) {
        $fechaResultadosConfigurada = true;

        $resultadosDisponibles = $ahora >= $fechaResultados;
    }
}

$resultadoDefinitivo =
    $postulacion &&
    $resultadosDisponibles &&
    in_array($postulacion["estado"], ["beneficiado", "no_beneficiado"], true);

$documentos = [];

if ($resultadoDefinitivo) {
    $stmt = $conn->prepare(
        "
            SELECT

                tipo_documento,
                estado_validacion,
                observacion,
                fecha_validacion

            FROM documentos

            WHERE postulacion_id = ?

            ORDER BY id
            ",
    );

    if (!$stmt) {
        mostrarAvisoBecas("No fue posible consultar los documentos.");
    }

    $stmt->bind_param("i", $postulacion["id"]);

    $stmt->execute();

    $resultadoDocumentos = $stmt->get_result();

    while ($documento = $resultadoDocumentos->fetch_assoc()) {
        $documentos[$documento["tipo_documento"]] = $documento;
    }

    $stmt->close();
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function nombreDocumentoResultado(string $tipo): string
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

function textoEstadoDocumentoResultado(?string $estado): string
{
    return match ($estado) {
        "validado" => "Aceptado",

        "rechazado" => "Rechazado",

        default => "Pendiente de revisión",
    };
}

function claseEstadoDocumento(?string $estado): string
{
    return match ($estado) {
        "validado" => "doc-aceptado",

        "rechazado" => "doc-rechazado",

        default => "doc-pendiente",
    };
}

function iconoEstadoDocumento(?string $estado): string
{
    return match ($estado) {
        "validado" => "✓",

        "rechazado" => "×",

        default => "…",
    };
}

function mostrarFechaResultado(DateTime|false|null $fecha): string
{
    if (!($fecha instanceof DateTime)) {
        return "Por definir";
    }

    return $fecha->format("d/m/Y H:i");
}

require_once __DIR__ . "/../vistas/usuario/resultado.php";
