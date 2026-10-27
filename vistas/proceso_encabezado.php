<?php
/** Nombre del proceso correspondiente a la pantalla o a la rendición consultada. */
$nombreProcesoEncabezado = trim(
    (string) ($detalleRendicion["proceso_nombre"] ??
        ($proceso["nombre"] ?? "")),
);
if ($nombreProcesoEncabezado === "" && isset($conn)) {
    $idProcesoEncabezado = (int) ($rendicion["proceso_id"] ?? 0);
    if ($idProcesoEncabezado > 0) {
        $consultaProcesoEncabezado = $conn->prepare(
            "SELECT nombre FROM procesos_beca WHERE id = ? LIMIT 1",
        );
        $consultaProcesoEncabezado->bind_param("i", $idProcesoEncabezado);
        $consultaProcesoEncabezado->execute();
        $filaProcesoEncabezado = $consultaProcesoEncabezado
            ->get_result()
            ->fetch_assoc();
        $consultaProcesoEncabezado->close();
    } else {
        $resultadoProcesoEncabezado = $conn->query(
            "SELECT nombre FROM procesos_beca WHERE activo = 1 ORDER BY id DESC LIMIT 1",
        );
        $filaProcesoEncabezado = $resultadoProcesoEncabezado->fetch_assoc();
    }
    $nombreProcesoEncabezado = trim(
        (string) ($filaProcesoEncabezado["nombre"] ?? ""),
    );
}
?>
<p class="nombre-proceso-encabezado">Proceso: <strong><?= htmlspecialchars(
    $nombreProcesoEncabezado !== ""
        ? $nombreProcesoEncabezado
        : "Sin proceso disponible",
    ENT_QUOTES,
    "UTF-8",
) ?></strong></p>
