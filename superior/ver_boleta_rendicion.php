<?php
require_once __DIR__ . "/../config/aviso.php";
require_once __DIR__ . "/../config/documentos_compartidos.php";

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

    mostrarAvisoAccesoBecas("Acceso no autorizado.");
}

$gastoId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$gastoId) {
    http_response_code(400);

    mostrarAvisoBecas("Boleta no válida.");
}

$modo = trim((string) ($_GET["modo"] ?? "ver"));

$descargar = $modo === "descargar";

$stmt = $conn->prepare(
    "
        SELECT

            nombre_archivo_original,
            ruta_archivo

        FROM rendicion_gastos

        WHERE id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    http_response_code(500);

    mostrarAvisoBecas("No fue posible consultar el archivo.");
}

$stmt->bind_param("i", $gastoId);

$stmt->execute();

$archivo = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$archivo) {
    http_response_code(404);

    mostrarAvisoBecas("La boleta no existe.");
}

$rutaRelativa = str_replace(["\\", "/"], DIRECTORY_SEPARATOR, (string) $archivo["ruta_archivo"]);

$rutaFisica = rutaDocumentoCompartido(str_replace(DIRECTORY_SEPARATOR, "/", $rutaRelativa)) ?? "";

if (!is_file($rutaFisica)) {
    http_response_code(404);

    mostrarAvisoBecas("El archivo PDF no fue encontrado.");
}

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($rutaFisica);

if ($mime !== "application/pdf") {
    http_response_code(415);

    mostrarAvisoBecas("El archivo no corresponde a un PDF válido.");
}

$nombre = basename((string) ($archivo["nombre_archivo_original"] ?: "boleta.pdf"));

$nombre = str_replace(["\r", "\n", '"'], "", $nombre);

if (!str_ends_with(strtolower($nombre), ".pdf")) {
    $nombre .= ".pdf";
}

header("Content-Type: application/pdf");

header(
    "Content-Disposition: " .
        ($descargar ? "attachment" : "inline") .
        '; filename="' .
        $nombre .
        '"',
);

header("Content-Length: " . filesize($rutaFisica));

header("X-Content-Type-Options: nosniff");

header("Cache-Control: private, max-age=0, must-revalidate");

readfile($rutaFisica);

exit();
