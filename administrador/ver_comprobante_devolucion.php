<?php
require_once __DIR__ . "/../config/aviso.php";
require_once __DIR__ . "/../config/documentos_compartidos.php";

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

$rendicionId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$rendicionId) {
    http_response_code(400);

    mostrarAvisoBecas("Rendición no válida.");
}

$modo = trim((string) ($_GET["modo"] ?? "ver"));

$descargar = $modo === "descargar";

$stmt = $conn->prepare(
    "
        SELECT

            devolucion_nombre_original,
            devolucion_ruta,
            numero_comprobante_devolucion

        FROM rendiciones

        WHERE id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    http_response_code(500);

    mostrarAvisoBecas("No fue posible consultar el comprobante.");
}

$stmt->bind_param("i", $rendicionId);

$stmt->execute();

$datos = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$datos || empty($datos["devolucion_ruta"])) {
    http_response_code(404);

    mostrarAvisoBecas("No existe un comprobante de devolución adjunto.");
}

$rutaRelativa = str_replace(["\\", "/"], DIRECTORY_SEPARATOR, (string) $datos["devolucion_ruta"]);

$rutaFisica = rutaDocumentoCompartido(str_replace(DIRECTORY_SEPARATOR, "/", $rutaRelativa)) ?? "";

if (!is_file($rutaFisica)) {
    http_response_code(404);

    mostrarAvisoBecas("El archivo del comprobante no fue encontrado.");
}

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($rutaFisica);

if ($mime !== "application/pdf") {
    http_response_code(415);

    mostrarAvisoBecas("El archivo no corresponde a un PDF válido.");
}

$nombre = basename((string) ($datos["devolucion_nombre_original"] ?? ""));

if ($nombre === "") {
    $numero = trim((string) ($datos["numero_comprobante_devolucion"] ?? ""));

    $nombre =
        $numero !== ""
            ? "comprobante_devolucion_" . preg_replace("/[^A-Za-z0-9_-]/", "_", $numero) . ".pdf"
            : "comprobante_devolucion.pdf";
}

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
