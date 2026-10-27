<?php

/** Mismo proceso activo que seleccionan configuración y la postulación del usuario. */
function idProcesoActivoBeca(mysqli $conn): int
{
    $resultado = $conn->query(
        "SELECT id FROM procesos_beca WHERE activo = 1 ORDER BY id DESC LIMIT 1",
    );
    $proceso = $resultado->fetch_assoc();
    // Sin proceso activo, los listados deben quedar vacíos.
    return (int) ($proceso["id"] ?? 0);
}
