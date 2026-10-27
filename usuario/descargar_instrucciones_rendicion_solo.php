<?php

/**
 * Valida la sesión y la ruta del documento antes de descargar las instrucciones.
 */

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";
require_once __DIR__ . "/../config/documentos_compartidos.php";

/** @var mysqli $conn */

if (
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "usuario" ||
    !isset($_SESSION["acceso_rut_id"]) ||
    !isset($_SESSION["rut"])
) {
    header("Location: ../index.php");
    exit();
}

$accesoRutId = (int) $_SESSION["acceso_rut_id"];

$procesoSolo = procesoSoloRendicionActivo($conn);
if (!$procesoSolo || !usuarioHabilitadoSoloRendicion($conn, (int) $procesoSolo["id"], $accesoRutId)) {
    http_response_code(403);
    exit("Acceso no autorizado.");
}

if ($accesoRutId <= 0) {
    http_response_code(403);
    exit("Acceso no autorizado.");
}

$stmt = $conn->prepare(
    "
        SELECT proceso_id
        FROM rendiciones
        WHERE acceso_rut_id = ?
        ORDER BY id DESC
        LIMIT 1
        ",
);

if (!$stmt) {
    http_response_code(500);
    exit("No fue posible consultar la rendición.");
}

$stmt->bind_param("i", $accesoRutId);

$stmt->execute();

$filaRendicion = $stmt->get_result()->fetch_assoc();

$stmt->close();

$procesoId = (int) ($filaRendicion["proceso_id"] ?? 0);

if ($procesoId <= 0) {
    $resultadoProceso = $conn->query(
        "
            SELECT id
            FROM procesos_beca
            WHERE activo = 1
            ORDER BY id DESC
            LIMIT 1
            ",
    );

    if ($resultadoProceso) {
        $filaProcesoActivo = $resultadoProceso->fetch_assoc();

        $procesoId = (int) ($filaProcesoActivo["id"] ?? 0);
    }
}

if ($procesoId <= 0) {
    http_response_code(404);
    exit("No existe un proceso de beca disponible.");
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
    exit("No fue posible consultar las instrucciones.");
}

$stmt->bind_param("i", $procesoId);

$stmt->execute();

$instrucciones = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$instrucciones || empty($instrucciones["instrucciones_ruta"])) {
    http_response_code(404);
    exit("Las instrucciones todavía no han sido publicadas.");
}

// Resolver el mismo documento compartido que utiliza el perfil del superior.
$rutaFisica = rutaDocumentoCompartido(
    (string) $instrucciones["instrucciones_ruta"],
);
if ($rutaFisica === null) {
    http_response_code(404);
    exit("El PDF de instrucciones no se encuentra disponible.");
}
$fp = fopen($rutaFisica, "rb");

if ($fp === false) {
    http_response_code(500);
    exit("No fue posible abrir el PDF.");
}

$cabecera = fread($fp, 5);

fclose($fp);

if ($cabecera !== "%PDF-") {
    http_response_code(415);
    exit("El archivo de instrucciones no es un PDF válido.");
}

$nombreDescarga = trim(
    (string) ($instrucciones["instrucciones_nombre_original"] ?? ""),
);

if ($nombreDescarga === "") {
    $nombreDescarga = "instrucciones_rendicion.pdf";
}

$nombreDescarga = basename($nombreDescarga);

if (strtolower(pathinfo($nombreDescarga, PATHINFO_EXTENSION)) !== "pdf") {
    $nombreDescarga .= ".pdf";
}

header("Content-Type: application/pdf");

header("Content-Length: " . filesize($rutaFisica));

header("X-Content-Type-Options: nosniff");

header(
    'Content-Disposition: attachment; filename="' .
        rawurlencode($nombreDescarga) .
        '"',
);

header("Cache-Control: private, no-store, no-cache, must-revalidate");

header("Pragma: no-cache");

readfile($rutaFisica);

exit();
