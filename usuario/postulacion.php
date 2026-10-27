<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

date_default_timezone_set("America/Santiago");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");

    exit();
}

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "usuario") {
    header("Location: ../index.php");

    exit();
}

$usuario_id = (int) $_SESSION["usuario_id"];

$sql = "
    SELECT
        id,
        proceso_id,
        estado,
        fecha_postulacion
    FROM postulaciones
    WHERE usuario_id = ?
    ORDER BY id DESC
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar la postulación.");
}

$stmt->bind_param("i", $usuario_id);

$stmt->execute();

$resultado = $stmt->get_result();

$postulacion = $resultado->fetch_assoc();

$stmt->close();

$sqlProceso = "
    SELECT
        id,
        nombre,
        fecha_inicio,
        fecha_cierre
    FROM procesos_beca
    WHERE activo = 1
    ORDER BY id DESC
    LIMIT 1
";

$resultadoProceso = $conn->query($sqlProceso);

$proceso = $resultadoProceso ? $resultadoProceso->fetch_assoc() : null;

// La postulación de otro proceso no impide postular en un nuevo año.
if ($proceso && $postulacion && (int) $postulacion["proceso_id"] !== (int) $proceso["id"]) {
    $postulacion = null;
}
require_once __DIR__ . "/../config/restriccion_postulacion.php";
$restriccionPostulacion = $proceso ? restriccionPostulacion($conn, $usuario_id, $proceso) : null;

$estadoPeriodo = "sin_configurar";

$fechaInicio = null;

$fechaCierre = null;

$zonaHoraria = new DateTimeZone("America/Santiago");

$ahora = new DateTime("now", $zonaHoraria);

if ($proceso) {
    if (!empty($proceso["fecha_inicio"])) {
        $fechaInicio = DateTime::createFromFormat(
            "Y-m-d H:i:s",
            $proceso["fecha_inicio"],
            $zonaHoraria,
        );
    }

    if (!empty($proceso["fecha_cierre"])) {
        $fechaCierre = DateTime::createFromFormat(
            "Y-m-d H:i:s",
            $proceso["fecha_cierre"],
            $zonaHoraria,
        );
    }

    if ($fechaInicio instanceof DateTime && $fechaCierre instanceof DateTime) {
        if ($ahora < $fechaInicio) {
            $estadoPeriodo = "no_iniciado";
        } elseif ($ahora > $fechaCierre) {
            $estadoPeriodo = "cerrado";
        } else {
            $estadoPeriodo = "abierto";
        }
    }
}

$mensajeAnioProceso = $proceso ? mensajeAnioProcesoBeca($proceso, "fecha_inicio") : null;
if ($mensajeAnioProceso) {
    $estadoPeriodo = "cerrado";
}

require_once __DIR__ . "/../vistas/usuario/postulacion.php";
