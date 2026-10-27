<?php

/**
 * Variables preparadas por el controlador o la vista que incluye este archivo.
 * @var mysqli $conn
 */
// Un mismo menú para todas las pantallas del postulante.
require_once __DIR__ . "/../../config/reglas_beca.php";
$procesoMenuUsuario = $conn
    ->query("SELECT id,tipo_proceso FROM procesos_beca WHERE activo=1 ORDER BY id DESC LIMIT 1")
    ->fetch_assoc();
$usuarioMenuId = (int) ($_SESSION["usuario_id"] ?? 0);
$consultaMenuUsuario = $conn->prepare("SELECT p.estado, pb.fecha_resultados, pb.fecha_inicio_rendicion, pb.fecha_cierre_rendicion
    FROM postulaciones p JOIN procesos_beca pb ON pb.id=p.proceso_id
    WHERE p.usuario_id=? AND pb.id=(SELECT id FROM procesos_beca WHERE activo=1 ORDER BY id DESC LIMIT 1)
    ORDER BY p.id DESC LIMIT 1");
$consultaMenuUsuario->bind_param("i", $usuarioMenuId);
$consultaMenuUsuario->execute();
$postulacionMenuUsuario = $consultaMenuUsuario->get_result()->fetch_assoc();
$consultaMenuUsuario->close();
$beneficiadoMenuUsuario =
    $postulacionMenuUsuario &&
    $postulacionMenuUsuario["estado"] === "beneficiado" &&
    resultadoBecaPublicado($postulacionMenuUsuario);
$moduloMenuUsuario = basename($_SERVER["SCRIPT_FILENAME"] ?? "", ".php");
$moduloMenuUsuario = match ($moduloMenuUsuario) {
    "formulario_postulacion", "comprobante" => "postulacion",
    "comprobante_rendicion", "aviso_rendicion" => "rendicion",
    default => $moduloMenuUsuario,
};
$enlacesMenuUsuario = ["inicio" => "Portal"];
if (($procesoMenuUsuario["tipo_proceso"] ?? "") === "solo_rendicion") {
    $enlacesMenuUsuario["segundo_semestre"] = "Segundo semestre";
    $enlacesMenuUsuario["rendicion"] = "Rendición";
} else {
    $enlacesMenuUsuario["postulacion"] = "Postulación";
    $enlacesMenuUsuario["resultado"] = "Resultado";
    if ($beneficiadoMenuUsuario) {
        $enlacesMenuUsuario["segundo_semestre"] = "Segundo semestre";
        $enlacesMenuUsuario["rendicion"] = "Rendición";
    }
}
?>
<nav class="nav" aria-label="Navegación principal">
    <?php foreach ($enlacesMenuUsuario as $rutaMenuUsuario => $textoMenuUsuario): ?>
        <a href="<?= $rutaMenuUsuario ?>.php" <?= $moduloMenuUsuario === $rutaMenuUsuario
                                                    ? ' aria-current="page"'
                                                    : "" ?>><?= $textoMenuUsuario ?></a>
    <?php endforeach; ?>
    <a href="../auth/cerrar_sesion.php">Cerrar sesión</a>
</nav>
<script src="../js/documentos_usuario.js?v=instrucciones-20261019" defer></script>
