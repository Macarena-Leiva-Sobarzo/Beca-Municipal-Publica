<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("La configuración está reservada al superior.");
}
$permiso = $conn->prepare("SELECT id FROM usuarios WHERE id=? AND rol='superior' AND activo=1");
$permiso->bind_param("i", $_SESSION["usuario_id"]);
$permiso->execute();
if (!$permiso->get_result()->fetch_assoc()) {
    http_response_code(403);
    mostrarAvisoAccesoBecas("La cuenta no tiene permisos vigentes.");
}
$permiso->close();

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

if (($_SESSION["rol"] ?? "") === "superior") {
    require __DIR__ . "/gestion_usuarios.php";
}

$procesoSolicitado = filter_input(INPUT_GET, "proceso_id", FILTER_VALIDATE_INT);
$filtroProceso = $procesoSolicitado ? "id = " . (int) $procesoSolicitado : "activo = 1";
$procesosDisponibles = $conn
    ->query("SELECT id,nombre,activo FROM procesos_beca ORDER BY id DESC")
    ->fetch_all(MYSQLI_ASSOC);
$sql = "
    SELECT
        id,
        nombre,
        descripcion,
        instrucciones_nombre_original,
        instrucciones_nombre_archivo,
        instrucciones_ruta,
        fecha_inicio,
        fecha_cierre,
        fecha_resultados,
        fecha_resultados_rendicion,
        fecha_inicio_rendicion,
        fecha_cierre_rendicion,
        activo
    FROM procesos_beca
    WHERE $filtroProceso
    ORDER BY id DESC
    LIMIT 1
";

$resultadoProceso = $conn->query($sql);

$proceso = $resultadoProceso ? $resultadoProceso->fetch_assoc() : null;

$requisitos = null;

if ($proceso) {
    $stmt = $conn->prepare(
        "
            SELECT
                id,
                titulo,
                descripcion,
                obligatorio,
                orden,
                activo
            FROM requisitos_beca
            WHERE proceso_id = ?
            ORDER BY orden ASC, id ASC
            ",
    );

    if ($stmt) {
        $stmt->bind_param("i", $proceso["id"]);

        $stmt->execute();

        $requisitos = $stmt->get_result();
    }
}

$mensajeExito = $_SESSION["admin_exito"] ?? null;

$mensajeError = $_SESSION["admin_error"] ?? null;

unset($_SESSION["admin_exito"], $_SESSION["admin_error"]);

function fechaInput(?string $fecha): string
{
    if (empty($fecha)) {
        return "";
    }

    $zona = new DateTimeZone("America/Santiago");

    $objeto = DateTime::createFromFormat("Y-m-d H:i:s", $fecha, $zona);

    if (!$objeto) {
        return "";
    }

    return $objeto->format("Y-m-d\TH:i");
}

require_once __DIR__ . "/../vistas/administrador/configuracion.php";
