<?php
require_once __DIR__ . "/../config/aviso.php";

/**
 * Comprueba el acceso a la rendición antes de mostrar su comprobante.
 */

require_once __DIR__ . "/contexto_rendicion.php";

require_once "../config/conexion.php";

$stmt = $conn->prepare(
    "
        SELECT

            r.id,
            r.estado,
            r.anio_cursa,
            COALESCE(r.monto_anual, p.monto_asignado) AS monto_anual,
            r.fecha_inicio,
            r.fecha_envio,
            r.fecha_revision,
            r.observaciones,
            r.acepta_declaracion,
            r.monto_devuelto,
            r.fecha_deposito_devolucion,
            r.numero_comprobante_devolucion,
            dp.rut,
            dp.nombres,
            dp.apellido_paterno,
            dp.apellido_materno,
            dp.correo_electronico,
            de.carrera,
            de.nombre_establecimiento,
            de.region,
            de.comuna,
            de.tiene_gratuidad,
            de.promedio_ultimo_semestre

        FROM rendiciones r
        INNER JOIN postulaciones p ON p.id=r.postulacion_id

        INNER JOIN datos_personales dp ON dp.postulacion_id=r.postulacion_id
        INNER JOIN datos_educacionales de ON de.postulacion_id=r.postulacion_id

        WHERE
            r.postulacion_id = ?

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

if (!$rendicion) {
    header("Location: rendicion.php");

    exit();
}

if (!in_array($rendicion["estado"], ["enviada", "en_revision", "aprobada", "rechazada"], true)) {
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
    mostrarAvisoBecas("No fue posible consultar los gastos.");
}

$stmt->bind_param("i", $rendicion["id"]);

$stmt->execute();

$gastos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

require_once __DIR__ . "/../vistas/usuario/comprobante_rendicion.php";
