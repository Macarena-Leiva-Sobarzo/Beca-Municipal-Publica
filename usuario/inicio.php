<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";

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

$procesoSoloRendicion = procesoSoloRendicionActivo($conn);
if ($procesoSoloRendicion && usuarioHabilitadoSoloRendicion($conn, (int) $procesoSoloRendicion["id"], $usuario_id)) {
    require_once __DIR__ . "/../vistas/usuario/inicio_solo_rendicion.php";
    exit();
}

$sql = "
    SELECT

        p.id,
        p.estado,
        p.monto_asignado,
        p.proceso_id,

        pb.fecha_resultados,
        pb.fecha_inicio_rendicion,
        pb.fecha_cierre_rendicion

    FROM postulaciones p

    INNER JOIN procesos_beca pb
        ON pb.id = p.proceso_id

    WHERE p.usuario_id = ?
        AND pb.activo = 1

    ORDER BY p.id DESC

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

$zonaHoraria = new DateTimeZone("America/Santiago");

$ahora = new DateTime("now", $zonaHoraria);

$resultadosDisponibles = false;

$estadoPeriodoRendicion = "sin_configurar";

$fechaResultados = null;

$fechaInicioRendicion = null;

$fechaCierreRendicion = null;

if ($postulacion) {
    if (!empty($postulacion["fecha_resultados"])) {
        $fechaResultados = DateTime::createFromFormat(
            "Y-m-d H:i:s",
            $postulacion["fecha_resultados"],
            $zonaHoraria,
        );

        if ($fechaResultados instanceof DateTime && $ahora >= $fechaResultados) {
            $resultadosDisponibles = true;
        }
    }

    if (
        !empty($postulacion["fecha_inicio_rendicion"]) &&
        !empty($postulacion["fecha_cierre_rendicion"])
    ) {
        $fechaInicioRendicion = DateTime::createFromFormat(
            "Y-m-d H:i:s",
            $postulacion["fecha_inicio_rendicion"],
            $zonaHoraria,
        );

        $fechaCierreRendicion = DateTime::createFromFormat(
            "Y-m-d H:i:s",
            $postulacion["fecha_cierre_rendicion"],
            $zonaHoraria,
        );

        if (
            $fechaInicioRendicion instanceof DateTime &&
            $fechaCierreRendicion instanceof DateTime
        ) {
            if ($ahora < $fechaInicioRendicion) {
                $estadoPeriodoRendicion = "no_iniciado";
            } elseif ($ahora > $fechaCierreRendicion) {
                $estadoPeriodoRendicion = "cerrado";
            } else {
                $estadoPeriodoRendicion = "abierto";
            }
        }
    }
}

$esBeneficiario =
    $resultadosDisponibles && $postulacion && $postulacion["estado"] === "beneficiado";

$rendicion = null;
$rendicionRealizada = false;

if ($postulacion) {
    $stmt = $conn->prepare(
        "
            SELECT
                id,
                estado,
                fecha_envio
            FROM rendiciones
            WHERE postulacion_id = ?
            ORDER BY id DESC
            LIMIT 1
            ",
    );

    if (!$stmt) {
        mostrarAvisoBecas("No fue posible consultar la rendición.");
    }

    $postulacionId = (int) $postulacion["id"];

    $stmt->bind_param("i", $postulacionId);

    $stmt->execute();

    $rendicion = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $rendicionRealizada =
        $rendicion &&
        in_array($rendicion["estado"], ["enviada", "en_revision", "aprobada", "rechazada"], true);
}

require_once __DIR__ . "/../config/reglas_beca.php";
$mensajeAnioRendicion = $postulacion
    ? mensajeAnioProcesoBeca($postulacion, "fecha_inicio_rendicion")
    : null;
if ($mensajeAnioRendicion) {
    $estadoPeriodoRendicion = "cerrado";
}

require_once __DIR__ . "/../vistas/usuario/inicio.php";
