<?php
// Mostrar el proceso de la pantalla; si no hay selección, usar el proceso activo.
$procesoVisibleModulo = $procesoSegundo ?? ($proceso ?? null);
if (!is_array($procesoVisibleModulo) || !isset($procesoVisibleModulo["nombre"])) {
    $idVisibleModulo = (int) ($detalleRendicion["proceso_id"] ?? ($postulacion["proceso_id"] ?? 0));
    $consultaVisibleModulo = $conn->prepare(
        $idVisibleModulo
            ? "SELECT nombre FROM procesos_beca WHERE id=? LIMIT 1"
            : "SELECT nombre FROM procesos_beca WHERE activo=1 ORDER BY id DESC LIMIT 1",
    );
    if ($idVisibleModulo) {
        $consultaVisibleModulo->bind_param("i", $idVisibleModulo);
    }
    $consultaVisibleModulo->execute();
    $procesoVisibleModulo = $consultaVisibleModulo->get_result()->fetch_assoc();
    $consultaVisibleModulo->close();
}

// En convocatorias de solo rendición no existen las etapas de inicio ni
// postulación para los perfiles administrativos. La regla se aplica desde
// este componente, compartido por todas sus vistas.
$procesoActivoNavegacion = $conn
    ->query(
        "SELECT tipo_proceso FROM procesos_beca WHERE activo=1 ORDER BY id DESC LIMIT 1",
    )
    ->fetch_assoc();
$ocultarNavegacionPostulacion =
    ($procesoActivoNavegacion["tipo_proceso"] ?? "") === "solo_rendicion" &&
    in_array($_SESSION["rol"] ?? "", ["administrador", "superior"], true);
?>
<?php if ($ocultarNavegacionPostulacion): ?>
<style>
    .header .nav a[href="inicio.php"],
    .header .nav a[href="postulaciones.php"] {
        display: none;
    }
</style>
<?php endif; ?>
<p class="proceso-modulo">Proceso: <strong><?= htmlspecialchars(
    (string) ($procesoVisibleModulo["nombre"] ?? "Sin proceso activo"),
    ENT_QUOTES,
    "UTF-8",
) ?></strong></p>
