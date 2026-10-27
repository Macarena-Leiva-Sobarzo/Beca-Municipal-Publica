<?php
/**
 * Variables recibidas desde:
 * administrador/detalle_rendicion.php
 *
 * @var int $rendicionId
 * @var array $detalleRendicion
 *
 * @var array $gastosPrimerSemestre
 * @var array $gastosSegundoSemestre
 * @var array $todosLosGastos
 *
 * @var bool $realizoDevolucion
 * @var bool $comprobanteDevolucionAdjunto
 * @var string $estadoDevolucionAdmin
 * @var bool $revisionAdministrativaTerminada
 *
 * @var bool $devolucionAceptada
 * @var bool $devolucionRechazada
 * @var bool $devolucionPendiente
 *
 * @var int $totalBoletasDetalle
 * @var int $totalAceptadasDetalle
 * @var int $totalRechazadasDetalle
 * @var int $totalPendientesDetalle
 *
 * @var float $totalMontoDetalle
 * @var float $totalMontoAceptadoDetalle
 * @var float $totalMontoRechazadoDetalle
 *
 * @var string|null $mensajeExito
 * @var string|null $mensajeError
 */
?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Detalle de rendición
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=mobile-20261020&titulo-detalle-rendicion-20261006">

</head>

<body>


    <header class="header">

        <div class="logo">
            Becas Estudiantiles
        </div>


        <nav class="nav">
            <span class="sesion-rol">Rol: <?= htmlspecialchars(
                (string) ($_SESSION["rol"] ?? ""),
                ENT_QUOTES,
                "UTF-8",
            ) ?></span>

            <a href="inicio.php">
                Inicio
            </a>

            <a href="postulaciones.php">
                Postulaciones
            </a>

            <a href="rendiciones.php" aria-current="page">
                Rendiciones
            </a>

            <a href="archivos_descargar.php">
                Descargar
            </a>

            <a href="historial.php">
                Historial
            </a>
            <a href="segundo_semestre.php">Segundo semestre</a>


            <a href="../auth/cerrar_sesion.php">
                Cerrar sesión
            </a>

        </nav>

    </header>


    <main class="admin-rendiciones">
        <h1 class="admin-detalle-rendicion-titulo">Detalle de rendición</h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>


        <div class="admin-detalle-rendicion-volver">

            <a
                href="rendiciones.php"
                class="btn-volver-rendiciones">
                Volver a rendiciones
            </a>

        </div>


        <section class="admin-responsable-revision" aria-label="Responsable de la revisión">

            <span>Revisado por</span>

            <strong><?= e($detalleRendicion["nombre_revisor"] ?: "Sin revisión registrada") ?></strong>

            <?php if (!empty($detalleRendicion["fecha_revision"])): ?>

                <span class="admin-responsable-fecha">
                    Revisado el <?= e(fechaHoraRevision($detalleRendicion["fecha_revision"])) ?>
                </span>

            <?php endif; ?>

        </section>


        <?php if ($mensajeExito): ?>

            <div class="mensaje exito">

                <?= e($mensajeExito) ?>

            </div>

        <?php endif; ?>


        <?php if ($mensajeError): ?>

            <div class="mensaje error">

                <?= e($mensajeError) ?>

            </div>

        <?php endif; ?>


        <div id="detalle-rendicion">


            <section class="portal-card">

                <h2>

                    <?= e(
                        $detalleRendicion["nombres"] .
                            " " .
                            $detalleRendicion["apellido_paterno"] .
                            " " .
                            $detalleRendicion["apellido_materno"],
                    ) ?>

                </h2>

                <div class="admin-rendicion-datos">


                    <p>

                        <strong>
                            RUT:
                        </strong>

                        <span class="rut-completo"><?= e($detalleRendicion["rut"]) ?></span>

                    </p>


                    <p>

                        <strong>
                            Correo:
                        </strong>

                        <?= e($detalleRendicion["correo_electronico"]) ?>

                    </p>


                    <p>

                        <strong>
                            Teléfono:
                        </strong>

                        <?= e($detalleRendicion["telefono"]) ?>

                    </p>


                    <p>

                        <strong>
                            Carrera:
                        </strong>

                        <?= e($detalleRendicion["carrera"]) ?>

                    </p>


                    <p>

                        <strong>
                            Institución:
                        </strong>

                        <?= e($detalleRendicion["nombre_establecimiento"]) ?>

                    </p>


                    <p>

                        <strong>
                            Folio:
                        </strong>

                        <?= e($detalleRendicion["folio"]) ?>

                    </p>


                    <p>

                        <strong>
                            Año que cursa:
                        </strong>

                        <?= e($detalleRendicion["anio_cursa"]) ?>

                    </p>


                    <p>

                        <strong>
                            Fecha de envío:
                        </strong>

                        <?= e(fechaHora($detalleRendicion["fecha_envio"])) ?>

                    </p>


                    <p>

                        <strong>
                            Estado:
                        </strong>

                        <?= e(textoEstadoRendicion($detalleRendicion["estado"])) ?>

                    </p>

                </div>

            </section>


            <form
                method="POST"
                action="guardar_revision_rendicion.php"
                id="formRevisionGeneral">


                <input
                    type="hidden"
                    name="rendicion_id"
                    value="<?= (int) $rendicionId ?>">


                <section class="portal-card">

                    <div class="etiqueta">
                        <?= $detalleRendicion["monto_anual"] !== null
                            ? "RENDICIÓN ANUAL"
                            : "PRIMER SEMESTRE" ?>
                    </div>


                    <h2>
                        Boletas y comprobantes
                    </h2>


                    <?php if (empty($gastosPrimerSemestre)): ?>


                        <p>
                            No existen gastos registrados
                            para <?= $detalleRendicion["monto_anual"] !== null
                                ? "el periodo anual."
                                : "el primer semestre." ?>
                        </p>


                    <?php else: ?>


                        <div class="admin-lista-boletas-estilo-documentos">


                            <?php foreach ($gastosPrimerSemestre as $indice => $gasto): ?>


                                <?php $estadoAdmin =
                                    $gasto["estado_revision_admin"] ?? "pendiente"; ?>


                                <article class="admin-archivo-revision">


                                    <h3>

                                        Boleta
                                        <?= $indice + 1 ?>

                                    </h3>


                                    <p class="admin-archivo-nombre">

                                        <?= e($gasto["nombre_archivo_original"] ?: "Boleta.pdf") ?>

                                    </p>


                                    <div class="admin-gasto-datos-documento">


                                        <div>

                                            <span>
                                                Fecha
                                            </span>

                                            <strong>

                                                <?= e(fecha($gasto["fecha_gasto"])) ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Categoría
                                            </span>

                                            <strong>

                                                <?= e(
                                                    categoriaGasto(
                                                        $gasto["categoria"],
                                                        $gasto["categoria_otro"] ?? null,
                                                    ),
                                                ) ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                N° documento
                                            </span>

                                            <strong>

                                                <?= e($gasto["numero_documento"]) ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Monto
                                            </span>

                                            <strong>

                                                <?= e(dinero($gasto["monto"])) ?>

                                            </strong>

                                        </div>


                                    </div>


                                    <div class="admin-estado-archivo">

                                        <span>
                                            Estado actual:
                                        </span>


                                        <span
                                            class="
                                            estado-doc-listado
                                            <?= e(claseEstadoBoleta($estadoAdmin)) ?>
                                        ">

                                            <?= e(estadoBoleta($estadoAdmin)) ?>

                                        </span>

                                    </div>


                                    <div class="admin-archivo-acciones">


                                        <button
                                            type="button"
                                            class="
                                            btn-formulario-principal
                                            btn-ver-pdf-rendicion
                                        "
                                            data-pdf="ver_boleta_rendicion.php?id=<?= (int) $gasto[
                                                "id"
                                            ] ?>"
                                            data-titulo="<?= e(
                                                $gasto["nombre_archivo_original"],
                                            ) ?>">
                                            Vista previa
                                        </button>


                                        <a
                                            href="ver_boleta_rendicion.php?id=<?= (int) $gasto[
                                                "id"
                                            ] ?>&modo=descargar"
                                            class="btn-descarga-admin-rendicion">
                                            Descargar
                                        </a>


                                    </div>


                                    <div class="revision-documento-opciones">


                                        <label
                                            class="
                                            opcion-documento
                                            opcion-aceptar
                                        ">

                                            <input
                                                type="radio"
                                                name="boletas[<?= (int) $gasto["id"] ?>][estado]"
                                                value="validado"
                                                <?= $revisionAdministrativaTerminada &&
                                                $estadoAdmin === "validado"
                                                    ? "checked"
                                                    : "" ?>
                                                required>

                                            <span>
                                                ✓ Aceptar
                                            </span>

                                        </label>


                                        <label
                                            class="
                                            opcion-documento
                                            opcion-rechazar
                                        ">

                                            <input
                                                type="radio"
                                                name="boletas[<?= (int) $gasto["id"] ?>][estado]"
                                                value="rechazado"
                                                <?= $revisionAdministrativaTerminada &&
                                                $estadoAdmin === "rechazado"
                                                    ? "checked"
                                                    : "" ?>
                                                required>

                                            <span>
                                                × Rechazar
                                            </span>

                                        </label>


                                    </div>


                                    <div class="campo-form">

                                        <label
                                            for="observacion_boleta_<?= (int) $gasto["id"] ?>">
                                            Observación del documento
                                        </label>


                                        <textarea
                                            id="observacion_boleta_<?= (int) $gasto["id"] ?>"
                                            name="boletas[<?= (int) $gasto["id"] ?>][observacion]"
                                            rows="5"
                                            maxlength="1000"
                                            placeholder="Escribe una observación para esta boleta si corresponde..."><?= e(
                                                $gasto["observacion_validacion"] ?? "",
                                            ) ?></textarea>

                                    </div>


                                </article>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </section>


                <?php if ($detalleRendicion["monto_anual"] === null): ?>
                    <section class="portal-card">

                        <div class="etiqueta">
                            SEGUNDO SEMESTRE
                        </div>


                        <h2>
                            Boletas y comprobantes
                        </h2>


                        <?php if (empty($gastosSegundoSemestre)): ?>


                            <p>
                                No existen gastos registrados
                                para el segundo semestre.
                            </p>


                        <?php else: ?>


                            <div class="admin-lista-boletas-estilo-documentos">


                                <?php foreach ($gastosSegundoSemestre as $indice => $gasto): ?>


                                    <?php $estadoAdmin =
                                        $gasto["estado_revision_admin"] ?? "pendiente"; ?>


                                    <article class="admin-archivo-revision">


                                        <h3>

                                            Boleta
                                            <?= $indice + 1 ?>

                                        </h3>


                                        <p class="admin-archivo-nombre">

                                            <?= e(
                                                $gasto["nombre_archivo_original"] ?: "Boleta.pdf",
                                            ) ?>

                                        </p>


                                        <div class="admin-gasto-datos-documento">


                                            <div>

                                                <span>
                                                    Fecha
                                                </span>

                                                <strong>

                                                    <?= e(fecha($gasto["fecha_gasto"])) ?>

                                                </strong>

                                            </div>


                                            <div>

                                                <span>
                                                    Categoría
                                                </span>

                                                <strong>

                                                    <?= e(
                                                        categoriaGasto(
                                                            $gasto["categoria"],
                                                            $gasto["categoria_otro"] ?? null,
                                                        ),
                                                    ) ?>

                                                </strong>

                                            </div>


                                            <div>

                                                <span>
                                                    N° documento
                                                </span>

                                                <strong>

                                                    <?= e($gasto["numero_documento"]) ?>

                                                </strong>

                                            </div>


                                            <div>

                                                <span>
                                                    Monto
                                                </span>

                                                <strong>

                                                    <?= e(dinero($gasto["monto"])) ?>

                                                </strong>

                                            </div>


                                        </div>


                                        <div class="admin-estado-archivo">

                                            <span>
                                                Estado actual:
                                            </span>


                                            <span
                                                class="
                                            estado-doc-listado
                                            <?= e(claseEstadoBoleta($estadoAdmin)) ?>
                                        ">

                                                <?= e(estadoBoleta($estadoAdmin)) ?>

                                            </span>

                                        </div>


                                        <div class="admin-archivo-acciones">


                                            <button
                                                type="button"
                                                class="
                                            btn-formulario-principal
                                            btn-ver-pdf-rendicion
                                        "
                                                data-pdf="ver_boleta_rendicion.php?id=<?= (int) $gasto[
                                                    "id"
                                                ] ?>"
                                                data-titulo="<?= e(
                                                    $gasto["nombre_archivo_original"],
                                                ) ?>">
                                                Vista previa
                                            </button>


                                            <a
                                                href="ver_boleta_rendicion.php?id=<?= (int) $gasto[
                                                    "id"
                                                ] ?>&modo=descargar"
                                                class="btn-descarga-admin-rendicion">
                                                Descargar
                                            </a>


                                        </div>


                                        <div class="revision-documento-opciones">


                                            <label
                                                class="
                                            opcion-documento
                                            opcion-aceptar
                                        ">

                                                <input
                                                    type="radio"
                                                    name="boletas[<?= (int) $gasto[
                                                        "id"
                                                    ] ?>][estado]"
                                                    value="validado"
                                                    <?= $revisionAdministrativaTerminada &&
                                                    $estadoAdmin === "validado"
                                                        ? "checked"
                                                        : "" ?>
                                                    required>

                                                <span>
                                                    ✓ Aceptar
                                                </span>

                                            </label>


                                            <label
                                                class="
                                            opcion-documento
                                            opcion-rechazar
                                        ">

                                                <input
                                                    type="radio"
                                                    name="boletas[<?= (int) $gasto[
                                                        "id"
                                                    ] ?>][estado]"
                                                    value="rechazado"
                                                    <?= $revisionAdministrativaTerminada &&
                                                    $estadoAdmin === "rechazado"
                                                        ? "checked"
                                                        : "" ?>
                                                    required>

                                                <span>
                                                    × Rechazar
                                                </span>

                                            </label>


                                        </div>


                                        <div class="campo-form">

                                            <label
                                                for="observacion_boleta_<?= (int) $gasto["id"] ?>">
                                                Observación del documento
                                            </label>


                                            <textarea
                                                id="observacion_boleta_<?= (int) $gasto["id"] ?>"
                                                name="boletas[<?= (int) $gasto[
                                                    "id"
                                                ] ?>][observacion]"
                                                rows="5"
                                                maxlength="1000"
                                                placeholder="Escribe una observación para esta boleta si corresponde..."><?= e(
                                                    $gasto["observacion_validacion"] ?? "",
                                                ) ?></textarea>

                                        </div>


                                    </article>


                                <?php endforeach; ?>


                            </div>


                        <?php endif; ?>


                    </section>


                <?php endif; ?>
                <?php if ($realizoDevolucion): ?>


                    <section class="portal-card">

                        <h2>
                            Comprobante de devolución
                        </h2>

                        <p class="admin-subtexto-rendicion">
                            Revisa el comprobante presentado por el beneficiario
                            y decide si corresponde aceptarlo o rechazarlo.
                        </p>


                        <div class="admin-devolucion-grid">


                            <div class="admin-devolucion-dato">

                                <span>
                                    Monto devuelto
                                </span>

                                <strong>

                                    <?= e(dinero($detalleRendicion["monto_devuelto"])) ?>

                                </strong>

                            </div>


                            <div class="admin-devolucion-dato">

                                <span>
                                    Fecha de depósito / transferencia
                                </span>

                                <strong>

                                    <?= e(fecha($detalleRendicion["fecha_deposito_devolucion"])) ?>

                                </strong>

                            </div>


                            <div class="admin-devolucion-dato">

                                <span>
                                    N° comprobante / operación
                                </span>

                                <strong>

                                    <?= e(
                                        $detalleRendicion["numero_comprobante_devolucion"] ?: "-",
                                    ) ?>

                                </strong>

                            </div>


                        </div>


                        <?php if ($comprobanteDevolucionAdjunto): ?>


                            <article
                                class="
                    admin-archivo-revision
                    admin-archivo-devolucion
                ">


                                <h3>
                                    Comprobante de devolución
                                </h3>


                                <p class="admin-archivo-nombre">

                                    <?= e(
                                        $detalleRendicion["devolucion_nombre_original"] ?:
                                        "Comprobante de devolución.pdf",
                                    ) ?>

                                </p>


                                <div class="admin-estado-archivo">

                                    <span>
                                        Estado actual:
                                    </span>


                                    <span
                                        class="
                            estado-doc-listado
                            <?= e(claseEstadoBoleta($estadoDevolucionAdmin)) ?>
                        ">

                                        <?= e(estadoBoleta($estadoDevolucionAdmin)) ?>

                                    </span>

                                </div>


                                <div class="admin-archivo-acciones">


                                    <button
                                        type="button"
                                        class="
                            btn-formulario-principal
                            btn-ver-pdf-rendicion
                        "
                                        data-pdf="ver_comprobante_devolucion.php?id=<?= (int) $rendicionId ?>"
                                        data-titulo="<?= e(
                                            $detalleRendicion["devolucion_nombre_original"] ?:
                                            "Comprobante de devolución",
                                        ) ?>">
                                        Vista previa
                                    </button>


                                    <a
                                        href="ver_comprobante_devolucion.php?id=<?= (int) $rendicionId ?>&modo=descargar"
                                        class="btn-descarga-admin-rendicion">
                                        Descargar
                                    </a>


                                </div>


                                <div class="revision-documento-opciones">


                                    <label
                                        class="
                            opcion-documento
                            opcion-aceptar
                        ">

                                        <input
                                            type="radio"
                                            name="devolucion[estado]"
                                            value="validado"
                                            <?= $revisionAdministrativaTerminada &&
                                            $estadoDevolucionAdmin === "validado"
                                                ? "checked"
                                                : "" ?>
                                            required>

                                        <span>
                                            ✓ Aceptar
                                        </span>

                                    </label>


                                    <label
                                        class="
                            opcion-documento
                            opcion-rechazar
                        ">

                                        <input
                                            type="radio"
                                            name="devolucion[estado]"
                                            value="rechazado"
                                            <?= $revisionAdministrativaTerminada &&
                                            $estadoDevolucionAdmin === "rechazado"
                                                ? "checked"
                                                : "" ?>
                                            required>

                                        <span>
                                            × Rechazar
                                        </span>

                                    </label>


                                </div>


                                <div class="campo-form">

                                    <label for="observacion_devolucion">

                                        Observación del documento

                                    </label>


                                    <textarea
                                        id="observacion_devolucion"
                                        name="devolucion[observacion]"
                                        rows="5"
                                        maxlength="1000"
                                        placeholder="Puedes escribir una observación para este comprobante."><?= e(
                                            $detalleRendicion[
                                                "devolucion_observacion_validacion"
                                            ] ?? "",
                                        ) ?></textarea>

                                </div>


                            </article>


                        <?php else: ?>


                            <div class="mensaje error">

                                El beneficiario registró una devolución,
                                pero el comprobante PDF no se encuentra disponible.

                            </div>


                        <?php endif; ?>


                    </section>


                <?php endif; ?>


                <section class="portal-card">

                    <h2>
                        Observación general
                    </h2><div class="aviso-documentos"><div class="aviso-documentos-icono" aria-hidden="true">⚠</div><p>Si la rendición es rechazada, debes escribir una observación general que explique el motivo.</p></div>

                    <p class="admin-subtexto-rendicion">
                        Puedes ingresar una observación general sobre la revisión
                        de la rendición. Esta información será visible para el
                        beneficiario cuando consulte el resultado.
                    </p>

                    <div class="campo-form">

                        <textarea
                            id="observaciones"
                            name="observaciones"
                            rows="6"
                            maxlength="2000"
                            placeholder="Escribe una observación general si corresponde..."><?= e(
                                $detalleRendicion["observaciones"] ?? "",
                            ) ?></textarea>


                    </div>


                </section>


                <section class="portal-card">

                    <h2>
                        Resumen de la rendición
                    </h2>

                    <div class="admin-rendicion-datos">
                        <p>

                            <strong>
                                Monto total asignado:
                            </strong>

                            <?= e(dinero($detalleRendicion["monto_asignado"])) ?>

                        </p>


                        <p>

                            <strong>
                                Total rendido:
                            </strong>

                            <?= e(dinero($totalMontoDetalle)) ?>

                        </p>


                        <?php if ($realizoDevolucion): ?>

                            <p>

                                <strong>
                                    Monto devuelto:
                                </strong>

                                <?= e(dinero($detalleRendicion["monto_devuelto"])) ?>

                            </p>

                        <?php endif; ?>


                        <p>

                            <strong>
                                Total aceptado:
                            </strong>

                            <?= e(dinero($totalMontoAceptadoDetalle)) ?>

                        </p>


                        <p>

                            <strong>
                                Total rechazado:
                            </strong>

                            <?= e(dinero($totalMontoRechazadoDetalle)) ?>

                        </p>


                        <p>

                            <strong>
                                Boletas pendientes:
                            </strong>

                            <?= $totalPendientesDetalle ?>

                        </p>


                    </div>

                </section>


                <?php if ($totalBoletasDetalle > 0): ?>

                    <button
                        type="submit"
                        class="btn-formulario-principal">
                        Guardar revisión
                    </button>

                <?php endif; ?>


            </form>


        </div>


    </main>


    <div
        class="
        admin-modal
        admin-modal-pdf-rendicion
    "
        id="modalPdfRendicion"
        aria-hidden="true">


        <div
            class="
            admin-modal-contenido
            admin-modal-pdf-contenido
        ">


            <div class="admin-modal-header">


                <div>

                    <span class="etiqueta">
                        DOCUMENTO
                    </span>


                    <h2 id="tituloPdfRendicion">
                        Documento
                    </h2>

                </div>


                <button
                    type="button"
                    class="admin-modal-cerrar"
                    id="cerrarPdfRendicion"
                    aria-label="Cerrar">
                    ×
                </button>


            </div>


            <iframe
                id="visorPdfRendicion"
                class="admin-visor-pdf-rendicion"
                src=""
                title="Documento de rendición"></iframe>


        </div>


    </div>


    <footer class="footer">

        <p>
            © <?= date("Y") ?>
            Becas Estudiantiles
        </p>

    </footer>


    <script>
        document.addEventListener(
            "DOMContentLoaded",
            function() {


                const modal =
                    document.getElementById(
                        "modalPdfRendicion"
                    );


                const visor =
                    document.getElementById(
                        "visorPdfRendicion"
                    );


                const titulo =
                    document.getElementById(
                        "tituloPdfRendicion"
                    );


                const cerrar =
                    document.getElementById(
                        "cerrarPdfRendicion"
                    );


                const formulario =
                    document.getElementById(
                        "formRevisionGeneral"
                    );


                function abrirPdf(
                    url,
                    nombre
                ) {

                    if (
                        !modal ||
                        !visor ||
                        !titulo
                    ) {
                        return;
                    }


                    visor.src =
                        url +
                        (url.includes("?") ? "&" : "?") +
                        "volver=" +
                        encodeURIComponent(
                            window.location.pathname + window.location.search
                        );


                    titulo.textContent =
                        nombre ||
                        "Documento";


                    modal.classList.add(
                        "visible"
                    );


                    modal.setAttribute(
                        "aria-hidden",
                        "false"
                    );


                    document.body.classList.add(
                        "modal-abierto"
                    );
                }


                function cerrarPdf() {

                    if (
                        !modal ||
                        !visor
                    ) {
                        return;
                    }


                    modal.classList.remove(
                        "visible"
                    );


                    modal.setAttribute(
                        "aria-hidden",
                        "true"
                    );


                    visor.src =
                        "";


                    document.body.classList.remove(
                        "modal-abierto"
                    );
                }

                window.addEventListener("message", function(evento) {
                    if (
                        evento.origin === window.location.origin &&
                        evento.data &&
                        evento.data.tipo === "becas-cerrar-visor-documento"
                    ) {
                        cerrarPdf();
                    }
                });


                document
                    .querySelectorAll(
                        ".btn-ver-pdf-rendicion"
                    )
                    .forEach(
                        function(boton) {

                            boton.addEventListener(
                                "click",
                                function() {

                                    abrirPdf(
                                        boton.dataset.pdf,
                                        boton.dataset.titulo
                                    );
                                }
                            );
                        }
                    );


                if (cerrar) {

                    cerrar.addEventListener(
                        "click",
                        cerrarPdf
                    );
                }


                if (modal) {

                    modal.addEventListener(
                        "click",
                        function(evento) {

                            if (
                                evento.target ===
                                modal
                            ) {

                                cerrarPdf();
                            }
                        }
                    );
                }


                document.addEventListener(
                    "keydown",
                    function(evento) {

                        if (
                            evento.key ===
                            "Escape" &&
                            modal &&
                            modal.classList.contains(
                                "visible"
                            )
                        ) {

                            cerrarPdf();
                        }
                    }
                );


                if (formulario) {
                    const observacionGeneral = document.getElementById('observaciones');
                    function actualizarObservacionGeneral() {
                        const rechazada = !!formulario.querySelector('input[type="radio"][value="rechazado"]:checked');
                        observacionGeneral.required = rechazada;
                        observacionGeneral.setCustomValidity(rechazada && !observacionGeneral.value.trim() ? 'Debes escribir una observación general que explique el motivo del rechazo de la rendición.' : '');
                    }
                    formulario.addEventListener('change', actualizarObservacionGeneral);
                    observacionGeneral.addEventListener('input', actualizarObservacionGeneral);
                    actualizarObservacionGeneral();


                    formulario.addEventListener(
                        "submit",
                        function(evento) {


                            const grupos =
                                new Set();


                            document
                                .querySelectorAll(
                                    'input[type="radio"][name^="boletas["]'
                                )
                                .forEach(
                                    function(radio) {

                                        grupos.add(
                                            radio.name
                                        );
                                    }
                                );


                            let primerGrupoSinSeleccionar =
                                null;


                            grupos.forEach(
                                function(nombre) {

                                    const seleccionado =
                                        document.querySelector(
                                            'input[name="' +
                                            CSS.escape(
                                                nombre
                                            ) +
                                            '"]:checked'
                                        );


                                    if (
                                        !seleccionado &&
                                        primerGrupoSinSeleccionar ===
                                        null
                                    ) {

                                        primerGrupoSinSeleccionar =
                                            nombre;
                                    }
                                }
                            );


                            if (
                                primerGrupoSinSeleccionar !==
                                null
                            ) {

                                evento.preventDefault();


                                alert(
                                    "Debes indicar si cada boleta es aceptada o rechazada antes de guardar la revisión."
                                );


                                const radio =
                                    document.querySelector(
                                        'input[name="' +
                                        CSS.escape(
                                            primerGrupoSinSeleccionar
                                        ) +
                                        '"]'
                                    );


                                if (radio) {

                                    const tarjeta =
                                        radio.closest(
                                            ".admin-archivo-revision"
                                        );


                                    if (tarjeta) {

                                        tarjeta.scrollIntoView({
                                            behavior: "smooth",

                                            block: "center"
                                        });
                                    }
                                }
                            }
                        }
                    );
                }


            }
        );
    </script>


</body>

</html>
