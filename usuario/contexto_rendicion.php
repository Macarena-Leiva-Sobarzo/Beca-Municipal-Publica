<?php
require_once __DIR__ . "/../config/aviso.php";
// Autorización y datos de la postulación para el formulario anual de rendición.
require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../config/reglas_beca.php";
if (($_SESSION["rol"] ?? "") !== "usuario" || empty($_SESSION["usuario_id"])) {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Sesión no autorizada.");
}
// Mostrar el bloqueo antes de buscar los datos de una postulación anterior.
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    require_once __DIR__ . "/../config/restriccion_postulacion.php";
    $resultadoProcesoActual = $conn->query(
        "SELECT id, fecha_inicio FROM procesos_beca WHERE activo=1 ORDER BY id DESC LIMIT 1",
    );
    $procesoActual = $resultadoProcesoActual->fetch_assoc();
    $restriccionActual = $procesoActual
        ? restriccionPostulacion($conn, (int) $_SESSION["usuario_id"], $procesoActual)
        : null;
    if ($restriccionActual) {
        $tituloAvisoRendicion = "Rendición pendiente del período anterior";
        $mensajeAvisoRendicion = $restriccionActual;
        return;
    }
}
$stmt = $conn->prepare(
    "SELECT p.*, pb.fecha_resultados, pb.fecha_inicio_rendicion, pb.fecha_cierre_rendicion FROM postulaciones p JOIN procesos_beca pb ON pb.id=p.proceso_id JOIN usuarios u ON u.id=p.usuario_id WHERE p.usuario_id=? AND u.activo=1 AND pb.activo=1 ORDER BY p.id DESC LIMIT 1",
);
$stmt->bind_param("i", $_SESSION["usuario_id"]);
$stmt->execute();
$postulacion = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (
    !$postulacion ||
    $postulacion["estado"] !== "beneficiado" ||
    !resultadoBecaPublicado($postulacion)
) {
    header("Location: resultado.php");
    exit();
}
$postulacionId = (int) $postulacion["id"];
$mensajeAnioProceso = mensajeAnioProcesoBeca($postulacion, "fecha_inicio_rendicion");
// Tanto la verificación AJAX como el envío final deben respetar el mismo plazo.
if ($_SERVER["REQUEST_METHOD"] === "POST" && !puedeRendirBeca($postulacion)) {
    $mensajePlazo =
        $mensajeAnioProceso ?? "Fuera de plazo: el período de rendición no está abierto.";
    if (isset($_POST["accion"])) {
        http_response_code(403);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["ok" => false, "mensaje" => $mensajePlazo], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $_SESSION["rendicion_error"] = $mensajePlazo;
    header("Location: rendicion.php");
    exit();
}
$stmt = $conn->prepare(
    "SELECT dp.*, de.carrera, de.nombre_establecimiento, de.region, de.comuna, de.tiene_gratuidad, de.promedio_ultimo_semestre FROM datos_personales dp JOIN datos_educacionales de ON de.postulacion_id=dp.postulacion_id WHERE dp.postulacion_id=?",
);
$stmt->bind_param("i", $postulacionId);
$stmt->execute();
$datosUsuario = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$datosUsuario) {
    http_response_code(409);
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $tituloAvisoRendicion = "Rendición no disponible";
        $mensajeAvisoRendicion =
            $mensajeAnioProceso ??
            "No se encontraron los datos de la postulación. Si necesitas ayuda, comunícate con la municipalidad.";
        return;
    }
    mostrarAvisoBecas("No se encontraron los datos de la postulación.");
}
$rutSesion = $datosUsuario["rut"];
