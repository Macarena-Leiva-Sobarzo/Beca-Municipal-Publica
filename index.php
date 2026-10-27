<?php

require_once __DIR__ . "/config/sesion.php";

require_once "config/conexion.php";

date_default_timezone_set("America/Santiago");

$zonaHoraria = new DateTimeZone("America/Santiago");

$proceso = null;

$requisitos = [];

$sqlProceso = "
    SELECT
        id,
        nombre,
        descripcion,
        fecha_inicio,
        fecha_cierre,
        fecha_resultados,
        fecha_inicio_rendicion,
        fecha_cierre_rendicion,
        fecha_resultados_rendicion,
        tipo_proceso,
        activo
    FROM procesos_beca
    WHERE activo = 1
    ORDER BY id DESC
    LIMIT 1
";

$resultadoProceso = $conn->query($sqlProceso);

if ($resultadoProceso && $resultadoProceso->num_rows === 1) {
    $proceso = $resultadoProceso->fetch_assoc();

    $esSoloRendicion = ($proceso["tipo_proceso"] ?? "completo") === "solo_rendicion";

    // La portada conserva sus variables de fecha, pero para un proceso exclusivo
    // de rendición las alimenta con las fechas propias de esa etapa.
    if ($esSoloRendicion) {
        $proceso["fecha_inicio"] = $proceso["fecha_inicio_rendicion"];
        $proceso["fecha_cierre"] = $proceso["fecha_cierre_rendicion"];
        $proceso["fecha_resultados"] = $proceso["fecha_resultados_rendicion"];
    }

    $stmt = $conn->prepare(
        "
            SELECT
                id,
                titulo,
                descripcion,
                obligatorio,
                orden
            FROM requisitos_beca
            WHERE proceso_id = ?
            AND activo = 1
            ORDER BY orden ASC, id ASC
            ",
    );

    if ($stmt) {
        $stmt->bind_param("i", $proceso["id"]);

        $stmt->execute();

        $resultadoRequisitos = $stmt->get_result();

        while ($fila = $resultadoRequisitos->fetch_assoc()) {
            $requisitos[] = $fila;
        }

        $stmt->close();
    }
}

$esSoloRendicion = $proceso && (($proceso["tipo_proceso"] ?? "completo") === "solo_rendicion");
$nombreEtapa = $esSoloRendicion ? "rendición" : "postulación";
$nombreEtapaPlural = $esSoloRendicion ? "rendiciones" : "postulaciones";

function mostrarFechaPublica(?string $fecha, DateTimeZone $zonaHoraria): string
{
    if (empty($fecha)) {
        return "Por definir";
    }

    $fechaObjeto = DateTime::createFromFormat("Y-m-d H:i:s", $fecha, $zonaHoraria);

    if (!($fechaObjeto instanceof DateTime)) {
        return "Por definir";
    }

    return $fechaObjeto->format("d/m/Y H:i");
}

$procesoAbierto = false;

$procesoNoIniciado = false;

$procesoCerrado = false;

$fechaInicioProceso = null;

$fechaCierreProceso = null;

if ($proceso) {
    /*
    Hora actual real de Chile.
    */

    $ahora = new DateTime("now", $zonaHoraria);

    if (!empty($proceso["fecha_inicio"])) {
        $fechaInicioProceso = DateTime::createFromFormat(
            "Y-m-d H:i:s",
            $proceso["fecha_inicio"],
            $zonaHoraria,
        );
    }

    if (!empty($proceso["fecha_cierre"])) {
        $fechaCierreProceso = DateTime::createFromFormat(
            "Y-m-d H:i:s",
            $proceso["fecha_cierre"],
            $zonaHoraria,
        );
    }

    if ($fechaInicioProceso instanceof DateTime && $fechaCierreProceso instanceof DateTime) {
        if ($ahora < $fechaInicioProceso) {
            $procesoNoIniciado = true;
        } elseif ($ahora > $fechaCierreProceso) {
            $procesoCerrado = true;
        } else {
            $procesoAbierto = true;
        }
    }
}

require_once __DIR__ . "/vistas/index.php";
