<?php
require_once __DIR__ . "/../config/aviso.php";

/**
 * Prepara el formulario con los datos del usuario, el proceso y la rendición vigente.
 */

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";
// El acceso por RUT corresponde únicamente a convocatorias de solo rendición.
// Las convocatorias completas exigen la autorización de la postulación en el otro portal.
$modalidadActual = $conn
    ->query(
        "SELECT id,tipo_proceso FROM procesos_beca WHERE activo=1 ORDER BY id DESC LIMIT 1",
    )
    ->fetch_assoc();
if (($modalidadActual["tipo_proceso"] ?? "") !== "solo_rendicion") {
    http_response_code(403);
    mostrarAvisoBecas(
        "Esta convocatoria requiere ingresar al portal de postulación y rendición con una postulación beneficiada.",
    );
}

/** @var mysqli $conn */

date_default_timezone_set("America/Santiago");

$zonaHoraria = new DateTimeZone("America/Santiago");

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

$rutSesion = trim((string) $_SESSION["rut"]);

if ($accesoRutId <= 0 || $rutSesion === "") {
    header("Location: ../auth/cerrar_sesion.php");

    exit();
}

if (!usuarioHabilitadoSoloRendicion($conn, (int) $modalidadActual["id"], $accesoRutId)) {
    http_response_code(403);
    mostrarAvisoBecas("Tu RUT no está habilitado para este proceso de rendición.");
}

if (!function_exists("e")) {
    function e(mixed $valor): string
    {
        return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
    }

}

if (!function_exists("totalGastos")) {
    function totalGastos(array $gastos): float
    {
        $total = 0.0;

        foreach ($gastos as $gasto) {
            $total += (float) ($gasto["monto"] ?? 0);
        }

        return $total;
    }
}

$stmt = $conn->prepare(
    "
        SELECT

            id,
            rut,
            rol,
            activo

        FROM accesos_rut

        WHERE id = ?
        AND rol = 'usuario'
        AND activo = 1

        LIMIT 1
        ",
);

if (!$stmt) {
    die("No fue posible comprobar el acceso.");
}

$stmt->bind_param("i", $accesoRutId);

$stmt->execute();

$acceso = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$acceso) {
    header("Location: ../auth/cerrar_sesion.php");

    exit();
}

$rutBase = strtoupper(
    str_replace([".", " "], "", trim((string) $acceso["rut"])),
);

$rutSesionComparar = strtoupper(str_replace([".", " "], "", $rutSesion));

if ($rutBase !== $rutSesionComparar) {
    header("Location: ../auth/cerrar_sesion.php");

    exit();
}

$stmt = $conn->prepare(
    "
        SELECT

            id,
            acceso_rut_id,
            nombres,
            apellido_paterno,
            apellido_materno,
            fecha_nacimiento,
            direccion,
            telefono,
            correo_electronico,
            carrera,
            nombre_establecimiento,
            region,
            comuna,
            tiene_gratuidad,
            promedio_ultimo_semestre,
            fecha_creacion,
            fecha_actualizacion

        FROM datos_rendicion_usuario

        WHERE acceso_rut_id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    die("No fue posible consultar los datos del beneficiario.");
}

$stmt->bind_param("i", $accesoRutId);

$stmt->execute();

$datosUsuario = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$datosUsuario) {
    $datosUsuario = [
        "id" => null,
        "acceso_rut_id" => $accesoRutId,
        "nombres" => "",
        "apellido_paterno" => "",
        "apellido_materno" => "",
        "fecha_nacimiento" => "",
        "direccion" => "",
        "telefono" => "",
        "correo_electronico" => "",
        "carrera" => "",
        "nombre_establecimiento" => "",
        "region" => "",
        "comuna" => "",
        "tiene_gratuidad" => "",
        "promedio_ultimo_semestre" => "",
        "fecha_creacion" => null,
        "fecha_actualizacion" => null,
    ];
}

$stmt = $conn->prepare(
    "
        SELECT

            id,
            acceso_rut_id,
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

        WHERE acceso_rut_id = ?
        AND proceso_id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    die("No fue posible consultar la rendición.");
}

$procesoActivoId = (int) $modalidadActual["id"];

$stmt->bind_param("ii", $accesoRutId, $procesoActivoId);

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
        die("No fue posible consultar el proceso de beca.");
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

            WHERE activo = 1

            ORDER BY id DESC

            LIMIT 1
            ",
    );

    if (!$stmt) {
        die("No fue posible consultar el proceso de beca.");
    }

    $stmt->execute();

    $proceso = $stmt->get_result()->fetch_assoc();

    $stmt->close();
}

if (!$proceso) {
    die(
        "Actualmente no existe un proceso de beca disponible para realizar la rendición."
    );
}

$procesoId = (int) $proceso["id"];

if (!$rendicion) {
    $fechaInicioActual = (new DateTime("now", $zonaHoraria))->format(
        "Y-m-d H:i:s",
    );

    $stmt = $conn->prepare(
        "
            INSERT INTO rendiciones
            (
                acceso_rut_id,
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
        die("No fue posible crear la rendición.");
    }

    $stmt->bind_param("iis", $accesoRutId, $procesoId, $fechaInicioActual);

    if (!$stmt->execute()) {
        $stmt->close();

        die("No fue posible crear la rendición.");
    }

    $rendicionId = (int) $conn->insert_id;

    $stmt->close();

    $rendicion = [
        "id" => $rendicionId,
        "acceso_rut_id" => $accesoRutId,
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
            AND acceso_rut_id = ?

            LIMIT 1
            ",
    );

    if (!$stmt) {
        die("No fue posible asociar la rendición al proceso.");
    }

    $rendicionId = (int) $rendicion["id"];

    $stmt->bind_param("iii", $procesoId, $rendicionId, $accesoRutId);

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
    die("No fue posible consultar los gastos.");
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

$camposDatosUsuario = [
    "nombres",
    "apellido_paterno",
    "apellido_materno",
    "fecha_nacimiento",
    "direccion",
    "telefono",
    "correo_electronico",
    "carrera",
    "nombre_establecimiento",
    "region",
    "comuna",
    "tiene_gratuidad",
    "promedio_ultimo_semestre",
];

if ($huboErrorFormulario && $rendicion["estado"] === "no_iniciada") {
    foreach ($camposDatosUsuario as $campo) {
        if (array_key_exists($campo, $formAnterior)) {
            $datosUsuario[$campo] = $formAnterior[$campo];
        }
    }

    if (array_key_exists("anio_cursa", $formAnterior)) {
        $rendicion["anio_cursa"] = $formAnterior["anio_cursa"];
    }

    if (array_key_exists("monto_anual", $formAnterior)) {
        $rendicion["monto_anual"] = $formAnterior["monto_anual"];
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
                "tipo_documento" =>
                    $gastoAnterior["tipo_documento"] ?? "boleta",
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
    : $rendicion["monto_devuelto"] !== null &&
        (float) $rendicion["monto_devuelto"] > 0;

$aceptaDeclaracionFormulario = $huboErrorFormulario
    ? isset($formAnterior["acepta_declaracion"])
    : (int) ($rendicion["acepta_declaracion"] ?? 0) === 1;

$confirmacionFormulario = $huboErrorFormulario
    ? isset($formAnterior["confirmacion"])
    : false;

$nombreCompleto = trim(
    (string) $datosUsuario["nombres"] .
        " " .
        (string) $datosUsuario["apellido_paterno"] .
        " " .
        (string) $datosUsuario["apellido_materno"],
);

$montoAnual = (float) ($rendicion["monto_anual"] ?? 0);

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

if (
    $fechaInicioRendicion instanceof DateTime &&
    $fechaCierreRendicion instanceof DateTime
) {
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

$estaRendicionEnRevision = in_array(
    $estadoActualRendicion,
    ["enviada", "en_revision"],
    true,
);

$mostrarComoEnRevision =
    $estaRendicionEnRevision ||
    (($esRendicionAprobada || $esRendicionRechazada) &&
        !$resultadosRendicionDisponibles);

$rendicionYaEnviada = in_array(
    $estadoActualRendicion,
    ["enviada", "en_revision", "aprobada", "rechazada"],
    true,
);

$estadoPermiteEditar = $esRendicionNueva;

$puedeEditar = $esRendicionNueva && $estadoPlazoRendicion === "abierto";

$mostrarEstadoPlazo = $esRendicionNueva;

$realizoDevolucion =
    $rendicion["monto_devuelto"] !== null &&
    (float) $rendicion["monto_devuelto"] > 0;

$estadoDevolucion = trim(
    (string) ($rendicion["devolucion_estado_validacion"] ?? ""),
);

if (!in_array($estadoDevolucion, ["validado", "rechazado"], true)) {
    $estadoDevolucion = "pendiente";
}

$observacionDevolucion = trim(
    (string) ($rendicion["devolucion_observacion_validacion"] ?? ""),
);

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

        "observacion" => trim(
            (string) ($gasto["observacion_validacion"] ?? ""),
        ),

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

        "numero_documento" =>
            $rendicion["numero_comprobante_devolucion"] ?? null,

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

require_once __DIR__ . "/../vistas/usuario/rendicion_solo.php";

unset(
    $_SESSION["rendicion_exito"],
    $_SESSION["rendicion_error"],
    $_SESSION["rendicion_errores_gastos"],
    $_SESSION["rendicion_form_anterior"],
);
