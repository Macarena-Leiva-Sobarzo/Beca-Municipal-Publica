<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

if (!isset($_SESSION["usuario_id"]) || !isset($_SESSION["rol"]) || $_SESSION["rol"] !== "usuario") {
    header("Location: ../auth/login.php");

    exit();
}

$usuarioId = (int) $_SESSION["usuario_id"];

$stmt = $conn->prepare(
    "
        SELECT
            pb.instrucciones_nombre_original,
            pb.instrucciones_ruta
        FROM postulaciones p
        INNER JOIN procesos_beca pb
            ON pb.id = p.proceso_id
        WHERE p.usuario_id = ?
        AND p.estado = 'beneficiado'
        AND pb.activo = 1
        ORDER BY p.id DESC
        LIMIT 1
        ",
);

if (!$stmt) {
    http_response_code(500);

    mostrarAvisoBecas("No fue posible consultar las instrucciones.");
}

$stmt->bind_param("i", $usuarioId);

$stmt->execute();

$resultado = $stmt->get_result();

$datos = $resultado->fetch_assoc();

$stmt->close();

if (!$datos || empty($datos["instrucciones_ruta"])) {
    http_response_code(404);

    mostrarAvisoBecas("Actualmente no existen instrucciones de rendición disponibles.");
}

$rutaFisica =
    dirname(__DIR__) .
    DIRECTORY_SEPARATOR .
    str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $datos["instrucciones_ruta"]);

$rutaReal = realpath($rutaFisica);

$directorioDocumentos = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . "documentos");

if (
    $rutaReal === false ||
    $directorioDocumentos === false ||
    !is_file($rutaReal) ||
    !str_starts_with($rutaReal, $directorioDocumentos . DIRECTORY_SEPARATOR)
) {
    http_response_code(404);

    mostrarAvisoBecas("El archivo de instrucciones no está disponible.");
}

$nombreOriginal = trim($datos["instrucciones_nombre_original"] ?? "");

if ($nombreOriginal === "") {
    $nombreOriginal = "instrucciones_rendicion.pdf";
}

$nombreOriginal = basename($nombreOriginal);

$nombreCabecera = preg_replace("/[^A-Za-z0-9._-]/", "_", $nombreOriginal);

if (!$nombreCabecera || strtolower(pathinfo($nombreCabecera, PATHINFO_EXTENSION)) !== "pdf") {
    $nombreCabecera = "instrucciones_rendicion.pdf";
}

header("Content-Type: application/pdf");

header(
    "Content-Disposition: " .
        (isset($_GET["descargar"]) ? "attachment" : "inline") .
        '; filename="' .
        $nombreCabecera .
        '"',
);

header("Content-Length: " . filesize($rutaReal));

header("X-Content-Type-Options: nosniff");

header("Cache-Control: private, max-age=0, must-revalidate");

readfile($rutaReal);

exit();
