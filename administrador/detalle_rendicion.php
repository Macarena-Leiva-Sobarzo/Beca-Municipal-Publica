<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

date_default_timezone_set("America/Santiago");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["administrador", "superior"], true)) {
    header("Location: ../index.php");
    exit();
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function dinero(mixed $valor): string
{
    return "$" . number_format((float) $valor, 0, ",", ".");
}

function fecha(?string $valor): string
{
    if (empty($valor)) {
        return "-";
    }

    $timestamp = strtotime($valor);

    if ($timestamp === false) {
        return "-";
    }

    return date("d/m/Y", $timestamp);
}

function fechaHora(?string $valor): string
{
    if (empty($valor)) {
        return "-";
    }

    $timestamp = strtotime($valor);

    if ($timestamp === false) {
        return "-";
    }

    return date("d/m/Y H:i", $timestamp);
}

function fechaHoraRevision(?string $valor): string
{
    if (empty($valor)) {
        return "-";
    }

    $timestamp = strtotime($valor);

    return $timestamp === false ? "-" : date("d/m/Y H:i:s", $timestamp);
}

function categoriaGasto(?string $categoria, ?string $otro = null): string
{
    if ($categoria === "otro" && trim((string) $otro) !== "") {
        return "Otro: " . trim($otro);
    }

    return match ($categoria) {
        "arancel" => "Arancel",

        "matricula" => "Matrícula",

        "materiales" => "Materiales de estudio",

        "vestuario" => "Vestuario",

        "alimentacion" => "Alimentación",

        default => ucfirst(str_replace("_", " ", (string) $categoria)),
    };
}

function estadoBoleta(?string $estado): string
{
    return match ($estado) {
        "validado" => "Aceptado",

        "rechazado" => "Rechazado",

        default => "Pendiente",
    };
}

function claseEstadoBoleta(?string $estado): string
{
    return match ($estado) {
        "validado" => "validado",

        "rechazado" => "rechazado",

        default => "pendiente",
    };
}

function textoEstadoRendicion(?string $estado): string
{
    return match ($estado) {
        "no_iniciada" => "No iniciada",

        "enviada" => "En revisión",

        "en_revision" => "En revisión",

        "aprobada" => "Aprobada",

        "rechazada" => "Rechazada",

        default => ucfirst(str_replace("_", " ", (string) $estado)),
    };
}

$rendicionId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$rendicionId) {
    header("Location: rendiciones.php");
    exit();
}

$stmt = $conn->prepare(
    "
        SELECT

            r.id,
            r.estado,
            r.anio_cursa, r.monto_anual,
            r.fecha_inicio,
            r.fecha_envio,
            r.fecha_revision,
            r.administrador_revision_id,
            TRIM(CONCAT_WS(' ', revisor.nombres, revisor.apellido_paterno, revisor.apellido_materno)) AS nombre_revisor,
            r.observaciones,
            r.acepta_declaracion,

            r.monto_devuelto,
            r.fecha_deposito_devolucion,
            r.numero_comprobante_devolucion,

            r.devolucion_nombre_archivo,
            r.devolucion_nombre_original,
            r.devolucion_ruta,

            r.devolucion_estado_validacion,
            r.devolucion_observacion_validacion,
            r.devolucion_fecha_validacion,

            p.id AS postulacion_id,
            COALESCE(p.folio, CONCAT('REND-', r.id)) AS folio,
            COALESCE(p.monto_asignado, r.monto_anual, r.monto_primer_semestre + r.monto_segundo_semestre) AS monto_asignado,
            p.monto_primer_semestre,
            p.monto_segundo_semestre,

            dp.nombres,
            dp.apellido_paterno,
            dp.apellido_materno,
            dp.rut,
            dp.correo_electronico,
            dp.telefono,

            de.carrera,
            de.nombre_establecimiento

        FROM rendiciones r

        LEFT JOIN postulaciones p ON p.id = r.postulacion_id

        LEFT JOIN usuarios revisor ON revisor.id = r.administrador_revision_id

        INNER JOIN personas_rendicion dp ON dp.rendicion_id = r.id

        INNER JOIN personas_rendicion de ON de.rendicion_id = r.id

        WHERE r.id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar el detalle de la rendición.");
}

$stmt->bind_param("i", $rendicionId);

$stmt->execute();

$detalleRendicion = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$detalleRendicion) {
    header("Location: rendiciones.php");
    exit();
}

$realizoDevolucion =
    $detalleRendicion["monto_devuelto"] !== null && (float) $detalleRendicion["monto_devuelto"] > 0;

$comprobanteDevolucionAdjunto = $realizoDevolucion && !empty($detalleRendicion["devolucion_ruta"]);

$estadoDevolucionAdmin = trim((string) ($detalleRendicion["devolucion_estado_validacion"] ?? ""));

if (!in_array($estadoDevolucionAdmin, ["validado", "rechazado"], true)) {
    $estadoDevolucionAdmin = "pendiente";
}

$revisionAdministrativaTerminada = in_array(
    (string) $detalleRendicion["estado"],
    ["aprobada", "rechazada"],
    true,
);

$stmt = $conn->prepare(
    "
        SELECT

            id,
            rendicion_id,
            semestre,
            fecha_gasto,
            categoria,
            tipo_documento,
            numero_documento,
            categoria_otro,
            monto,

            nombre_archivo,
            nombre_archivo_original,
            ruta_archivo,

            tipo_mime,
            extension,
            tamano_bytes,

            estado_validacion,
            observacion_validacion,
            fecha_validacion,
            fecha_registro

        FROM rendicion_gastos

        WHERE rendicion_id = ?

        ORDER BY
            semestre,
            fecha_gasto,
            id
        ",
);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar las boletas.");
}

$stmt->bind_param("i", $rendicionId);

$stmt->execute();

$resultadoGastos = $stmt->get_result();

$gastosPrimerSemestre = [];

$gastosSegundoSemestre = [];

while ($gasto = $resultadoGastos->fetch_assoc()) {
    if ($revisionAdministrativaTerminada) {
        $gasto["estado_revision_admin"] = $gasto["estado_validacion"] ?? "pendiente";
    } else {
        $gasto["estado_revision_admin"] = "pendiente";
    }

    if (in_array($gasto["semestre"], ["primer_semestre", "anual"], true)) {
        $gastosPrimerSemestre[] = $gasto;
    } elseif ($gasto["semestre"] === "segundo_semestre") {
        $gastosSegundoSemestre[] = $gasto;
    }
}

$stmt->close();

$totalBoletasDetalle = 0;

$totalAceptadasDetalle = 0;

$totalRechazadasDetalle = 0;

$totalPendientesDetalle = 0;

$totalMontoDetalle = 0.0;

$totalMontoAceptadoDetalle = 0.0;

$totalMontoRechazadoDetalle = 0.0;

$todosLosGastos = array_merge($gastosPrimerSemestre, $gastosSegundoSemestre);

foreach ($todosLosGastos as $gasto) {
    $totalBoletasDetalle++;

    $montoGasto = (float) ($gasto["monto"] ?? 0);

    $totalMontoDetalle += $montoGasto;

    $estadoAdmin = $gasto["estado_revision_admin"] ?? "pendiente";

    if ($estadoAdmin === "validado") {
        $totalAceptadasDetalle++;

        $totalMontoAceptadoDetalle += $montoGasto;
    } elseif ($estadoAdmin === "rechazado") {
        $totalRechazadasDetalle++;

        $totalMontoRechazadoDetalle += $montoGasto;
    } else {
        $totalPendientesDetalle++;
    }
}

$devolucionAceptada = $realizoDevolucion && $estadoDevolucionAdmin === "validado";

$devolucionRechazada = $realizoDevolucion && $estadoDevolucionAdmin === "rechazado";

$devolucionPendiente = $realizoDevolucion && $estadoDevolucionAdmin === "pendiente";

$mensajeExito = $_SESSION["admin_exito"] ?? null;

$mensajeError = $_SESSION["admin_error"] ?? null;

unset($_SESSION["admin_exito"], $_SESSION["admin_error"]);

require_once __DIR__ . "/../vistas/administrador/detalle_rendicion.php";
