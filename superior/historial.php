<?php
require_once __DIR__ . "/../config/aviso.php";

// Controlador propio del superior: esta ruta no ejecuta controladores de administrador.
require_once __DIR__ . "/../config/sesion.php";
if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso reservado al superior.");
}

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/datos_historial.php";
accesoHistorial($conn);
$filtro = filtrosHistorial($_GET);
$anios = $conn
    ->query(
        "SELECT DISTINCT anio FROM (" .
            origenHistorial("postulaciones") .
            " UNION ALL " .
            origenHistorial("rendiciones") .
            ") h WHERE anio IS NOT NULL ORDER BY anio DESC",
    )
    ->fetch_all(MYSQLI_ASSOC);
$listados = [];
foreach (["postulaciones", "rendiciones"] as $tipo) {
    $listados[$tipo] = consultarHistorial(
        $conn,
        $tipo,
        $filtro,
        max(1, (int) ($_GET["pagina_" . $tipo] ?? 1)),
    );
}
$_SESSION["token_historial"] ??= bin2hex(random_bytes(32));
require __DIR__ . "/../vistas/superior/historial.php";
