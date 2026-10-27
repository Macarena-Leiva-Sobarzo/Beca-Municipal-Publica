<?php

/** Comparte la publicación entre los procesos de rendición del mismo año. */
function sincronizarResultadosRendicion(mysqli $conn, int $procesoId): void
{
    $stmt = $conn->prepare(
        "SELECT fecha_inicio_rendicion, fecha_resultados_rendicion FROM procesos_beca WHERE id=?",
    );
    $stmt->bind_param("i", $procesoId);
    $stmt->execute();
    $proceso = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$proceso || empty($proceso["fecha_inicio_rendicion"])) {
        throw new RuntimeException("El proceso debe tener fecha de inicio de rendición.");
    }
    // El período depende del inicio de rendición, no del año en que se publica el resultado.
    $anio = (int) substr($proceso["fecha_inicio_rendicion"], 0, 4);
    $desde = sprintf("%04d-01-01 00:00:00", $anio);
    $hasta = sprintf("%04d-01-01 00:00:00", $anio + 1);
    $fecha = $proceso["fecha_resultados_rendicion"];
    $stmt = $conn->prepare(
        "UPDATE procesos_beca SET fecha_resultados_rendicion=? WHERE fecha_inicio_rendicion >= ? AND fecha_inicio_rendicion < ?",
    );
    $stmt->bind_param("sss", $fecha, $desde, $hasta);
    $stmt->execute();
    $stmt->close();
}
