<?php
require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";

$procesoSoloRendicion = procesoSoloRendicionActivo($conn);
if (
    $procesoSoloRendicion &&
    ($_SESSION["rol"] ?? "") === "usuario" &&
    usuarioHabilitadoSoloRendicion($conn, (int) $procesoSoloRendicion["id"], (int) ($_SESSION["usuario_id"] ?? 0))
) {
    require __DIR__ . "/rendicion_solo.php";
    exit();
}

require_once __DIR__ . "/../config/aviso.php";
require_once __DIR__ . "/contexto_rendicion.php";
if (!empty($mensajeAvisoRendicion)) {
    require __DIR__ . "/../vistas/usuario/aviso_rendicion.php";
    exit();
}
$fechaAperturaRendicion = fechaBeca($postulacion["fecha_inicio_rendicion"] ?? null);
if (
    $fechaAperturaRendicion &&
    new DateTimeImmutable("now", new DateTimeZone("America/Santiago")) < $fechaAperturaRendicion
) {
    require __DIR__ . "/../vistas/usuario/espera_rendicion.php";
    exit();
}
date_default_timezone_set("America/Santiago");
$zonaHoraria = new DateTimeZone("America/Santiago");
function e(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ""), ENT_QUOTES, "UTF-8");
}
function totalGastos(array $gastos): float
{
    return array_sum(array_column($gastos, "monto"));
}
if ($mensajeAnioProceso) {
    require __DIR__ . "/../vistas/usuario/rendicion.php";
    exit();
}
$stmt = $conn->prepare(
    "
        SELECT

            id,
            postulacion_id,
            proceso_id,
            estado,
            anio_cursa,
            monto_anual,
            fecha_inicio,
            fecha_envio,
            fecha_revision,
            observaciones,
            acepta_declaracion,
            monto_devuelto,
            fecha_deposito_devolucion,
            numero_comprobante_devolucion,
            devolucion_nombre_archivo,
            devolucion_nombre_original,
            devolucion_ruta,
            devolucion_estado_validacion,
            devolucion_observacion_validacion,
            devolucion_fecha_validacion

        FROM rendiciones

        WHERE postulacion_id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar la rendición.");
}

$stmt->bind_param("i", $postulacionId);

$stmt->execute();

$rendicion = $stmt->get_result()->fetch_assoc();

$stmt->close();

$proceso = null;

if ($rendicion && !empty($rendicion["proceso_id"])) {
    $procesoId = (int) $rendicion["proceso_id"];

    $stmt = $conn->prepare(
        "
            SELECT

                id,
                nombre,
                descripcion,
                instrucciones_nombre_original,
                instrucciones_nombre_archivo,
                instrucciones_ruta,
                fecha_resultados_rendicion,
                fecha_inicio_rendicion,
                fecha_cierre_rendicion,
                activo

            FROM procesos_beca

            WHERE id = ?

            LIMIT 1
            ",
    );

    if (!$stmt) {
        mostrarAvisoBecas("No fue posible consultar el proceso de beca.");
    }

    $stmt->bind_param("i", $procesoId);

    $stmt->execute();

    $proceso = $stmt->get_result()->fetch_assoc();

    $stmt->close();
} else {
    $stmt = $conn->prepare(
        "
            SELECT

                id,
                nombre,
                descripcion,
                instrucciones_nombre_original,
                instrucciones_nombre_archivo,
                instrucciones_ruta,
                fecha_resultados_rendicion,
                fecha_inicio_rendicion,
                fecha_cierre_rendicion,
                activo

            FROM procesos_beca

            WHERE id = " .
            (int) $postulacion["proceso_id"] .
            "

            ORDER BY id DESC

            LIMIT 1
            ",
    );

    if (!$stmt) {
        mostrarAvisoBecas("No fue posible consultar el proceso de beca.");
    }

    $stmt->execute();

    $proceso = $stmt->get_result()->fetch_assoc();

    $stmt->close();
}

if (!$proceso) {
    mostrarAvisoBecas(
        "Actualmente no existe un proceso de beca disponible para realizar la rendición.",
    );
}

$procesoId = (int) $proceso["id"];

if (!$rendicion) {
    $fechaInicioActual = (new DateTime("now", $zonaHoraria))->format("Y-m-d H:i:s");

    $stmt = $conn->prepare(
        "
            INSERT INTO rendiciones
            (
                postulacion_id,
                proceso_id,
                    estado,
                fecha_inicio,

                acepta_declaracion
            )

            VALUES
            (
                ?,
                ?,

                'no_iniciada',
                ?,

                0
            )
            ",
    );

    if (!$stmt) {
        mostrarAvisoBecas("No fue posible crear la rendición.");
    }

    $stmt->bind_param("iis", $postulacionId, $procesoId, $fechaInicioActual);

    if (!$stmt->execute()) {
        $stmt->close();

        mostrarAvisoBecas("No fue posible crear la rendición.");
    }

    $rendicionId = (int) $conn->insert_id;

    $stmt->close();

    $rendicion = [
        "id" => $rendicionId,
        "postulacion_id" => $postulacionId,
        "proceso_id" => $procesoId,
        "estado" => "no_iniciada",
        "anio_cursa" => null,
        "monto_anual" => null,
        "fecha_inicio" => $fechaInicioActual,
        "fecha_envio" => null,
        "fecha_revision" => null,
        "observaciones" => null,
        "acepta_declaracion" => 0,
        "monto_devuelto" => null,
        "fecha_deposito_devolucion" => null,
        "numero_comprobante_devolucion" => null,
        "comprobante_devolucion" => null,
        "devolucion_nombre_archivo" => null,
        "devolucion_nombre_original" => null,
        "devolucion_ruta" => null,
        "devolucion_estado_validacion" => null,
        "devolucion_observacion_validacion" => null,
        "devolucion_fecha_validacion" => null,
    ];
}

if (empty($rendicion["proceso_id"])) {
    $stmt = $conn->prepare(
        "
            UPDATE rendiciones

            SET proceso_id = ?

            WHERE id = ?
            AND postulacion_id = ?

            LIMIT 1
            ",
    );

    if (!$stmt) {
        mostrarAvisoBecas("No fue posible asociar la rendición al proceso.");
    }

    $rendicionId = (int) $rendicion["id"];

    $stmt->bind_param("iii", $procesoId, $rendicionId, $postulacionId);

    $stmt->execute();

    $stmt->close();

    $rendicion["proceso_id"] = $procesoId;
}

$gastos = [];

$stmt = $conn->prepare(
    "
        SELECT

            id,
            fecha_gasto,
            categoria,
            categoria_otro,
            tipo_documento,
            numero_documento,
            monto,
            nombre_archivo,
            nombre_archivo_original,
            ruta_archivo,
            tipo_mime,
            extension,
            tamano_bytes,
            estado_validacion,
            monto_detectado,
            numero_documento_detectado,
            observacion_validacion,
            fecha_validacion,
            fecha_registro

        FROM rendicion_gastos

        WHERE rendicion_id = ?

        ORDER BY
            fecha_gasto,
            id
        ",
);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar los gastos.");
}

$rendicionId = (int) $rendicion["id"];

$stmt->bind_param("i", $rendicionId);

$stmt->execute();

$resultadoGastos = $stmt->get_result();

while ($gasto = $resultadoGastos->fetch_assoc()) {
    $gastos[] = $gasto;
}

$stmt->close();

$formAnterior = $_SESSION["rendicion_form_anterior"] ?? [];

$huboErrorFormulario = is_array($formAnterior) && !empty($formAnterior);

if ($huboErrorFormulario && $rendicion["estado"] === "no_iniciada") {
    if (array_key_exists("anio_cursa", $formAnterior)) {
        $rendicion["anio_cursa"] = $formAnterior["anio_cursa"];
    }

    $gastos = [];

    $gastosAnteriores = $formAnterior["gastos"] ?? [];

    if (is_array($gastosAnteriores)) {
        foreach ($gastosAnteriores as $gastoAnterior) {
            if (!is_array($gastoAnterior)) {
                continue;
            }

            $gastos[] = [
                "id" => null,
                "fecha_gasto" => $gastoAnterior["fecha"] ?? "",
                "categoria" => $gastoAnterior["categoria"] ?? "",
                "categoria_otro" => $gastoAnterior["categoria_otro"] ?? "",
                "tipo_documento" => $gastoAnterior["tipo_documento"] ?? "boleta",
                "numero_documento" => $gastoAnterior["numero_documento"] ?? "",
                "monto" => $gastoAnterior["monto"] ?? "",
                "nombre_archivo" => null,
                "nombre_archivo_original" => null,
                "ruta_archivo" => null,
                "tipo_mime" => null,
                "extension" => "pdf",
                "tamano_bytes" => null,
                "estado_validacion" => "pendiente",
                "monto_detectado" => null,
                "numero_documento_detectado" => null,
                "observacion_validacion" => null,
                "fecha_validacion" => null,
                "fecha_registro" => null,
            ];
        }
    }
}

$tieneDevolucionFormulario = $huboErrorFormulario
    ? isset($formAnterior["tiene_devolucion"])
    : $rendicion["monto_devuelto"] !== null && (float) $rendicion["monto_devuelto"] > 0;

$aceptaDeclaracionFormulario = $huboErrorFormulario
    ? isset($formAnterior["acepta_declaracion"])
    : (int) ($rendicion["acepta_declaracion"] ?? 0) === 1;

$confirmacionFormulario = $huboErrorFormulario ? isset($formAnterior["confirmacion"]) : false;

$nombreCompleto = trim(
    (string) $datosUsuario["nombres"] .
        " " .
        (string) $datosUsuario["apellido_paterno"] .
        " " .
        (string) $datosUsuario["apellido_materno"],
);

$montoAnual = (float) ($rendicion["monto_anual"] ?? ($postulacion["monto_asignado"] ?? 0));

$montoTotal = $montoAnual;

$totalGeneral = totalGastos($gastos);

$ahora = new DateTime("now", $zonaHoraria);

$fechaResultados = null;

if (!empty($proceso["fecha_resultados_rendicion"])) {
    $fechaResultados = DateTime::createFromFormat(
        "Y-m-d H:i:s",
        $proceso["fecha_resultados_rendicion"],
        $zonaHoraria,
    );
}

$fechaInicioRendicion = null;

$fechaCierreRendicion = null;

if (!empty($proceso["fecha_inicio_rendicion"])) {
    $fechaInicioRendicion = DateTime::createFromFormat(
        "Y-m-d H:i:s",
        $proceso["fecha_inicio_rendicion"],
        $zonaHoraria,
    );
}

if (!empty($proceso["fecha_cierre_rendicion"])) {
    $fechaCierreRendicion = DateTime::createFromFormat(
        "Y-m-d H:i:s",
        $proceso["fecha_cierre_rendicion"],
        $zonaHoraria,
    );
}

$resultadosRendicionDisponibles =
    $fechaResultados instanceof DateTime && $ahora >= $fechaResultados;

$estadoPlazoRendicion = "sin_configurar";

if ($fechaInicioRendicion instanceof DateTime && $fechaCierreRendicion instanceof DateTime) {
    if ($ahora < $fechaInicioRendicion) {
        $estadoPlazoRendicion = "no_iniciado";
    } elseif ($ahora > $fechaCierreRendicion) {
        $estadoPlazoRendicion = "vencido";
    } else {
        $estadoPlazoRendicion = "abierto";
    }
}

$estadoActualRendicion = (string) ($rendicion["estado"] ?? "no_iniciada");

$esRendicionNueva = $estadoActualRendicion === "no_iniciada";

$esRendicionRechazada = $estadoActualRendicion === "rechazada";

$esRendicionAprobada = $estadoActualRendicion === "aprobada";

$estaRendicionEnRevision = in_array($estadoActualRendicion, ["enviada", "en_revision"], true);

$mostrarComoEnRevision =
    $estaRendicionEnRevision ||
    (($esRendicionAprobada || $esRendicionRechazada) && !$resultadosRendicionDisponibles);

$rendicionYaEnviada = in_array(
    $estadoActualRendicion,
    ["enviada", "en_revision", "aprobada", "rechazada"],
    true,
);

$estadoPermiteEditar = $esRendicionNueva;

$puedeEditar = $esRendicionNueva && $estadoPlazoRendicion === "abierto";

$mostrarEstadoPlazo = $esRendicionNueva;

$realizoDevolucion =
    $rendicion["monto_devuelto"] !== null && (float) $rendicion["monto_devuelto"] > 0;

$estadoDevolucion = trim((string) ($rendicion["devolucion_estado_validacion"] ?? ""));

if (!in_array($estadoDevolucion, ["validado", "rechazado"], true)) {
    $estadoDevolucion = "pendiente";
}

$observacionDevolucion = trim((string) ($rendicion["devolucion_observacion_validacion"] ?? ""));

$documentosRendicion = [];

foreach ($gastos as $indice => $gasto) {
    $documentosRendicion[] = [
        "tipo" => "gasto",

        "titulo" => "Documento " . ($indice + 1),

        "nombre_archivo" => $gasto["nombre_archivo_original"] ?? "",

        "fecha_gasto" => $gasto["fecha_gasto"] ?? null,

        "categoria" => $gasto["categoria"] ?? null,

        "categoria_otro" => $gasto["categoria_otro"] ?? null,

        "numero_documento" => $gasto["numero_documento"] ?? null,

        "monto" => $gasto["monto"] ?? null,

        "estado" => $gasto["estado_validacion"] ?? "pendiente",

        "observacion" => trim((string) ($gasto["observacion_validacion"] ?? "")),

        "fecha_validacion" => $gasto["fecha_validacion"] ?? null,
    ];
}

if ($realizoDevolucion) {
    $documentosRendicion[] = [
        "tipo" => "devolucion",

        "titulo" => "Comprobante de devolución",

        "nombre_archivo" => $rendicion["devolucion_nombre_original"] ?? "",

        "fecha_gasto" => $rendicion["fecha_deposito_devolucion"] ?? null,

        "categoria" => null,

        "numero_documento" => $rendicion["numero_comprobante_devolucion"] ?? null,

        "monto" => $rendicion["monto_devuelto"] ?? null,

        "estado" => $estadoDevolucion,

        "observacion" => $observacionDevolucion,

        "fecha_validacion" => $rendicion["devolucion_fecha_validacion"] ?? null,
    ];
}

$tieneInstrucciones = !empty($proceso["instrucciones_ruta"]);

$mensajeExito = $_SESSION["rendicion_exito"] ?? null;

$mensajeError = $_SESSION["rendicion_error"] ?? null;

$erroresGastos = $_SESSION["rendicion_errores_gastos"] ?? [];

$categoriaOtroNombre = "";

$mostrarCampoCategoriaOtro = true;

$categorias = [
    "arancel" => "Arancel",

    "matricula" => "Matrícula",

    "materiales" => "Materiales de estudio",

    "vestuario" => "Vestuario",

    "alimentacion" => "Alimentación",

    "otro" => "Otro",
];

require_once __DIR__ . "/../vistas/usuario/rendicion.php";

unset(
    $_SESSION["rendicion_exito"],
    $_SESSION["rendicion_error"],
    $_SESSION["rendicion_errores_gastos"],
    $_SESSION["rendicion_form_anterior"],
);
