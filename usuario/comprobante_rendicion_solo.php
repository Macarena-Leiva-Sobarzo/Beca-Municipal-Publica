<?php

/**
 * Comprueba el acceso a la rendición antes de mostrar su comprobante.
 */

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/aviso.php";

require_once "../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";

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
    mostrarAvisoAccesoBecas("No tienes permisos para acceder a este comprobante.");
}

if ($accesoRutId <= 0) {
    header("Location: ../auth/cerrar_sesion.php");

    exit();
}

$stmt = $conn->prepare(
    "
        SELECT

            r.id,
            r.proceso_id,
            r.estado,
            r.anio_cursa,
            r.monto_anual,
            r.fecha_inicio,
            r.fecha_envio,
            r.fecha_revision,
            r.observaciones,
            r.acepta_declaracion,
            r.monto_devuelto,
            r.fecha_deposito_devolucion,
            r.numero_comprobante_devolucion,
            ar.rut,
            dru.nombres,
            dru.apellido_paterno,
            dru.apellido_materno,
            dru.correo_electronico,
            dru.carrera,
            dru.nombre_establecimiento,
            dru.region,
            dru.comuna,
            dru.tiene_gratuidad,
            dru.promedio_ultimo_semestre

        FROM rendiciones r

        INNER JOIN accesos_rut ar
            ON ar.id = r.acceso_rut_id

        LEFT JOIN datos_rendicion_usuario dru
            ON dru.acceso_rut_id =
                r.acceso_rut_id

        WHERE
            r.acceso_rut_id = ?
            AND r.proceso_id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    die("No fue posible consultar la rendición.");
}

$procesoSoloId = (int) $procesoSolo["id"];

$stmt->bind_param("ii", $accesoRutId, $procesoSoloId);

$stmt->execute();

$rendicion = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$rendicion) {
    header("Location: rendicion.php");

    exit();
}

if (
    !in_array(
        $rendicion["estado"],
        ["enviada", "en_revision", "aprobada", "rechazada"],
        true,
    )
) {
    header("Location: rendicion.php");

    exit();
}

$stmt = $conn->prepare(
    "
        SELECT

            fecha_gasto,
            categoria,
            categoria_otro,
            tipo_documento,
            numero_documento,
            monto,
            nombre_archivo_original

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

$stmt->bind_param("i", $rendicion["id"]);

$stmt->execute();

$gastos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

require_once __DIR__ . "/../vistas/usuario/comprobante_rendicion_solo.php";
