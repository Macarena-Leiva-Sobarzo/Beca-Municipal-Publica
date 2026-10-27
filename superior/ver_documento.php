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
            ruta_archivo,
            tipo_mime

        FROM documentos

        WHERE id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    http_response_code(500);

    mostrarAvisoBecas("No fue posible consultar el documento.");
}

$stmt->bind_param("i", $documentoId);

$stmt->execute();

$documento = $stmt->get_result()->fetch_assoc();

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

header("Content-Type: application/pdf");

header(
    "Content-Disposition: inline; filename=\"" .
        basename($documento["nombre_archivo_original"]) .
        "\"",
);

header("Content-Length: " . filesize($archivoReal));

header("X-Content-Type-Options: nosniff");

readfile($archivoReal);

exit();
