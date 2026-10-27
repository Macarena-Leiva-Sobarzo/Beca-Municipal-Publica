<?php
/**
 * Datos preparados por config/modulo_segundo_semestre.php.
 * @var bool $esUsuarioSegundo
 * @var string $rolSegundo
 * @var array|null $procesoSegundo
 * @var int $procesoSegundoId
 * @var string $errorSegundo
 * @var bool $habilitadoSegundo
 * @var array|null $certificadoSegundo
 * @var bool $abiertoSegundo
 * @var DateTimeImmutable|null $fechaSegundo
 * @var array $listadoSegundo
 * @var array $pendientesSegundo
 * @var string $mensajeSegundo
 * @var string $buscarSegundo
 * @var array $estadisticasSegundo
 * @var int $totalSegundo
 * @var int $totalGeneralSegundo
 * @var int $paginaSegundo
 * @var int $paginasSegundo
 * @var int $offsetSegundo
 * @var float|int $avanceSegundo
 * @var Closure(int): string $urlPaginaSegundo
 */

// La vista se abre desde el controlador del perfil, que prepara los datos.
if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {
    require_once __DIR__ . "/../config/sesion.php";
    $rolAccesoSegundo = $_SESSION["rol"] ?? "";
    if (
        !empty($_SESSION["usuario_id"]) &&
        in_array($rolAccesoSegundo, ["usuario", "administrador", "superior"], true)
    ) {
        header("Location: ../" . $rolAccesoSegundo . "/segundo_semestre.php");
    } else {
        header("Location: ../auth/login.php");
    }
    exit();
}

$eSegundo = static fn(mixed $valor): string => htmlspecialchars(
    (string) $valor,
    ENT_QUOTES,
    "UTF-8",
);
$textoEstadoSegundo = static fn(?string $estado): string => match ($estado) {
    "validado" => "Aceptado",
    "rechazado" => "Rechazado",
    default => "Pendiente de revisión",
};
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificado de segundo semestre | Becas Estudiantiles</title>
    <link rel="stylesheet" href="../css/estilos.css?v=mobile-20261020&certificados-centrado-20261019">
</head>

<body>
    <header class="header">

        <div class="logo">
            Becas Estudiantiles
        </div>


        <?php if ($esUsuarioSegundo): ?>
            <?php require __DIR__ . "/usuario/menu.php"; ?>
        <?php else: ?>
        <nav class="nav">
            <span class="sesion-rol">Rol: <?php echo htmlspecialchars(
                (string) ($_SESSION["rol"] ?? ""),
                ENT_QUOTES,
                "UTF-8",
            ); ?></span>

            <a href="inicio.php">
                Inicio
            </a>

            <a href="postulaciones.php">
                Postulaciones
            </a>

            <a href="rendiciones.php">
                Rendiciones
            </a>

            <?php if ($rolSegundo === "superior"): ?>
            <a href="configuracion.php">
                Configuración
            </a>
            <?php endif; ?>

            <a href="archivos_descargar.php">
                Descargar
            </a>

            <a href="historial.php">
                Historial
            </a>

            <a href="segundo_semestre.php" aria-current="page">
                Segundo semestre
            </a>

            <a href="../auth/cerrar_sesion.php">
                Cerrar sesión
            </a>

        </nav>
        <?php endif; ?>

    </header>

    <?php $esperaCertificadoSegundo =
        $esUsuarioSegundo && $habilitadoSegundo && !$abiertoSegundo; ?>
    <?php $resultadoCertificadoSegundo =
        $esUsuarioSegundo &&
        $habilitadoSegundo &&
        $certificadoSegundo &&
        in_array($certificadoSegundo["estado"], ["pendiente", "validado", "rechazado"], true); ?>
    <?php $certificadoSegundoConEncabezado =
        $esUsuarioSegundo && ($esperaCertificadoSegundo || $resultadoCertificadoSegundo); ?>
    <main class="<?php echo $certificadoSegundoConEncabezado
        ? "resultado resultado-con-encabezado"
        : ($esUsuarioSegundo
            ? "admin-contenedor segundo-semestre segundo-usuario"
            : "admin-rendiciones segundo-semestre segundo-administracion"); ?>">
        <?php if ($certificadoSegundoConEncabezado): ?>
            <div class="modulo-usuario-encabezado">
                <h1>Certificado de alumno regular</h1>
                <?php require __DIR__ . "/proceso_modulo.php"; ?>
                <p>Aquí debes subir el certificado de alumno regular correspondiente al segundo semestre para el pago de la segunda cuota.</p>
            </div>
        <?php endif; ?>

        <?php if ($esperaCertificadoSegundo): ?>
            <section class="resultado-box" role="status">
                <div class="resultado-icono revision" aria-hidden="true">⏳</div>
                <h1>Certificado de segundo semestre aún no disponible</h1>
                <p>La carga del certificado todavía no está disponible.</p>
                <?php if ($fechaSegundo): ?>
                    <div class="monto-beca">
                        <span>Inicio de carga del certificado</span>
                        <strong style="font-size: 24px;"><?php echo $eSegundo(
                            $fechaSegundo->format("d/m/Y H:i"),
                        ); ?></strong>
                    </div>
                <?php else: ?>
                    <p>La fecha de apertura todavía no ha sido configurada.</p>
                <?php endif; ?>
            </section>
        <?php else: ?>
        <?php if (!$resultadoCertificadoSegundo && !$esUsuarioSegundo): ?>
        <div class="portal-encabezado">
            <h1>Revisar certificados de segundo semestre</h1>
        <?php require __DIR__ . "/proceso_modulo.php"; ?>
            <p>Revisa los certificados de alumno regular para el segundo pago.</p>
        </div>
        <?php elseif (!$resultadoCertificadoSegundo && $esUsuarioSegundo): ?>
        <div class="portal-encabezado">
            <h1>Certificado de alumno regular</h1>
        <?php require __DIR__ . "/proceso_modulo.php"; ?>
            <p>Aquí debes subir el certificado de alumno regular correspondiente al segundo semestre para el pago de la segunda cuota.</p>
        </div>
        <?php endif; ?>
        <?php if ($errorSegundo): ?><div class="mensaje error" role="alert"><?php echo $eSegundo(
    $errorSegundo,
); ?></div><?php endif; ?>
        <?php if (
            !$esUsuarioSegundo &&
            $mensajeSegundo !== ""
        ): ?><div class="mensaje exito" role="status"><?php echo $eSegundo(
    $mensajeSegundo,
); ?></div><?php endif; ?>
        <?php if (!$procesoSegundo): ?>
            <section class="portal-card">
                <h2>Sin proceso activo</h2>
                <p>Activa un proceso en configuración para utilizar este módulo.</p>
            </section>
        <?php elseif ($esUsuarioSegundo): ?>
            <section class="<?php echo $resultadoCertificadoSegundo
                ? "resultado-box"
                : "portal-card"; ?>">
                <?php if (!$habilitadoSegundo): ?>
                    <h2>Certificado no disponible</h2>
                    <p>Este módulo está disponible para estudiantes beneficiados del proceso activo, una vez publicados los resultados.</p>
                <?php else: ?>
                    <?php if ($certificadoSegundo): ?>
                        <?php if ($resultadoCertificadoSegundo): ?>
                            <div class="resultado-icono <?php echo $certificadoSegundo["estado"] ===
                            "pendiente"
                                ? "revision"
                                : ($certificadoSegundo["estado"] === "validado"
                                    ? "exito"
                                    : "error"); ?>" aria-hidden="true"><?php echo $certificadoSegundo[
    "estado"
] === "pendiente"
    ? "⏳"
    : ($certificadoSegundo["estado"] === "validado"
        ? "✓"
        : "×"); ?></div>
                            <h1><?php echo $certificadoSegundo["estado"] === "pendiente"
                                ? "Pendiente de revisión"
                                : ($certificadoSegundo["estado"] === "validado"
                                    ? "¡Certificado aceptado!"
                                    : "Certificado rechazado"); ?></h1>
                            <?php if (
                                $certificadoSegundo["estado"] === "pendiente"
                            ): ?><p>Tu certificado fue enviado y está pendiente de revisión por el personal municipal.</p><?php endif; ?>
                        <?php else: ?>
                            <h2><?php echo $eSegundo(
                                $textoEstadoSegundo($certificadoSegundo["estado"]),
                            ); ?></h2>
                        <?php endif; ?>
                        <p>Archivo: <?php echo $eSegundo(
                            $certificadoSegundo["nombre_original"],
                        ); ?></p>
                        <?php if (
                            $certificadoSegundo["observacion"]
                        ): ?><p class="segundo-observacion">Observación: <?php echo $eSegundo(
    $certificadoSegundo["observacion"],
); ?></p><?php endif; ?>
                        <?php if (
                            $certificadoSegundo["estado"] === "validado"
                        ): ?><p>Tu certificado fue aceptado para la revisión del segundo pago.</p><?php endif; ?>
                        <?php if (
                            $certificadoSegundo["estado"] === "rechazado"
                        ): ?><p>Corrige el documento y vuelve a subirlo.</p><?php endif; ?>
                        <a class="btn-portal segundo-ver-certificado" href="?documento=<?php echo (int) $certificadoSegundo[
                            "id"
                        ]; ?>" aria-haspopup="dialog">Ver certificado</a>
                    <?php endif; ?>
                    <?php if (!$abiertoSegundo): ?>
                        <h2>Carga aún no disponible</h2>
                        <p><?php echo $fechaSegundo
                            ? "Podrás subir el certificado desde el " .
                                $eSegundo($fechaSegundo->format("d/m/Y H:i")) .
                                "."
                            : "El superior aún no ha configurado la fecha de apertura."; ?></p>
                    <?php elseif (
                        !$certificadoSegundo ||
                        $certificadoSegundo["estado"] === "rechazado"
                    ): ?>
                        <form method="post" enctype="multipart/form-data" class="segundo-formulario segundo-carga-certificado">
                            <input type="hidden" name="proceso_id" value="<?php echo (int) $procesoSegundoId; ?>">
                            <input type="hidden" name="csrf" value="<?php echo $eSegundo(
                                $_SESSION["csrf_segundo"],
                            ); ?>">
                            <input type="hidden" name="accion" value="subir">
                            <div class="campo-form"><label for="certificado">Certificado de alumno regular del segundo semestre <span class="obligatorio">*</span></label>
                                <input id="certificado" type="file" name="certificado" accept=".pdf,application/pdf" required>
                                <p class="campo-ayuda">Solo PDF con contenido. Máximo 5 MB. La carga no tiene fecha de término.</p>
                            </div>
                            <button class="btn-formulario-principal" type="submit">Enviar certificado</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <div class="admin-estadisticas segundo-estadisticas">
                <div class="admin-stat stat-total"><span>Postulantes</span><strong><?php echo (int) $estadisticasSegundo[
                    "postulantes"
                ]; ?></strong><small>Beneficiarios del proceso activo</small></div>
                <div class="admin-stat stat-beneficiados"><span>Recibidos</span><strong><?php echo (int) $estadisticasSegundo[
                    "recibidos"
                ]; ?></strong><small>Certificados enviados</small></div>
                <button type="button" class="admin-stat stat-revision segundo-pendientes-boton" id="abrirPendientesSegundo" aria-haspopup="dialog" aria-controls="pendientesSegundoModal"><span>Pendientes</span><strong><?php echo (int) $estadisticasSegundo[
                    "pendientes"
                ]; ?></strong><small>Beneficiarios sin certificado</small></button>
                <div class="admin-stat stat-total"><span>Avance</span><strong><?php echo $eSegundo(
                    $avanceSegundo,
                ); ?>%</strong><small>Certificados recibidos sobre beneficiarios</small></div>
            </div>
            <section class="segundo-listado">
                <h2>Certificados recibidos</h2>
                <div class="admin-rendiciones-filtros segundo-filtros">
                    <form method="get" action="segundo_semestre.php">
                        <div class="campo"><input type="text" name="buscar" aria-label="Buscar certificados por nombre, RUT o correo" placeholder="Buscar por nombre, RUT o correo" value="<?php echo $eSegundo(
                            $buscarSegundo,
                        ); ?>"></div>
                        <button type="submit" class="admin-btn-buscar">Buscar</button>
                        <a href="segundo_semestre.php" class="admin-btn-limpiar">Limpiar</a>
                    </form>
                </div>
                <div class="admin-listado-resumen">Mostrando <strong><?php echo count(
                    $listadoSegundo,
                ) > 0
                    ? $offsetSegundo + 1
                    : 0; ?></strong> a <strong><?php echo $offsetSegundo +
    count(
        $listadoSegundo,
    ); ?></strong> de <strong><?php echo $totalSegundo; ?></strong> registros. · Total general: <strong><?php echo $totalGeneralSegundo; ?></strong></div>
                <div class="admin-tabla-contenedor">
                    <table class="admin-rendiciones-tabla segundo-tabla">
                        <thead><tr><th>N°</th><th>RUT</th><th>Nombre</th><th>Teléfono</th><th>Carrera</th><th>Institución</th><th>Estado</th><th>Adjunto</th><th>Fecha carga</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php if (
                            !$listadoSegundo
                        ): ?><tr><td colspan="10"><?php echo $buscarSegundo !== ""
    ? "No hay certificados que coincidan con la búsqueda."
    : "No hay certificados recibidos para este proceso."; ?></td></tr><?php endif; ?>
                        <?php foreach ($listadoSegundo as $indiceSegundo => $filaSegundo): ?>
                            <tr>
                                <td><?php echo $offsetSegundo + $indiceSegundo + 1; ?></td>
                                <td><span class="rut-completo"><?php echo $eSegundo(
                                    $filaSegundo["rut"],
                                ); ?></span></td>
                                <td><span class="admin-persona-nombre"><?php echo $eSegundo(
                                    trim(
                                        $filaSegundo["nombres"] .
                                            " " .
                                            $filaSegundo["apellido_paterno"] .
                                            " " .
                                            $filaSegundo["apellido_materno"],
                                    ),
                                ); ?></span><span class="admin-persona-correo"><?php echo $eSegundo(
    $filaSegundo["correo"] ?: "Sin correo registrado",
); ?></span></td>
                                <td><?php echo $eSegundo(
                                    $filaSegundo["telefono"] ?: "Sin información",
                                ); ?></td>
                                <td><?php echo $eSegundo(
                                    $filaSegundo["carrera"] ?: "Sin información",
                                ); ?></td>
                                <td><?php echo $eSegundo(
                                    $filaSegundo["institucion"] ?: "Sin información",
                                ); ?></td>
                                <td><span class="segundo-estado <?php echo $eSegundo(
                                    $filaSegundo["estado"],
                                ); ?>"><?php echo $eSegundo(
    $textoEstadoSegundo($filaSegundo["estado"]),
); ?></span></td>
                                <td><?php echo $eSegundo($filaSegundo["nombre_original"]); ?></td>
                                <td><?php echo $eSegundo(
                                    (new DateTimeImmutable($filaSegundo["fecha_subida"]))->format(
                                        "d/m/Y H:i",
                                    ),
                                ); ?></td>
                                <td>
                                    <div class="segundo-acciones">
                                        <a class="segundo-ver" data-certificado="<?php echo (int) $filaSegundo[
                                            "id"
                                        ]; ?>" data-estudiante="<?php echo $eSegundo(
    trim(
        $filaSegundo["nombres"] .
            " " .
            $filaSegundo["apellido_paterno"] .
            " " .
            $filaSegundo["apellido_materno"],
    ),
); ?>" href="?documento=<?php echo (int) $filaSegundo[
    "id"
]; ?>" aria-haspopup="dialog" title="Vista previa del certificado" aria-label="Vista previa del certificado"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M2 12s3-6 10-6 10 6 10 6-3 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg></a>
                                        <a class="segundo-descargar" href="?documento=<?php echo (int) $filaSegundo[
                                            "id"
                                        ]; ?>&amp;descargar=1" title="Descargar certificado" aria-label="Descargar certificado"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 3v13m-5-5 5 5 5-5M5 19h14" fill="none" stroke="currentColor" stroke-width="2"/></svg></a>
                                    </div>
                                    <template id="revision-certificado-<?php echo (int) $filaSegundo[
                                        "id"
                                    ]; ?>">
                                        <div class="segundo-revision-cabecera">
                                            <h3>📝 Revisión del certificado</h3>
                                            <button type="button" class="btn-cerrar-pdf segundo-cerrar-revision" aria-label="Cerrar panel de revisión">×</button>
                                        </div>
                                        <p class="segundo-revision-estado">Estado actual: <?php
                                        echo $eSegundo($textoEstadoSegundo($filaSegundo["estado"]));
                                        if (
                                            $filaSegundo["fecha_revision"]
                                        ): ?> · Revisado por: <?php echo $eSegundo(
     $filaSegundo["nombre_revisor"] ?: "Personal municipal",
 ); ?> · <?php echo $eSegundo(
     (new DateTimeImmutable($filaSegundo["fecha_revision"]))->format("d/m/Y H:i"),
 );endif;
                                        ?></p>
                                        <form method="post" class="segundo-formulario">
                                            <input type="hidden" name="pagina" value="<?php echo (int) $paginaSegundo; ?>">
                                            <input type="hidden" name="buscar" value="<?php echo $eSegundo(
                                                $buscarSegundo,
                                            ); ?>">
                                            <input type="hidden" name="proceso_id" value="<?php echo (int) $procesoSegundoId; ?>">
                                            <input type="hidden" name="csrf" value="<?php echo $eSegundo(
                                                $_SESSION["csrf_segundo"],
                                            ); ?>">
                                            <input type="hidden" name="accion" value="revisar">
                                            <input type="hidden" name="id" value="<?php echo (int) $filaSegundo[
                                                "id"
                                            ]; ?>">
                                            <label for="revisor-modal-<?php echo (int) $filaSegundo[
                                                "id"
                                            ]; ?>">Revisado por</label>
                                            <input id="revisor-modal-<?php echo (int) $filaSegundo[
                                                "id"
                                            ]; ?>" type="text" value="<?php echo $eSegundo(
    $filaSegundo["revisor_id"]
        ? ($filaSegundo["nombre_revisor"] ?:
        "Nombre no registrado")
        : "Sin revisión",
); ?>" readonly>
                                            <label for="observacion-modal-<?php echo (int) $filaSegundo[
                                                "id"
                                            ]; ?>">Observación</label>
                                            <textarea name="observacion" id="observacion-modal-<?php echo (int) $filaSegundo[
                                                "id"
                                            ]; ?>" maxlength="2000" rows="5" placeholder="Observación. Obligatoria al rechazar."><?php echo $eSegundo(
    $filaSegundo["observacion"] ?? "",
); ?></textarea>
                                            <div class="segundo-revision-botones">
                                                <button type="submit" name="estado" value="validado" class="segundo-validar">✓ Validar</button>
                                                <button type="submit" name="estado" value="rechazado" class="segundo-rechazar">✕ Rechazar</button>
                                            </div>
                                        </form>
                                        <div class="segundo-revision-historial">
                                            <h4>Historial</h4>
                                            <?php if ($filaSegundo["fecha_revision"]): ?>
                                                <p><strong><?php echo $eSegundo(
                                                    $textoEstadoSegundo($filaSegundo["estado"]),
                                                ); ?></strong> · <?php echo $eSegundo(
    (new DateTimeImmutable($filaSegundo["fecha_revision"]))->format("d/m/Y H:i:s"),
); ?><br>Revisado por: <?php echo $eSegundo(
    $filaSegundo["nombre_revisor"] ?: "Personal municipal",
); ?></p>
                                            <?php else: ?><p>Sin revisiones registradas.</p><?php endif; ?>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="postulaciones-paginacion" aria-label="Páginas de certificados recibidos">
                    <?php if (
                        $paginaSegundo > 1
                    ): ?><a class="postulaciones-paginacion-control" href="<?php echo $eSegundo(
    $urlPaginaSegundo($paginaSegundo - 1),
); ?>">‹ Anterior</a><?php else: ?><span class="postulaciones-paginacion-control deshabilitado">‹ Anterior</span><?php endif; ?>
                    <div class="postulaciones-paginacion-paginas">
                        <?php
                        $anteriorPaginaSegundo = 0;
                        for (
                            $numeroSegundo = 1;
                            $numeroSegundo <= $paginasSegundo;
                            $numeroSegundo++
                        ):

                            if (
                                $numeroSegundo !== 1 &&
                                $numeroSegundo !== $paginasSegundo &&
                                abs($numeroSegundo - $paginaSegundo) > 2
                            ) {
                                continue;
                            }
                            if (
                                $anteriorPaginaSegundo &&
                                $numeroSegundo > $anteriorPaginaSegundo + 1
                            ): ?><span class="postulaciones-paginacion-puntos">…</span><?php endif;
                            $anteriorPaginaSegundo = $numeroSegundo;
                            ?>
                            <?php if (
                                $numeroSegundo === $paginaSegundo
                            ): ?><span class="postulaciones-paginacion-numero activo" aria-current="page"><?php echo $numeroSegundo; ?></span><?php else: ?><a class="postulaciones-paginacion-numero" href="<?php echo $eSegundo(
    $urlPaginaSegundo($numeroSegundo),
); ?>"><?php echo $numeroSegundo; ?></a><?php endif; ?>
                        <?php
                        endfor;
                        ?>
                    </div>
                    <?php if (
                        $paginaSegundo < $paginasSegundo
                    ): ?><a class="postulaciones-paginacion-control" href="<?php echo $eSegundo(
    $urlPaginaSegundo($paginaSegundo + 1),
); ?>">Siguiente ›</a><?php else: ?><span class="postulaciones-paginacion-control deshabilitado">Siguiente ›</span><?php endif; ?>
                </nav>
                <p class="historial-pagina-actual">Página <?php echo $paginaSegundo; ?> de <?php echo $paginasSegundo; ?></p>
            </section>
        <?php endif; ?>
        <?php endif; ?>
    </main>
    <footer class="footer">
        <p>© <?php echo date("Y"); ?> Becas Estudiantiles</p>
    </footer>
<dialog id="vistaCertificadoSegundo" class="segundo-modal" aria-labelledby="tituloCertificadoSegundo">
            <div class="modal-pdf-header">
                <div><h2 id="tituloCertificadoSegundo">Certificado de segundo semestre</h2><p id="estudianteCertificadoSegundo"></p></div>
                <div style="display:flex;align-items:center;gap:10px">
                    <a id="descargarCertificadoSegundo" class="btn-cerrar-pdf" download title="Descargar certificado" aria-label="Descargar certificado" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M5 17v4h14v-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                    <button type="button" class="btn-cerrar-pdf" id="cerrarCertificadoSegundo" aria-label="Cerrar vista previa">×</button>
                </div>
            </div>
            <div class="segundo-modal-cuerpo"><iframe id="pdfCertificadoSegundo" title="Vista previa del certificado de segundo semestre"></iframe><aside id="revisionCertificadoSegundo" class="segundo-revision"></aside></div>
        </dialog>
        <?php if (!$esUsuarioSegundo): ?>
            <dialog id="pendientesSegundoModal" class="segundo-modal-pendientes" aria-labelledby="tituloPendientesSegundo">
                <div class="segundo-pendientes-cabecera">
                    <div>
                        <h2 id="tituloPendientesSegundo">Certificados pendientes</h2>
                        <p>Estudiantes que aún no han enviado su certificado de segundo semestre.</p>
                    </div>
                    <button type="button" class="btn-cerrar-pdf" id="cerrarPendientesSegundo" aria-label="Cerrar listado de pendientes">×</button>
                </div>
                <div class="segundo-pendientes-contenido">
                    <div class="admin-listado-resumen segundo-pendientes-resumen" id="resumenPendientesSegundo"></div>
                    <div class="segundo-pendientes-tabla-contenedor">
                        <table class="segundo-pendientes-tabla">
                            <thead>
                                <tr><th>ID</th><th>RUT</th><th>Nombre completo</th><th>Correo</th><th>Teléfono</th></tr>
                            </thead>
                            <tbody id="listaPendientesSegundo">
                                <?php if (!$pendientesSegundo): ?>
                                    <tr><td colspan="5">No hay estudiantes con certificados pendientes.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($pendientesSegundo as $pendienteSegundo): ?>
                                    <tr class="pendiente-segundo-fila">
                                        <td><?php echo (int) $pendienteSegundo["estudiante_id"]; ?></td>
                                        <td><span class="rut-completo"><?php echo $eSegundo($pendienteSegundo["rut"] ?: "Sin RUT registrado"); ?></span></td>
                                        <td><?php echo $eSegundo($pendienteSegundo["nombre_completo"] ?: "Sin nombre registrado"); ?></td>
                                        <td><?php echo $eSegundo($pendienteSegundo["correo"] ?: "Sin correo registrado"); ?></td>
                                        <td><?php echo $eSegundo($pendienteSegundo["telefono"] ?: "Sin teléfono registrado"); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="segundo-pendientes-paginacion" id="paginacionPendientesSegundo" aria-label="Paginación de certificados pendientes" hidden>
                        <button type="button" id="anteriorPendientesSegundo">‹ Anterior</button>
                        <div id="numerosPendientesSegundo"></div>
                        <button type="button" id="siguientePendientesSegundo">Siguiente ›</button>
                    </nav>
                    <p class="segundo-pendientes-pagina-actual" id="paginaActualPendientesSegundo" hidden></p>
                </div>
            </dialog>
        <?php endif; ?>
        <script>
            const modalCertificado = document.getElementById('vistaCertificadoSegundo');
            const frameCertificado = document.getElementById('pdfCertificadoSegundo');
            let enlaceCertificado;
            document.querySelectorAll('.segundo-ver, .segundo-ver-certificado').forEach(enlace => {
                enlace.addEventListener('click', event => {
                    event.preventDefault();
                    enlaceCertificado = enlace;
                    frameCertificado.src = enlace.href;
                    const descargaCertificado = new URL(enlace.href);
                    descargaCertificado.searchParams.set('descargar', '1');
                    document.getElementById('descargarCertificadoSegundo').href = descargaCertificado.href;
                    document.getElementById('estudianteCertificadoSegundo').textContent = enlace.dataset.estudiante || '';
                    const panelRevision = document.getElementById('revisionCertificadoSegundo');
                    const plantillaRevision = document.getElementById('revision-certificado-' + enlace.dataset.certificado);
                    panelRevision.hidden = !plantillaRevision;
                    if (plantillaRevision) {
                    panelRevision.replaceChildren(plantillaRevision.content.cloneNode(true));
                    panelRevision.querySelector('.segundo-cerrar-revision').addEventListener('click', () => { panelRevision.hidden = true; });
                    panelRevision.querySelector('form')?.addEventListener('submit', event => {
                        const observacion = panelRevision.querySelector('textarea');
                        observacion.setCustomValidity(event.submitter?.value === 'rechazado' && !observacion.value.trim() ? 'Indica el motivo del rechazo.' : '');
                        if (!observacion.reportValidity()) event.preventDefault();
                    });
                    panelRevision.querySelector('textarea')?.addEventListener('input', event => event.target.setCustomValidity(''));
                    }
                    modalCertificado.showModal();
                    document.body.classList.add('modal-abierto');
                });
            });
            document.getElementById('cerrarCertificadoSegundo').addEventListener('click', () => modalCertificado.close());
            modalCertificado.addEventListener('click', event => {
                const limites = modalCertificado.getBoundingClientRect();
                if (event.target === modalCertificado && (
                    event.clientX < limites.left || event.clientX > limites.right ||
                    event.clientY < limites.top || event.clientY > limites.bottom
                )) modalCertificado.close();
            });
            modalCertificado.addEventListener('close', () => {
                frameCertificado.removeAttribute('src');
                document.body.classList.remove('modal-abierto');
                enlaceCertificado?.focus();
            });

            const modalPendientesSegundo = document.getElementById('pendientesSegundoModal');
            const botonPendientesSegundo = document.getElementById('abrirPendientesSegundo');
            const cerrarPendientesSegundo = document.getElementById('cerrarPendientesSegundo');

            if (modalPendientesSegundo && botonPendientesSegundo && cerrarPendientesSegundo) {
                const filasPendientesSegundo = Array.from(document.querySelectorAll('.pendiente-segundo-fila'));
                const porPaginaPendientesSegundo = 10;
                const totalPaginasPendientesSegundo = Math.max(1, Math.ceil(filasPendientesSegundo.length / porPaginaPendientesSegundo));
                const paginacionPendientesSegundo = document.getElementById('paginacionPendientesSegundo');
                const paginaActualPendientesSegundo = document.getElementById('paginaActualPendientesSegundo');
                const anteriorPendientesSegundo = document.getElementById('anteriorPendientesSegundo');
                const siguientePendientesSegundo = document.getElementById('siguientePendientesSegundo');
                const numerosPendientesSegundo = document.getElementById('numerosPendientesSegundo');
                const resumenPendientesSegundo = document.getElementById('resumenPendientesSegundo');
                let paginaPendientesSegundo = 1;

                const mostrarPaginaPendientesSegundo = pagina => {
                    paginaPendientesSegundo = Math.min(Math.max(pagina, 1), totalPaginasPendientesSegundo);

                    filasPendientesSegundo.forEach((fila, indice) => {
                        fila.hidden = Math.floor(indice / porPaginaPendientesSegundo) + 1 !== paginaPendientesSegundo;
                    });

                    anteriorPendientesSegundo.disabled = paginaPendientesSegundo === 1;
                    siguientePendientesSegundo.disabled = paginaPendientesSegundo === totalPaginasPendientesSegundo;
                    paginaActualPendientesSegundo.textContent = `Página ${paginaPendientesSegundo} de ${totalPaginasPendientesSegundo}`;
                    const inicioPendientesSegundo = filasPendientesSegundo.length
                        ? (paginaPendientesSegundo - 1) * porPaginaPendientesSegundo + 1
                        : 0;
                    const finPendientesSegundo = Math.min(
                        paginaPendientesSegundo * porPaginaPendientesSegundo,
                        filasPendientesSegundo.length,
                    );
                    resumenPendientesSegundo.innerHTML = `Mostrando <strong>${inicioPendientesSegundo}</strong> a <strong>${finPendientesSegundo}</strong> de <strong>${filasPendientesSegundo.length}</strong> registros. · Total general: <strong>${filasPendientesSegundo.length}</strong>`;
                    numerosPendientesSegundo.replaceChildren();

                    let numeroAnteriorPendientesSegundo = 0;

                    for (let numero = 1; numero <= totalPaginasPendientesSegundo; numero += 1) {
                        if (
                            numero !== 1 &&
                            numero !== totalPaginasPendientesSegundo &&
                            Math.abs(numero - paginaPendientesSegundo) > 2
                        ) {
                            continue;
                        }

                        if (numeroAnteriorPendientesSegundo && numero > numeroAnteriorPendientesSegundo + 1) {
                            const puntos = document.createElement('span');
                            puntos.textContent = '…';
                            puntos.setAttribute('aria-hidden', 'true');
                            numerosPendientesSegundo.appendChild(puntos);
                        }

                        const boton = document.createElement('button');
                        boton.type = 'button';
                        boton.textContent = numero;
                        boton.className = numero === paginaPendientesSegundo ? 'activo' : '';
                        boton.setAttribute('aria-label', `Ir a la página ${numero}`);
                        boton.setAttribute('aria-current', numero === paginaPendientesSegundo ? 'page' : 'false');
                        boton.addEventListener('click', () => mostrarPaginaPendientesSegundo(numero));
                        numerosPendientesSegundo.appendChild(boton);
                        numeroAnteriorPendientesSegundo = numero;
                    }
                };

                if (filasPendientesSegundo.length > 0) {
                    paginacionPendientesSegundo.hidden = false;
                    paginaActualPendientesSegundo.hidden = false;
                    mostrarPaginaPendientesSegundo(1);
                    anteriorPendientesSegundo.addEventListener('click', () => mostrarPaginaPendientesSegundo(paginaPendientesSegundo - 1));
                    siguientePendientesSegundo.addEventListener('click', () => mostrarPaginaPendientesSegundo(paginaPendientesSegundo + 1));
                }

                botonPendientesSegundo.addEventListener('click', () => {
                    mostrarPaginaPendientesSegundo(1);
                    modalPendientesSegundo.showModal();
                    document.body.classList.add('modal-abierto');
                });

                cerrarPendientesSegundo.addEventListener('click', () => modalPendientesSegundo.close());

                modalPendientesSegundo.addEventListener('click', event => {
                    const limites = modalPendientesSegundo.getBoundingClientRect();
                    if (event.target === modalPendientesSegundo && (
                        event.clientX < limites.left || event.clientX > limites.right ||
                        event.clientY < limites.top || event.clientY > limites.bottom
                    )) modalPendientesSegundo.close();
                });

                modalPendientesSegundo.addEventListener('close', () => {
                    document.body.classList.remove('modal-abierto');
                    botonPendientesSegundo.focus();
                });
            }
        </script>
</body>

</html>
