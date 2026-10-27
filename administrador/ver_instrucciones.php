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
    header("Location: ../auth/login.php");

    exit();
}

$procesoId = filter_input(INPUT_GET, "proceso", FILTER_VALIDATE_INT);

if (!$procesoId) {
    http_response_code(400);

    mostrarAvisoBecas("El proceso indicado no es válido.");
}

$stmt = $conn->prepare(
    "
        SELECT

            instrucciones_nombre_original,
            instrucciones_nombre_archivo,
            instrucciones_ruta

        FROM procesos_beca

        WHERE id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    http_response_code(500);

    mostrarAvisoBecas("No fue posible consultar las instrucciones.");
}

$stmt->bind_param("i", $procesoId);

$stmt->execute();

$proceso = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$proceso || empty($proceso["instrucciones_ruta"])) {
    http_response_code(404);

    mostrarAvisoBecas("No existen instrucciones de rendición publicadas para este proceso.");
}

// El PDF puede haber sido subido desde cualquiera de los dos portales.
$rutaReal = rutaDocumentoCompartido((string) $proceso["instrucciones_ruta"]);
if ($rutaReal === null) {
    http_response_code(404);
    mostrarAvisoBecas("El archivo de instrucciones no está disponible.");
}

$extension = strtolower(pathinfo($rutaReal, PATHINFO_EXTENSION));

if ($extension !== "pdf") {
    http_response_code(400);

    mostrarAvisoBecas("El archivo almacenado no corresponde a un PDF.");
}

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($rutaReal);

if ($mime !== "application/pdf") {
    http_response_code(400);

    mostrarAvisoBecas("El archivo almacenado no es un PDF válido.");
}

$nombreOriginal = trim($proceso["instrucciones_nombre_original"] ?? "");

if ($nombreOriginal === "") {
    $nombreOriginal = "instrucciones_rendicion.pdf";
}

$nombreSeguro = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($nombreOriginal));

if (!$nombreSeguro || strtolower(pathinfo($nombreSeguro, PATHINFO_EXTENSION)) !== "pdf") {
    $nombreSeguro = "instrucciones_rendicion.pdf";
}

header("Content-Type: application/pdf");

header('Content-Disposition: inline; filename="' . $nombreSeguro . '"');

header("Content-Length: " . filesize($rutaReal));

header("X-Content-Type-Options: nosniff");

header("Cache-Control: private, max-age=0, must-revalidate");

header("Pragma: public");

readfile($rutaReal);

exit();
