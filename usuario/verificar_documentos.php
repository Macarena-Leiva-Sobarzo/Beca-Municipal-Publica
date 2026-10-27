<?php

/** Analiza un documento por solicitud (o un lote compatible). Guarda la revisión en sesión vinculada al hash del PDF y a los datos del postulante; el envío final reutiliza esa revisión. */

require_once __DIR__ . "/../config/sesion.php";

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../validaciones/validar_pdf.php";

require_once __DIR__ . "/../validaciones/verificacion_postulacion.php";

function responder(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    exit();
}

if (!isset($_SESSION["usuario_id"])) {
    responder([
        "ok" => false,
        "mensaje" => "La sesión ha expirado. Inicia sesión nuevamente.",
    ]);
}

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "usuario") {
    responder([
        "ok" => false,
        "mensaje" => "No tienes permisos para realizar esta operación.",
    ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder([
        "ok" => false,
        "mensaje" => "Solicitud no válida.",
    ]);
}

// La comprobación técnica del PDF no requiere completar antes los datos del formulario.
$documentosRequeridos = [
    "carnet" => "Carnet de identidad",

    "alumno_regular" => "Certificado de alumno regular",

    "certificado_notas" => "Concentración de notas",
    "registro_social" => "Registro Social de Hogares",
    "acreditacion_gratuidad" => "Acreditación de gratuidad",
];

if (isset($_POST["documento"])) {
    $campoSolicitado = (string) $_POST["documento"];
    if (!isset($documentosRequeridos[$campoSolicitado])) {
        responder(["ok" => false, "mensaje" => "Documento no válido."]);
    }
    $documentosRequeridos = [$campoSolicitado => $documentosRequeridos[$campoSolicitado]];
}

foreach ($documentosRequeridos as $campo => $nombreDocumento) {
    if (!isset($_FILES[$campo])) {
        responder([
            "ok" => false,

            "mensaje" => "Debes seleccionar " . $nombreDocumento . ".",
        ]);
    }

    $archivo = $_FILES[$campo];

    if ($archivo["error"] !== UPLOAD_ERR_OK) {
        responder([
            "ok" => false,

            "mensaje" => "No fue posible recibir " . $nombreDocumento . ".",
        ]);
    }

    if (!is_uploaded_file($archivo["tmp_name"])) {
        responder([
            "ok" => false,

            "mensaje" => $nombreDocumento . " no fue recibido correctamente.",
        ]);
    }

    if ((int) $archivo["size"] <= 0) {
        responder([
            "ok" => false,

            "mensaje" => $nombreDocumento . " está vacío.",
        ]);
    }

    if ((int) $archivo["size"] > 5 * 1024 * 1024) {
        responder([
            "ok" => false,

            "mensaje" => $nombreDocumento . " supera el tamaño máximo permitido de 5 MB.",
        ]);
    }

    $extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));

    if ($extension !== "pdf") {
        responder([
            "ok" => false,

            "mensaje" => $nombreDocumento . " debe estar en formato PDF.",
        ]);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mime = $finfo->file($archivo["tmp_name"]);

    if ($mime !== "application/pdf") {
        responder([
            "ok" => false,

            "mensaje" => $nombreDocumento . " no corresponde a un archivo PDF válido",
        ]);
    }

    $archivoTemporal = fopen($archivo["tmp_name"], "rb");

    if ($archivoTemporal === false) {
        responder([
            "ok" => false,

            "mensaje" => "No fue posible analizar " . $nombreDocumento . ".",
        ]);
    }

    $cabecera = fread($archivoTemporal, 5);

    fclose($archivoTemporal);

    if ($cabecera !== "%PDF-") {
        responder([
            "ok" => false,

            "mensaje" => $nombreDocumento . " no posee una estructura PDF válida",
        ]);
    }
}

$resultados = [];
foreach ($documentosRequeridos as $campo => $nombreDocumento) {
    $ruta = $_FILES[$campo]["tmp_name"];
    if (!is_uploaded_file($ruta)) {
        responder(["ok" => false, "mensaje" => "La carga del documento no es válida."]);
    }
    try {
        validarArchivoPDF($ruta, $_FILES[$campo]["name"], 5 * 1024 * 1024);
        $resultado = [
            "estado" => "correcto",
            "mensaje" =>
                "PDF recibido con contenido. Los datos del documento serán revisados por el personal municipal.",
        ];
    } catch (Throwable $e) {
        $resultado = [
            "estado" => "incorrecto",
            "mensaje" => $nombreDocumento . ": " . $e->getMessage(),
        ];
    }
    guardarVerificacionPostulacion($campo, $ruta, $_POST, $resultado);
    $resultados[$campo] = $resultado;
}
responder([
    "ok" => true,
    "todos_correctos" =>
        count(array_filter($resultados, fn($r) => $r["estado"] === "correcto")) ===
        count($resultados),
    "documentos" => $resultados,
]);
