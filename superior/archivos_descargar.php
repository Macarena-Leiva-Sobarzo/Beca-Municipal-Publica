<?php
require_once __DIR__ . "/../config/aviso.php";

// Controlador propio del superior: esta ruta no ejecuta controladores de administrador.
require_once __DIR__ . "/../config/sesion.php";
if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso reservado al superior.");
}

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    !in_array($_SESSION["rol"], ["administrador", "superior"], true)
) {
    header("Location: ../auth/login.php");
    exit();
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["token_archivos_descargar"])) {
    $_SESSION["token_archivos_descargar"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["token_archivos_descargar"];

$mensajeError = $_SESSION["archivo_error"] ?? null;

unset($_SESSION["archivo_error"]);

require_once __DIR__ . "/../config/proceso_activo.php";
$procesoDescargaId = idProcesoActivoBeca($conn);
$totalPostulantes = 0;
$totalRendiciones = 0;

// totales

$resultado = $conn->query(
    "
        SELECT COUNT(*) AS total
        FROM postulaciones WHERE proceso_id=$procesoDescargaId ",
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();

    $totalPostulantes = (int) ($fila["total"] ?? 0);
}

$resultado = $conn->query(
    "
        SELECT COUNT(*) AS total
        FROM rendiciones r LEFT JOIN postulaciones p ON p.id=r.postulacion_id
        WHERE COALESCE(r.proceso_id,p.proceso_id)=$procesoDescargaId AND r.estado IN (
            'enviada',
            'en_revision',
            'aprobada',
            'rechazada'
        )
        ",
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();

    $totalRendiciones = (int) ($fila["total"] ?? 0);
}

$rutaCss = __DIR__ . "/../css/estilos.css";

$versionCss = is_file($rutaCss) ? (string) filemtime($rutaCss) : (string) time();

require_once __DIR__ . "/../vistas/superior/archivos_descargar.php";
