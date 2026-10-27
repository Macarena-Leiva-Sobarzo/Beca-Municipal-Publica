<?php
/**
 * Variables recibidas desde:
 * superior/detalle_postulacion.php
 *
 * @var array $postulacion
 * @var array $documentos
 * @var string $banco
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
        Revisar postulación
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=mobile-20261020&espaciado-20261004">

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

            <a href="postulaciones.php" aria-current="page">
                Postulaciones
            </a>

            <a href="rendiciones.php">
                Rendiciones
            </a>

            <a href="configuracion.php">
                Configuración
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


    <main class="admin-pagina">



        <div class="admin-detalle-superior">

            <div>

                <h1>

                    <?= e(
                        $postulacion["nombres"] .
                            " " .
                            $postulacion["apellido_paterno"] .
                            " " .
                            $postulacion["apellido_materno"],
                    ) ?>

                </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>


                <p>

                    RUT:

                    <strong>
                        <?= e($postulacion["rut"]) ?>
                    </strong>

                    &nbsp; · &nbsp;

                    Folio:

                    <strong>
                        <?= e($postulacion["folio"]) ?>
                    </strong>

                </p>

            </div>


            <span
                class="admin-estado estado-<?= e($postulacion["estado"]) ?>">

                <?= e(textoEstado($postulacion["estado"])) ?>

            </span>

        </div>


        <section class="admin-responsable-revision" aria-label="Responsable de la revisión">

            <span>Revisado por</span>

            <strong><?= e($postulacion["nombre_revisor"] ?: "Sin revisión registrada") ?></strong>

            <?php if (!empty($postulacion["fecha_revision"])): ?>

                <span class="admin-responsable-fecha">
                    Revisado el <?= e(fechaHoraRevision($postulacion["fecha_revision"])) ?>
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


        <section class="admin-detalle-card">

            <h2>
                Datos personales
            </h2>


            <div class="admin-datos-grid">

                <div>

                    <span>
                        Nombres
                    </span>

                    <strong>
                        <?= e($postulacion["nombres"]) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Apellido paterno
                    </span>

                    <strong>
                        <?= e($postulacion["apellido_paterno"]) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Apellido materno
                    </span>

                    <strong>
                        <?= e($postulacion["apellido_materno"]) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        RUT
                    </span>

                    <strong>
                        <span class="rut-completo"><?= e($postulacion["rut"]) ?></span>
                    </strong>

                </div>


                <div>

                    <span>
                        Fecha nacimiento
                    </span>

                    <strong>

                        <?= !empty($postulacion["fecha_nacimiento"])
                            ? date("d/m/Y", strtotime($postulacion["fecha_nacimiento"]))
                            : "-" ?>

                    </strong>

                </div>


                <div>

                    <span>
                        Teléfono
                    </span>

                    <strong>
                        <?= e($postulacion["telefono"]) ?>
                    </strong>

                </div>


                <div class="admin-dato-ancho">

                    <span>
                        Dirección
                    </span>

                    <strong>
                        <?= e($postulacion["direccion"]) ?>
                    </strong>

                </div>


                <div class="admin-dato-ancho">

                    <span>
                        Correo
                    </span>

                    <strong>

                        <?= e($postulacion["correo_electronico"]) ?>

                    </strong>

                </div>

            </div>

        </section>


        <section class="admin-detalle-card">

            <h2>
                Antecedentes educacionales
            </h2>


            <div class="admin-datos-grid">

                <div>

                    <span>
                        Carrera
                    </span>

                    <strong>
                        <?= e($postulacion["carrera"]) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Institución
                    </span>

                    <strong>

                        <?= e($postulacion["nombre_establecimiento"]) ?>

                    </strong>

                </div>


                <div>

                    <span>
                        Región
                    </span>

                    <strong>
                        <?= e($postulacion["region"]) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Comuna
                    </span>

                    <strong>
                        <?= e($postulacion["comuna"]) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Gratuidad
                    </span>

                    <strong>

                        <?= $postulacion["tiene_gratuidad"] === "si" ? "Sí" : "No" ?>

                    </strong>

                </div>


                <div>

                    <span>
                        Promedio último semestre
                    </span>

                    <strong>

                        <?= number_format(
                            (float) $postulacion["promedio_ultimo_semestre"],
                            2,
                            ",",
                            ".",
                        ) ?>

                    </strong>

                </div>

            </div>

        </section>


        <section class="admin-detalle-card">

            <h2>
                Datos bancarios
            </h2>


            <div class="admin-datos-grid">

                <div>

                    <span>
                        Banco
                    </span>

                    <strong>
                        <?= e($banco) ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Tipo de cuenta
                    </span>

                    <strong>

                        <?= e($postulacion["tipo_cuenta_nombre"]) ?>

                    </strong>

                </div>


                <div>

                    <span>
                        Número de cuenta
                    </span>

                    <strong>

                        <?= e($postulacion["numero_cuenta"]) ?>

                    </strong>

                </div>

            </div>

        </section>


        <form
            action="procesar_evaluacion.php"
            method="POST"
            id="formEvaluacion">


            <input
                type="hidden"
                name="postulacion_id"
                value="<?= (int) $postulacion["id"] ?>">


            <section class="admin-detalle-card">

                <h2>
                    Documentos adjuntos
                </h2>


                <p class="admin-texto-secundario">

                    Revisa cada documento y selecciona
                    si corresponde aceptarlo o rechazarlo.

                    Los cambios se guardarán únicamente
                    al presionar <strong>Guardar evaluación</strong>.

                </p>


                <div class="admin-documentos-grid">


                    <?php foreach (
                        [
                            "carnet",
                            "alumno_regular",
                            "certificado_notas",
                            "registro_social",
                            "acreditacion_gratuidad",
                        ]
                        as $tipo
                    ): ?>


                        <div class="admin-documento-card">


                            <h3>

                                <?= e(nombreDocumento($tipo)) ?>

                            </h3>


                            <?php if (isset($documentos[$tipo])): ?>


                                <?php $documento = $documentos[$tipo]; ?>

                                <?php
                                $estadoDocumentoMostrar = "pendiente";

                                if ($documento["estado_validacion"] === "rechazado") {
                                    $estadoDocumentoMostrar = "rechazado";
                                }

                                if (
                                    $documento["estado_validacion"] === "validado" &&
                                    $postulacion["estado"] !== "en_revision"
                                ) {
                                    $estadoDocumentoMostrar = "validado";
                                }
                                ?>

                                <p>

                                    <?= e($documento["nombre_archivo_original"]) ?>

                                </p>


                                <div class="documento-estado-contenedor">

                                    <span>
                                        Estado actual:
                                    </span>

                                    <?php
                                    $estadoVisualDocumento = "pendiente";

                                    if ($documento["estado_validacion"] === "rechazado") {
                                        $estadoVisualDocumento = "rechazado";
                                    }

                                    if (
                                        $documento["estado_validacion"] === "validado" &&
                                        $postulacion["estado"] !== "en_revision"
                                    ) {
                                        $estadoVisualDocumento = "validado";
                                    }
                                    ?>


                                    <strong
                                        class="documento-estado documento-estado-<?= e(
                                            $estadoVisualDocumento,
                                        ) ?>">

                                        <?= e(textoEstadoDocumento($estadoVisualDocumento)) ?>

                                    </strong>

                                </div>


                                <input
                                    type="hidden"
                                    name="documentos[<?= e($tipo) ?>][id]"
                                    value="<?= (int) $documento["id"] ?>">



                                <div class="admin-documento-acciones">

                                    <button
                                        type="button"
                                        class="btn-admin-documento"
                                        onclick="mostrarDocumento(
                                        <?= (int) $documento["id"] ?>,
                                        '<?= e(nombreDocumento($tipo)) ?>'
                                    )">
                                        Vista previa
                                    </button>


                                    <a
                                        href="descargar_documento.php?id=<?= (int) $documento[
                                            "id"
                                        ] ?>"
                                        class="btn-admin-documento secundario"
                                        target="_blank">
                                        Descargar
                                    </a>

                                </div>


                                <div class="revision-documento-opciones">


                                    <label
                                        class="opcion-documento opcion-aceptar">

                                        <input
                                            type="radio"
                                            name="documentos[<?= e($tipo) ?>][estado]"
                                            value="validado"
                                            <?= $documento["estado_validacion"] === "validado" &&
                                            $postulacion["estado"] !== "en_revision"
                                                ? "checked"
                                                : "" ?>
                                            required>

                                        <span>
                                            ✓ Aceptar
                                        </span>

                                    </label>


                                    <label
                                        class="opcion-documento opcion-rechazar">

                                        <input
                                            type="radio"
                                            name="documentos[<?= e($tipo) ?>][estado]"
                                            value="rechazado"
                                            <?= $documento["estado_validacion"] === "rechazado"
                                                ? "checked"
                                                : "" ?>
                                            required>

                                        <span>
                                            × Rechazar
                                        </span>

                                    </label>


                                </div>


                                <div class="campo-form">

                                    <label>
                                        Observación del documento
                                    </label>

                                    <textarea
                                        name="documentos[<?= e($tipo) ?>][observacion]"
                                        class="observacion-documento"
                                        rows="3"
                                        maxlength="2000"
                                        placeholder="Escribe una observación si corresponde..."><?= $documento[
                                            "estado_validacion"
                                        ] !== "pendiente"
                                            ? e(trim($documento["observacion"] ?? ""))
                                            : "" ?></textarea>

                                </div>


                            <?php else: ?>


                                <p class="admin-documento-faltante">

                                    Documento no disponible

                                </p>


                            <?php endif; ?>


                        </div>


                    <?php endforeach; ?>


                </div>

            </section>


            <section class="admin-detalle-card">

                <h2>
                    Evaluación de la postulación
                </h2>
                <div class="aviso-documentos"><div class="aviso-documentos-icono" aria-hidden="true">⚠</div><p>Ingresa los montos sin puntos ni comas. El número debe contener solo dígitos.</p></div>


                <!-- DECISIÓN -->

                <div class="campo-form">

                    <label for="decision">
                        Decisión
                    </label>


                    <select
                        name="decision"
                        id="decision"
                        required>

                        <option value="">
                            Selecciona una decisión
                        </option>


                        <option
                            value="beneficiado"
                            <?= $postulacion["estado"] === "beneficiado" ? "selected" : "" ?>>
                            Es beneficiario/a de la beca
                        </option>


                        <option
                            value="no_beneficiado"
                            <?= $postulacion["estado"] === "no_beneficiado" ? "selected" : "" ?>>
                            No es beneficiario/a de la beca
                        </option>

                    </select>

                </div>


                <div
                    class="admin-montos-beca"
                    id="contenedorMonto">


                    <div class="campo-form">

                        <label for="monto_asignado">
                            Monto total de la beca
                        </label>

                        <input
                            type="number"
                            name="monto_asignado"
                            id="monto_asignado" placeholder="100000"
                            min="1"
                            step="1"
                            value="<?= e($postulacion["monto_asignado"] ?? "") ?>">

                    </div>


                    <div class="form-grid">


                        <div class="campo-form">

                            <label for="monto_primer_semestre">
                                Monto primer semestre
                            </label>

                            <input
                                type="number"
                                name="monto_primer_semestre"
                                id="monto_primer_semestre" placeholder="50000"
                                min="0"
                                step="1"
                                value="<?= e($postulacion["monto_primer_semestre"] ?? "") ?>">

                        </div>


                        <div class="campo-form">

                            <label for="monto_segundo_semestre">
                                Monto segundo semestre
                            </label>

                            <input
                                type="number"
                                name="monto_segundo_semestre"
                                id="monto_segundo_semestre" placeholder="50000"
                                min="0"
                                step="1"
                                value="<?= e($postulacion["monto_segundo_semestre"] ?? "") ?>">

                        </div>


                    </div>


                    <small class="campo-ayuda">

                        La suma del primer y segundo semestre
                        debe coincidir exactamente con el monto total.

                    </small>


                    <div
                        class="mensaje error"
                        id="errorMontosBeca"
                        style="display: none;"></div>


                </div>

                <div
                    class="mensaje error"
                    id="errorDocumentos"
                    style="display: none;"></div>


            </section>


            <section class="admin-detalle-card observacion-general-card">

                <h2>
                    Observación general
                </h2>

                <div class="aviso-documentos" id="aviso-observacion-general"><div class="aviso-documentos-icono" aria-hidden="true">⚠</div><p>Si el postulante no es beneficiario, debes escribir una observación general que explique el motivo.</p></div>
                <textarea name="observacion"
                    id="observacion"
                    rows="6"
                    maxlength="2000"
                    placeholder="Escribe una observación si corresponde..."><?= isset(
                        $postulacion["observacion_admin"],
                    )
                        ? e(trim($postulacion["observacion_admin"]))
                        : "" ?></textarea>


            </section>


            <div class="contenedor-guardar-evaluacion">

                <div class="formulario-acciones"><button
                    type="submit"
                    class="btn-formulario-principal">

                    Guardar evaluación

                </button>
                <a href="postulaciones.php" class="btn-formulario-secundario">Volver</a></div>

            </div>


        </form>


        </form>


    </main>


    <div
        class="admin-modal"
        id="modalDocumento">

        <div class="admin-modal-contenido">


            <div class="admin-modal-header">

                <h2 id="modalTitulo">
                    Documento
                </h2>


                <button
                    type="button"
                    class="admin-modal-cerrar"
                    id="cerrarModal">
                    ×
                </button>

            </div>


            <iframe
                id="visorDocumento"
                class="admin-documento-visor"
                src=""
                title="Vista previa del documento"></iframe>


        </div>

    </div>


    <footer class="footer">

        <p>
            © <?= date("Y") ?> Becas Estudiantiles
        </p>

    </footer>


    <script>
        const modalDocumento =
            document.getElementById(
                "modalDocumento"
            );


        const visorDocumento =
            document.getElementById(
                "visorDocumento"
            );


        const modalTitulo =
            document.getElementById(
                "modalTitulo"
            );


        const cerrarModal =
            document.getElementById(
                "cerrarModal"
            );


        function mostrarDocumento(
            id,
            titulo
        ) {

            modalTitulo.textContent =
                titulo;


            visorDocumento.src =
                "ver_documento.php?id=" +
                encodeURIComponent(id) +
                "&volver=" +
                encodeURIComponent(
                    window.location.pathname + window.location.search
                );


            modalDocumento.classList.add(
                "visible"
            );


            document.body.classList.add(
                "modal-abierto"
            );
        }


        function ocultarDocumento() {

            modalDocumento.classList.remove(
                "visible"
            );


            visorDocumento.src =
                "";


            document.body.classList.remove(
                "modal-abierto"
            );
        }

        window.addEventListener("message", function(event) {
            if (
                event.origin === window.location.origin &&
                event.data &&
                event.data.tipo === "becas-cerrar-visor-documento"
            ) {
                ocultarDocumento();
            }
        });


        cerrarModal.addEventListener(
            "click",
            ocultarDocumento
        );


        modalDocumento.addEventListener(
            "click",
            function(event) {

                if (
                    event.target ===
                    modalDocumento
                ) {

                    ocultarDocumento();
                }
            }
        );


        document.addEventListener(
            "keydown",
            function(event) {

                if (
                    event.key === "Escape"
                ) {

                    ocultarDocumento();
                }
            }
        );


        const formularioEvaluacion =
            document.getElementById(
                "formEvaluacion"
            );


        const decision =
            document.getElementById(
                "decision"
            );


        const montoTotal =
            document.getElementById(
                "monto_asignado"
            );


        const montoPrimer =
            document.getElementById(
                "monto_primer_semestre"
            );


        const montoSegundo =
            document.getElementById(
                "monto_segundo_semestre"
            );


        const contenedorMonto =
            document.getElementById(
                "contenedorMonto"
            );


        const errorMontosBeca =
            document.getElementById(
                "errorMontosBeca"
            );


        const errorDocumentos =
            document.getElementById(
                "errorDocumentos"
            );


        function actualizarMonto() {

            const beneficiado =
                decision.value ===
                "beneficiado";


            contenedorMonto.style.display =
                beneficiado ?
                "block" :
                "none";


            montoTotal.required =
                beneficiado;


            montoPrimer.required =
                beneficiado;


            montoSegundo.required =
                beneficiado;


            if (!beneficiado) {

                errorMontosBeca.style.display =
                    "none";
            }
        }


        function validarObservacionesDocumentos() {

            const tarjetas =
                document.querySelectorAll(
                    ".admin-documento-card"
                );


            for (const tarjeta of tarjetas) {

                const rechazado =
                    tarjeta.querySelector(
                        'input[type="radio"][value="rechazado"]:checked'
                    );


                const observacion =
                    tarjeta.querySelector(
                        ".observacion-documento"
                    );


                if (
                    rechazado &&
                    observacion &&
                    !observacion.value.trim()
                ) {

                    errorDocumentos.textContent =
                        "Debes escribir una observación para cada documento rechazado.";


                    errorDocumentos.style.display =
                        "block";


                    observacion.focus();


                    return false;
                }
            }


            errorDocumentos.style.display =
                "none";


            return true;
        }


        const observacionGeneral = document.getElementById("observacion");
        function actualizarObservacionGeneral() {
            observacionGeneral.required = decision.value === "no_beneficiado";
            observacionGeneral.setCustomValidity(observacionGeneral.required && !observacionGeneral.value.trim() ? "Debes escribir una observación general que explique por qué el postulante no es beneficiario." : "");
        }
        decision.addEventListener("change", actualizarObservacionGeneral);
        observacionGeneral.addEventListener("input", actualizarObservacionGeneral);
        actualizarObservacionGeneral();

        formularioEvaluacion.addEventListener(
            "submit",
            function(event) {

                errorDocumentos.style.display =
                    "none";


                errorMontosBeca.style.display =
                    "none";


                if (
                    !validarObservacionesDocumentos()
                ) {

                    event.preventDefault();

                    return;
                }


                if (
                    decision.value ===
                    "beneficiado"
                ) {

                    const documentosRechazados =
                        document.querySelectorAll(
                            'input[type="radio"][value="rechazado"]:checked'
                        );


                    const documentosAceptados =
                        document.querySelectorAll(
                            'input[type="radio"][value="validado"]:checked'
                        );


                    if (
                        documentosRechazados.length > 0
                    ) {

                        event.preventDefault();


                        errorDocumentos.textContent =
                            "No puedes marcar la postulación como beneficiada mientras exista un documento rechazado.";


                        errorDocumentos.style.display =
                            "block";


                        errorDocumentos.scrollIntoView({
                            behavior: "smooth",
                            block: "center"
                        });


                        return;
                    }


                    if (
                        documentosAceptados.length < 5
                    ) {

                        event.preventDefault();


                        errorDocumentos.textContent =
                            "Debes aceptar los cinco documentos antes de marcar la postulación como beneficiada.";


                        errorDocumentos.style.display =
                            "block";


                        return;
                    }


                    const total =
                        Number(
                            montoTotal.value
                        );


                    const primero =
                        Number(
                            montoPrimer.value
                        );


                    const segundo =
                        Number(
                            montoSegundo.value
                        );


                    if (
                        !Number.isFinite(total) ||
                        !Number.isFinite(primero) ||
                        !Number.isFinite(segundo) ||
                        total <= 0 ||
                        primero < 0 ||
                        segundo < 0 ||
                        Math.abs(
                            (primero + segundo) -
                            total
                        ) > 0.01
                    ) {

                        event.preventDefault();


                        errorMontosBeca.textContent =
                            "La suma del primer y segundo semestre debe coincidir con el monto total de la beca.";


                        errorMontosBeca.style.display =
                            "block";


                        montoTotal.focus();


                        return;
                    }
                }
            }
        );


        decision.addEventListener(
            "change",
            actualizarMonto
        );


        actualizarMonto();
    </script>


</body>

</html>
