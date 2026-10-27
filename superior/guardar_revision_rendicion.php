<?php
require_once __DIR__ . "/../config/aviso.php";

// Controlador propio del superior: esta ruta no ejecuta controladores de administrador.
require_once __DIR__ . "/../config/sesion.php";
if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso reservado al superior.");
}

/** Persiste la revisión de gastos y devolución. Un documento rechazado determina el rechazo general; el correo se gestiona según la fecha de publicación. */

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

date_default_timezone_set("America/Santiago");

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    !in_array($_SESSION["rol"], ["administrador", "superior"], true)
) {
    header("Location: ../auth/login.php");

    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: rendiciones.php");

    exit();
}

function volverDetalle(int $rendicionId, string $mensaje, bool $exito = false): void
{
    if ($exito) {
        $_SESSION["admin_exito"] = $mensaje;
    } else {
        $_SESSION["admin_error"] = $mensaje;
    }

    header("Location: detalle_rendicion.php?id=" . $rendicionId);

    exit();
}

$rendicionId = filter_input(INPUT_POST, "rendicion_id", FILTER_VALIDATE_INT);

$boletas = $_POST["boletas"] ?? [];

$devolucionRecibida = $_POST["devolucion"] ?? null;

$observacionGeneral = trim((string) ($_POST["observaciones"] ?? ""));

if (!$rendicionId) {
    header("Location: rendiciones.php");

    exit();
}

if (mb_strlen($observacionGeneral, "UTF-8") > 2000) {
    volverDetalle($rendicionId, "La observación general supera el máximo de 2000 caracteres.");
}

$stmt = $conn->prepare(
    "
        SELECT

            id,
            estado,
            monto_devuelto,
            devolucion_ruta

        FROM rendiciones

        WHERE id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    volverDetalle($rendicionId, "No fue posible comprobar la rendición.");
}

$stmt->bind_param("i", $rendicionId);

$stmt->execute();

$rendicion = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$rendicion) {
    volverDetalle($rendicionId, "La rendición seleccionada no existe.");
}

$realizoDevolucion =
    $rendicion["monto_devuelto"] !== null && (float) $rendicion["monto_devuelto"] > 0;

if ($realizoDevolucion && empty($rendicion["devolucion_ruta"])) {
    volverDetalle($rendicionId, "La devolución registrada no posee un comprobante PDF.");
}

$stmt = $conn->prepare(
    "
        SELECT id

        FROM rendicion_gastos

        WHERE rendicion_id = ?

        ORDER BY id
        ",
);

if (!$stmt) {
    volverDetalle($rendicionId, "No fue posible consultar las boletas de la rendición.");
}

$stmt->bind_param("i", $rendicionId);

$stmt->execute();

$resultado = $stmt->get_result();

$idsReales = [];

while ($fila = $resultado->fetch_assoc()) {
    $idsReales[] = (int) $fila["id"];
}

$stmt->close();

if (empty($idsReales)) {
    volverDetalle($rendicionId, "La rendición no contiene boletas para revisar.");
}

if (!is_array($boletas)) {
    volverDetalle($rendicionId, "La información recibida para las boletas no es válida.");
}

$decisiones = [];

foreach ($idsReales as $gastoId) {
    if (!isset($boletas[$gastoId]) || !is_array($boletas[$gastoId])) {
        volverDetalle($rendicionId, "Debes indicar si cada boleta es aceptada o rechazada.");
    }

    $estado = trim((string) ($boletas[$gastoId]["estado"] ?? ""));

    $observacion = trim((string) ($boletas[$gastoId]["observacion"] ?? ""));

    if (!in_array($estado, ["validado", "rechazado"], true)) {
        volverDetalle($rendicionId, "Existe una boleta sin una decisión válida.");
    }

    if (mb_strlen($observacion, "UTF-8") > 1000) {
        volverDetalle(
            $rendicionId,
            "Una de las observaciones supera el máximo de 1000 caracteres.",
        );
    }

    if ($estado === "rechazado" && $observacion === "") {
        volverDetalle($rendicionId, "Debes escribir una observación para cada boleta rechazada.");
    }

    $decisiones[$gastoId] = [
        "estado" => $estado,

        "observacion" => $observacion,
    ];
}

$decisionDevolucion = null;

if ($realizoDevolucion) {
    if (!is_array($devolucionRecibida)) {
        volverDetalle(
            $rendicionId,
            "Debes indicar si el comprobante de devolución es aceptado o rechazado.",
        );
    }

    $estadoDevolucion = trim((string) ($devolucionRecibida["estado"] ?? ""));

    $observacionDevolucion = trim((string) ($devolucionRecibida["observacion"] ?? ""));

    if (!in_array($estadoDevolucion, ["validado", "rechazado"], true)) {
        volverDetalle($rendicionId, "Debes aceptar o rechazar el comprobante de devolución.");
    }

    if (mb_strlen($observacionDevolucion, "UTF-8") > 1000) {
        volverDetalle(
            $rendicionId,
            "La observación del comprobante de devolución supera los 1000 caracteres.",
        );
    }

    if ($estadoDevolucion === "rechazado" && $observacionDevolucion === "") {
        volverDetalle(
            $rendicionId,
            "Debes indicar el motivo por el cual rechazas el comprobante de devolución.",
        );
    }

    $decisionDevolucion = [
        "estado" => $estadoDevolucion,

        "observacion" => $observacionDevolucion,
    ];
}

$rechazoGeneral = ($decisionDevolucion["estado"] ?? "") === "rechazado";
foreach ($decisiones as $decisionRevision) {
    $rechazoGeneral = $rechazoGeneral || $decisionRevision["estado"] === "rechazado";
}
if ($rechazoGeneral && $observacionGeneral === "") {
    volverDetalle(
        $rendicionId,
        "Debes escribir una observación general que explique el motivo del rechazo de la rendición.",
    );
}
// Gastos, devolución y estado general deben quedar guardados juntos para evitar revisiones parciales.
$conn->begin_transaction();

try {
    $stmtActualizar = $conn->prepare(
        "
            UPDATE rendicion_gastos

            SET

                estado_validacion = ?,
                observacion_validacion = ?,
                fecha_validacion = NOW()

            WHERE id = ?
            AND rendicion_id = ?
            ",
    );

    if (!$stmtActualizar) {
        throw new RuntimeException("No fue posible preparar la actualización de las boletas.");
    }

    $hayRechazadas = false;

    foreach ($decisiones as $gastoId => $decision) {
        $estado = $decision["estado"];

        $observacion = $decision["observacion"];

        if ($estado === "rechazado") {
            $hayRechazadas = true;
        }

        $stmtActualizar->bind_param("ssii", $estado, $observacion, $gastoId, $rendicionId);

        if (!$stmtActualizar->execute()) {
            throw new RuntimeException("No fue posible actualizar una de las boletas.");
        }
    }

    $stmtActualizar->close();

    if ($realizoDevolucion && is_array($decisionDevolucion)) {
        $estadoDevolucion = $decisionDevolucion["estado"];

        $observacionDevolucion = $decisionDevolucion["observacion"];

        if ($estadoDevolucion === "rechazado") {
            $hayRechazadas = true;
        }

        $stmtDevolucion = $conn->prepare(
            "
                UPDATE rendiciones

                SET

                    devolucion_estado_validacion = ?,
                    devolucion_observacion_validacion = ?,
                    devolucion_fecha_validacion = NOW()

                WHERE id = ?
                ",
        );

        if (!$stmtDevolucion) {
            throw new RuntimeException(
                "No fue posible preparar la revisión del comprobante de devolución.",
            );
        }

        $stmtDevolucion->bind_param("ssi", $estadoDevolucion, $observacionDevolucion, $rendicionId);

        if (!$stmtDevolucion->execute()) {
            throw new RuntimeException(
                "No fue posible guardar la revisión del comprobante de devolución.",
            );
        }

        $stmtDevolucion->close();
    }

    $nuevoEstadoRendicion = $hayRechazadas ? "rechazada" : "aprobada";

    $stmtRendicion = $conn->prepare(
        "
            UPDATE rendiciones

            SET

                estado = ?,
                observaciones = ?,
                fecha_revision = NOW(), administrador_revision_id = ?

            WHERE id = ?
            ",
    );

    if (!$stmtRendicion) {
        throw new RuntimeException("No fue posible preparar la actualización de la rendición.");
    }

    $stmtRendicion->bind_param(
        "ssii",
        $nuevoEstadoRendicion,
        $observacionGeneral,
        $_SESSION["usuario_id"],
        $rendicionId,
    );

    if (!$stmtRendicion->execute()) {
        throw new RuntimeException("No fue posible actualizar el estado general de la rendición.");
    }

    $stmtRendicion->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();

    error_log("Error revisión general rendición: " . $e->getMessage());

    volverDetalle($rendicionId, "No fue posible guardar la revisión. Inténtalo nuevamente.");
}

 $mensajeExito = $realizoDevolucion
        ? "La revisión de las boletas y del comprobante de devolución fue guardada correctamente."
        : "La revisión de todas las boletas fue guardada correctamente.";

// Igual que en la evaluación de postulaciones, se intenta avisar al estudiante
// inmediatamente. Si la fecha de publicación aún no llega, la tarea programada
// lo enviará después.
require_once __DIR__ . "/../correo/notificar_resultado_rendicion.php";
$resultadoCorreo = notificarResultadoRendicion($conn, $rendicionId);
$mensajeExito .= match ($resultadoCorreo) {
    "enviado" => " El resultado fue enviado al correo del estudiante.",
    "ya_enviado" => " El resultado ya había sido enviado al correo del estudiante.",
    "pendiente" => " El correo se enviará cuando se publique el resultado.",
    "en_proceso" => " El envío del correo está en curso.",
    default => " No se pudo enviar el correo del resultado. Revisa el registro de errores.",
};

volverDetalle($rendicionId, $mensajeExito, true);
