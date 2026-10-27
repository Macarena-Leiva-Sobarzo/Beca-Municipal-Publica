<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    !in_array($_SESSION["rol"], ["administrador", "superior"], true)
) {
    http_response_code(403);

    mostrarAvisoAccesoBecas("Acceso denegado.");
}

$documentoId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$documentoId) {
    http_response_code(400);

    mostrarAvisoBecas("Documento no válido.");
}

$stmt = $conn->prepare(
    "
        SELECT
            nombre_archivo_original,
            ruta_archivo
        FROM documentos
        WHERE id = ?
        LIMIT 1
        ",
);

$stmt->bind_param("i", $documentoId);

$stmt->execute();

$resultado = $stmt->get_result();

$documento = $resultado->fetch_assoc();

$stmt->close();

if (!$documento) {
    http_response_code(404);

    mostrarAvisoBecas("Documento no encontrado.");
}

$rutaRelativa = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $documento["ruta_archivo"]);

$rutaFisica = dirname(__DIR__) . DIRECTORY_SEPARATOR . $rutaRelativa;

$baseDocumentos = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . "documentos");

$archivoReal = realpath($rutaFisica);

if (
    $baseDocumentos === false ||
    $archivoReal === false ||
    !str_starts_with($archivoReal, $baseDocumentos) ||
    !is_file($archivoReal)
) {
    http_response_code(404);

    mostrarAvisoBecas("Archivo no disponible.");
}

$nombreDescarga = basename($documento["nombre_archivo_original"]);

header("Content-Type: application/pdf");

header("Content-Disposition: attachment; filename=\"" . $nombreDescarga . "\"");

header("Content-Length: " . filesize($archivoReal));

header("X-Content-Type-Options: nosniff");

readfile($archivoReal);

exit();
