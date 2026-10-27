<?php
/**
 * Variables recibidas desde:
 * usuario/resultado.php
 *
 * @var int $usuario_id
 * @var array|null $postulacion
 * @var DateTimeZone $zonaHoraria
 * @var DateTime $ahora
 * @var DateTime|false|null $fechaResultados
 * @var bool $resultadosDisponibles
 * @var bool $fechaResultadosConfigurada
 * @var bool $resultadoDefinitivo
 * @var array $documentos
 * @var string|null $restriccionPostulacion
 * @var array|null $proceso
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
        Resultado de postulación
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=mobile-20261020&modulo-encabezado-20261005">

</head>

<body>


    <header class="header">

        <div class="logo">
            Becas Estudiantiles
        </div>


        <?php require __DIR__ . "/menu.php"; ?>

    </header>


    <main class="resultado resultado-con-encabezado">
        <div class="modulo-usuario-encabezado">
            <h1>Resultado</h1>
            <?php require __DIR__ . "/../proceso_modulo.php"; ?>
            <p>Aquí podrás revisar el resultado de tu postulación y el estado de los documentos que adjuntaste.</p>
        </div>
        <div class="modulo-usuario-contenido">



        <?php if (!empty($restriccionPostulacion)): ?>

            <div class="resultado-box" role="status">
                <div class="resultado-icono revision">!</div>
                <h1>Postulación restringida por rendición pendiente</h1>

                <p><?= e($restriccionPostulacion) ?></p>
                <p>No tienes un resultado de postulación para este proceso.</p>
                <a href="inicio.php" class="btn-portal resultado-volver-portal">Volver al portal</a>
            </div>

        <?php elseif (!$postulacion): ?>


            <div class="resultado-box">

                <div class="resultado-icono revision">
                    ?
                </div>


                <h1>
                    Sin postulación
                </h1>



                <p>
                    <?= $proceso
                        ? "Todavía no existe una postulación registrada para este proceso."
                        : "Actualmente no hay un proceso activo para consultar resultados." ?>
                </p>


                <br>


                <a
                    href="postulacion.php"
                    class="btn-portal">
                    Ir a mi postulación
                </a>

            </div>


        <?php elseif (!$fechaResultadosConfigurada): ?>


            <div class="resultado-box">

                <div class="resultado-icono revision">
                    ⏳
                </div>


                <h1>
                    Resultados aún no disponibles
                </h1>



                <p>
                    La fecha oficial de publicación de resultados
                    todavía no ha sido configurada.
                </p>


                <p>
                    Cuando el administrador defina la fecha,
                    podrás consultar tu resultado desde esta sección.
                </p>

            </div>


        <?php elseif (!$resultadosDisponibles): ?>


            <div class="resultado-box">

                <div class="resultado-icono revision">
                    ⏳
                </div>


                <h1>
                    Resultados aún no disponibles
                </h1>



                <p>
                    Tu resultado todavía no puede ser consultado.
                </p>


                <div class="monto-beca">

                    <span>
                        Publicación de resultados
                    </span>


                    <strong
                        style="
                        font-size: 24px;
                    ">

                        <?= e(mostrarFechaResultado($fechaResultados)) ?>

                    </strong>

                </div>

            </div>


        <?php elseif ($postulacion["estado"] === "pendiente"): ?>


            <div class="resultado-box">

                <div class="resultado-icono revision">
                    ⏳
                </div>


                <h1>
                    Postulación pendiente
                </h1>



                <p>
                    Tu postulación todavía no ha sido enviada.
                </p>

            </div>


        <?php elseif ($postulacion["estado"] === "en_revision"): ?>


            <div class="resultado-box">

                <div class="resultado-icono revision">
                    ⏳
                </div>


                <h1>
                    Resultado pendiente
                </h1>



                <p>
                    La fecha oficial de publicación ya comenzó,
                    pero tu postulación todavía se encuentra
                    en proceso de evaluación.
                </p>

            </div>


        <?php elseif ($postulacion["estado"] === "beneficiado"): ?>


            <div class="resultado-box">

                <div class="resultado-icono exito">
                    ✓
                </div>


                <h1>
                    ¡Has sido beneficiado!
                </h1>



                <p>
                    Felicitaciones. Tu postulación fue seleccionada
                    para recibir la beca.
                </p>


                <div class="monto-beca">

                    <span>
                        Monto asignado
                    </span>


                    <strong>

                        $

                        <?= number_format((float) $postulacion["monto_asignado"], 0, ",", ".") ?>

                    </strong>

                </div>


                <a
                    href="rendicion.php"
                    class="btn-portal">
                    Ir a rendición
                </a>

            </div>


        <?php elseif ($postulacion["estado"] === "no_beneficiado"): ?>


            <div class="resultado-box">

                <div class="resultado-icono error">
                    ×
                </div>


                <h1>
                    No beneficiado
                </h1>



                <p>
                    En esta oportunidad tu postulación no fue
                    seleccionada para recibir la beca.
                </p>

            </div>


        <?php endif; ?>


        <?php if (
            $resultadoDefinitivo &&
            !empty(trim((string) ($postulacion["observacion_admin"] ?? "")))
        ): ?>


            <section
                class="
                resultado-documentos
                resultado-documentos-mejorado
                resultado-observacion-seccion
            ">


                <div class="resultado-documentos-encabezado">

                    <div>

                        <h2>
                            Observación general
                        </h2>

                    </div>

                </div>


                <div class="resultado-observacion-general">

                    <p>

                        <?= nl2br(e(trim((string) $postulacion["observacion_admin"]))) ?>

                    </p>

                </div>


            </section>


        <?php endif; ?>


        <?php if ($resultadoDefinitivo): ?>


            <section
                class="
                resultado-documentos
                resultado-documentos-mejorado
            ">


                <div class="resultado-documentos-encabezado">

                    <div>

                        <h2>
                            Estado de tus documentos
                        </h2>


                        <p>
                            Revisa el resultado de la validación
                            de los antecedentes que adjuntaste
                            durante tu postulación.
                        </p>

                    </div>

                </div>


                <div class="resultado-documentos-lista">


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


                        <?php
                        $documento = $documentos[$tipo] ?? null;

                        $estadoDocumento = $documento["estado_validacion"] ?? "pendiente";

                        $claseEstado = claseEstadoDocumento($estadoDocumento);

                        $iconoEstado = iconoEstadoDocumento($estadoDocumento);

                        $observacionDocumento = trim((string) ($documento["observacion"] ?? ""));

                        $fechaValidacion = null;

                        if ($documento && !empty($documento["fecha_validacion"])) {
                            $fechaValidacion = DateTime::createFromFormat(
                                "Y-m-d H:i:s",
                                $documento["fecha_validacion"],
                                $zonaHoraria,
                            );
                        }
                        ?>


                        <article
                            class="
                            resultado-documento-fila
                            <?= e($claseEstado) ?>
                        ">


                            <div class="resultado-documento-icono">

                                <?= e($iconoEstado) ?>

                            </div>


                            <div class="resultado-documento-info">


                                <div class="resultado-documento-superior">


                                    <div class="resultado-documento-nombre">


                                        <h3>

                                            <?= e(nombreDocumentoResultado($tipo)) ?>

                                        </h3>


                                        <?php if ($fechaValidacion instanceof DateTime): ?>


                                            <span
                                                class="resultado-documento-fecha">

                                                Revisado el

                                                <?= e($fechaValidacion->format("d/m/Y H:i")) ?>

                                            </span>


                                        <?php elseif ($estadoDocumento === "pendiente"): ?>


                                            <span
                                                class="resultado-documento-fecha">

                                                Aún no ha sido revisado

                                            </span>


                                        <?php endif; ?>


                                        <?php if ($observacionDocumento !== ""): ?>


                                            <div
                                                class="resultado-observacion-documento">

                                                <strong>
                                                    Observación del documento
                                                </strong>


                                                <p>

                                                    <?= nl2br(e($observacionDocumento)) ?>

                                                </p>

                                            </div>


                                        <?php endif; ?>


                                    </div>


                                    <span
                                        class="resultado-documento-badge">

                                        <?= e(textoEstadoDocumentoResultado($estadoDocumento)) ?>

                                    </span>


                                </div>


                                <?php if ($estadoDocumento === "validado"): ?>


                                    <p
                                        class="
                                        resultado-documento-mensaje
                                        aceptado
                                    ">

                                        El documento fue revisado
                                        y aceptado correctamente.

                                    </p>


                                <?php endif; ?>


                                <?php if ($estadoDocumento === "rechazado"): ?>


                                    <p
                                        class="
                                        resultado-documento-mensaje
                                        rechazado
                                    ">

                                        El documento fue rechazado
                                        durante la revisión.

                                    </p>


                                <?php endif; ?>


                                <?php if ($estadoDocumento === "pendiente"): ?>


                                    <p
                                        class="
                                        resultado-documento-mensaje
                                        pendiente
                                    ">

                                        Este documento todavía se encuentra
                                        pendiente de revisión.

                                    </p>


                                <?php endif; ?>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


                <div class="resultado-documentos-ayuda">

                    <div class="resultado-documentos-ayuda-icono">
                        i
                    </div>


                    <p>

                        Las observaciones indicadas corresponden
                        a los comentarios realizados por el administrador
                        durante la revisión de cada documento.

                    </p>

                </div>


            </section>


        <?php endif; ?>


        </div></main>


    <footer class="footer">

        <p>
            © <?= date("Y") ?>
            Becas Estudiantiles
        </p>

    </footer>


</body>

</html>
