<?php
require_once __DIR__ . "/../config/aviso.php";

// Controlador propio del superior: esta ruta no ejecuta controladores de administrador.
require_once __DIR__ . "/../config/sesion.php";
if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso reservado al superior.");
}

/** Guarda la decisión de la postulación, observaciones por documento y montos de la beca. La publicación y el aviso por correo respetan la fecha configurada. */

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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: postulaciones.php");

    exit();
}

$administradorId = (int) $_SESSION["usuario_id"];

$postulacionId = filter_input(INPUT_POST, "postulacion_id", FILTER_VALIDATE_INT);

$decision = trim($_POST["decision"] ?? "");

$observacion = trim($_POST["observacion"] ?? "");

$documentos = $_POST["documentos"] ?? [];

function volverDetalle(int $postulacionId, string $mensaje): void
{
    $_SESSION["admin_error"] = $mensaje;

    header("Location: detalle_postulacion.php?id=" . $postulacionId);

    exit();
}

if (!$postulacionId) {
    $_SESSION["admin_error"] = "La postulación no es válida.";

    header("Location: postulaciones.php");

    exit();
}

if ($decision === "no_beneficiado" && $observacion === "") {
    volverDetalle(
        $postulacionId,
        "Debes escribir una observación general que explique por qué el postulante no es beneficiario.",
    );
}

$decisionesPermitidas = ["beneficiado", "no_beneficiado"];

if (!in_array($decision, $decisionesPermitidas, true)) {
    volverDetalle($postulacionId, "Debes seleccionar una decisión válida.");
}

if (mb_strlen($observacion, "UTF-8") > 2000) {
    volverDetalle($postulacionId, "La observación general no puede superar los 2000 caracteres.");
}

if (!is_array($documentos)) {
    volverDetalle($postulacionId, "La información de los documentos no es válida.");
}

$tiposDocumentos = [
    "carnet",
    "alumno_regular",
    "certificado_notas",
    "registro_social",
    "acreditacion_gratuidad",
];

$documentosPreparados = [];

foreach ($tiposDocumentos as $tipo) {
    if (!isset($documentos[$tipo]) || !is_array($documentos[$tipo])) {
        volverDetalle(
            $postulacionId,
            "Debes revisar todos los documentos antes de guardar la evaluación.",
        );
    }

    $datosDocumento = $documentos[$tipo];

    $documentoId = filter_var($datosDocumento["id"] ?? null, FILTER_VALIDATE_INT);

    $estadoDocumento = trim($datosDocumento["estado"] ?? "");

    $observacionDocumento = trim($datosDocumento["observacion"] ?? "");

    if (!$documentoId) {
        volverDetalle($postulacionId, "Uno de los documentos no es válido.");
    }

    if (!in_array($estadoDocumento, ["validado", "rechazado"], true)) {
        volverDetalle($postulacionId, "Debes aceptar o rechazar cada documento.");
    }

    if ($estadoDocumento === "rechazado" && $observacionDocumento === "") {
        volverDetalle(
            $postulacionId,
            "Debes indicar el motivo de rechazo de cada documento rechazado.",
        );
    }

    if (mb_strlen($observacionDocumento, "UTF-8") > 2000) {
        volverDetalle($postulacionId, "La observación de un documento supera los 2000 caracteres.");
    }

    $documentosPreparados[$tipo] = [
        "id" => (int) $documentoId,

        "estado" => $estadoDocumento,

        "observacion" => $observacionDocumento,
    ];
}

if ($decision === "beneficiado") {
    foreach ($documentosPreparados as $documento) {
        if ($documento["estado"] !== "validado") {
            volverDetalle(
                $postulacionId,
                "No puedes marcar la postulación como beneficiada mientras exista un documento rechazado.",
            );
        }
    }
}

$montoTotal = null;

$montoPrimer = null;

$montoSegundo = null;

if ($decision === "beneficiado") {
    $montoTotalRecibido = trim($_POST["monto_asignado"] ?? "");

    $montoPrimerRecibido = trim($_POST["monto_primer_semestre"] ?? "");

    $montoSegundoRecibido = trim($_POST["monto_segundo_semestre"] ?? "");

    if (
        $montoTotalRecibido === "" ||
        $montoPrimerRecibido === "" ||
        $montoSegundoRecibido === "" ||
        !is_numeric($montoTotalRecibido) ||
        !is_numeric($montoPrimerRecibido) ||
        !is_numeric($montoSegundoRecibido)
    ) {
        volverDetalle(
            $postulacionId,
            "Debes ingresar correctamente el monto total y los montos de ambos semestres.",
        );
    }

    $montoTotal = (float) $montoTotalRecibido;

    $montoPrimer = (float) $montoPrimerRecibido;

    $montoSegundo = (float) $montoSegundoRecibido;

    if ($montoTotal <= 0 || $montoPrimer < 0 || $montoSegundo < 0) {
        volverDetalle($postulacionId, "Los montos ingresados no son válidos.");
    }

    if (abs($montoPrimer + $montoSegundo - $montoTotal) > 0.01) {
        volverDetalle(
            $postulacionId,
            "La suma del primer y segundo semestre debe coincidir con el monto total de la beca.",
        );
    }
}

$stmt = $conn->prepare(
    "
        SELECT id

        FROM postulaciones

        WHERE id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    volverDetalle($postulacionId, "No fue posible comprobar la postulación.");
}

$stmt->bind_param("i", $postulacionId);

$stmt->execute();

$existePostulacion = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$existePostulacion) {
    $_SESSION["admin_error"] = "La postulación no existe.";

    header("Location: postulaciones.php");

    exit();
}

foreach ($documentosPreparados as $tipo => $documento) {
    $stmt = $conn->prepare(
        "
            SELECT

                id,
                tipo_documento

            FROM documentos

            WHERE id = ?
            AND postulacion_id = ?
            AND tipo_documento = ?

            LIMIT 1
            ",
    );

    if (!$stmt) {
        volverDetalle($postulacionId, "No fue posible comprobar uno de los documentos.");
    }

    $stmt->bind_param("iis", $documento["id"], $postulacionId, $tipo);

    $stmt->execute();

    $documentoReal = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$documentoReal) {
        volverDetalle($postulacionId, "Uno de los documentos no pertenece a esta postulación.");
    }
}

$conn->begin_transaction();

try {
    foreach ($documentosPreparados as $documento) {
        $stmt = $conn->prepare(
            "
                UPDATE documentos

                SET

                    estado_validacion = ?,
                    observacion = ?,
                    fecha_validacion = NOW()

                WHERE id = ?
                AND postulacion_id = ?
                ",
        );

        if (!$stmt) {
            throw new RuntimeException("No fue posible preparar la actualización de documentos.");
        }

        $stmt->bind_param(
            "ssii",
            $documento["estado"],
            $documento["observacion"],
            $documento["id"],
            $postulacionId,
        );

        if (!$stmt->execute()) {
            throw new RuntimeException("No fue posible actualizar uno de los documentos.");
        }

        $stmt->close();
    }

    if ($decision === "beneficiado") {
        $stmt = $conn->prepare(
            "
                UPDATE postulaciones

                SET

                    estado = 'beneficiado',

                    monto_asignado = ?,
                    monto_primer_semestre = ?,
                    monto_segundo_semestre = ?,

                    observacion_admin = ?,

                    administrador_revision_id = ?,

                    fecha_revision = NOW(),
                    fecha_resultado = NOW()

                WHERE id = ?
                ",
        );

        if (!$stmt) {
            throw new RuntimeException("No fue posible preparar la evaluación.");
        }

        $stmt->bind_param(
            "dddsii",
            $montoTotal,
            $montoPrimer,
            $montoSegundo,
            $observacion,
            $administradorId,
            $postulacionId,
        );
    } else {
        $stmt = $conn->prepare(
            "
                UPDATE postulaciones

                SET

                    estado = 'no_beneficiado',

                    monto_asignado = NULL,
                    monto_primer_semestre = NULL,
                    monto_segundo_semestre = NULL,

                    observacion_admin = ?,

                    administrador_revision_id = ?,

                    fecha_revision = NOW(),
                    fecha_resultado = NOW()

                WHERE id = ?
                ",
        );

        if (!$stmt) {
            throw new RuntimeException("No fue posible preparar la evaluación.");
        }

        $stmt->bind_param("sii", $observacion, $administradorId, $postulacionId);
    }

    if (!$stmt->execute()) {
        throw new RuntimeException("No fue posible guardar la evaluación.");
    }

    $stmt->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();

    error_log("Error evaluación postulación " . $postulacionId . ": " . $e->getMessage());

    volverDetalle(
        $postulacionId,
        "No fue posible guardar la evaluación. Ningún cambio fue aplicado.",
    );
}

$_SESSION["admin_exito"] =
    $decision === "beneficiado"
        ? "La evaluación fue guardada correctamente. La postulación fue aprobada."
        : "La evaluación fue guardada correctamente. La postulación fue rechazada.";

// La evaluación ya está confirmada: un fallo del correo no revierte la decisión.
require_once __DIR__ . "/../correo/notificar_resultado_postulacion.php";
$resultadoCorreo = notificarResultadoPostulacion($conn, (int) $postulacionId);
$_SESSION["admin_exito"] .= match ($resultadoCorreo) {
    "enviado" => " El resultado fue enviado al correo del postulante.",
    "ya_enviado" => " El resultado ya había sido enviado al correo del postulante.",
    "pendiente"
        => " El correo se enviará mediante la tarea programada cuando se publique el resultado.",
    "en_proceso" => " El envío del correo está en curso.",
    "revisar_entrega"
        => " Debe comprobarse la entrega del correo en el registro antes de reintentarlo.",
    default => " No se pudo enviar el correo del resultado. Revisa el registro de errores.",
};

header("Location: postulaciones.php");

exit();
