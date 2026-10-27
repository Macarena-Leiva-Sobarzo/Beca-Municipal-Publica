<?php

/**
 * Variables recibidas desde:
 * usuario/postulacion.php
 *
 * @var int $usuario_id
 * @var array|null $postulacion
 * @var array|null $proceso
 * @var string $estadoPeriodo
 * @var DateTime|false|null $fechaInicio
 * @var DateTime|false|null $fechaCierre
 * @var DateTimeZone $zonaHoraria
 * @var DateTime $ahora
 * @var string|null $mensajeAnioProceso
 * @var string|null $restriccionPostulacion
 */

$mensajeAnioProceso = $mensajeAnioProceso ?? null;
$restriccionPostulacion = $restriccionPostulacion ?? null;
$mostrarNoDisponible =
    !$mensajeAnioProceso &&
    !$restriccionPostulacion &&
    (!$postulacion || $postulacion["estado"] === "pendiente") &&
    $estadoPeriodo === "sin_configurar";
?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Mi postulación
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


    <main class="<?= $mostrarNoDisponible
        ? "resultado resultado-con-encabezado"
        : "formulario-postulacion" ?>">




        <div class="formulario-encabezado">

            <h1>
                Mi postulación
            </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>

            <p>
                Revisa el estado de tu postulación
                y descarga tu comprobante una vez enviada
            </p>

        </div><?php
        if ($mostrarNoDisponible): ?><div class="modulo-usuario-contenido"><?php endif;
        if ($mensajeAnioProceso): ?>
            <div class="portal-card" role="alert">
                <h2>Período de postulación no disponible</h2>
                <p><?= htmlspecialchars($mensajeAnioProceso, ENT_QUOTES, "UTF-8") ?></p>
                <?php if ($postulacion && $postulacion["estado"] !== "pendiente"): ?>
                    <a href="comprobante.php" class="btn-portal">Ver comprobante</a>
                <?php endif; ?>
            </div>
        <?php elseif ($restriccionPostulacion): ?>
            <div class="portal-card" role="alert">
                <h2>Postulación restringida por rendición pendiente</h2>
                <p><?= htmlspecialchars($restriccionPostulacion, ENT_QUOTES, "UTF-8") ?></p>
            </div>
        <?php elseif ($postulacion && $postulacion["estado"] !== "pendiente"): ?>


            <div class="portal-card">

                <div class="portal-card-icono">
                    ✓
                </div>

                <h2>
                    Postulación enviada
                </h2>

                <p>
                    Tu postulación fue recibida correctamente.
                    Puedes revisar y descargar tu comprobante
                    de postulación.
                </p>


                <?php if (!empty($postulacion["fecha_postulacion"])): ?>

                    <p class="postulacion-fecha">

                        Fecha de envío:

                        <strong>

                            <?= htmlspecialchars(
                                date("d/m/Y H:i", strtotime($postulacion["fecha_postulacion"])),
                            ) ?>

                        </strong>

                    </p>

                <?php endif; ?>


                <a
                    href="comprobante.php"
                    class="btn-portal">

                    Ver comprobante

                </a>

            </div>


        <?php elseif ($estadoPeriodo === "cerrado"): ?>


            <div class="portal-card">

                <div class="portal-card-icono">
                    🔒
                </div>

                <h2>
                    Postulación cerrada
                </h2>

                <p>
                    El período de postulación
                    ha finalizado.
                    Ya no es posible realizar
                    ni continuar una postulación.
                </p>


                <?php if ($fechaCierre instanceof DateTime): ?>

                    <p class="postulacion-fecha">

                        Fecha de cierre:

                        <strong>

                            <?= htmlspecialchars($fechaCierre->format("d/m/Y H:i")) ?>

                        </strong>

                    </p>

                <?php endif; ?>

            </div>


        <?php elseif ($estadoPeriodo === "no_iniciado"): ?>


            <div class="portal-card">

                <div class="portal-card-icono">
                    📅
                </div>

                <h2>
                    Postulación aún no disponible
                </h2>

                <p>
                    El período de postulación
                    todavía no ha comenzado.
                </p>


                <?php if ($fechaInicio instanceof DateTime): ?>

                    <p class="postulacion-fecha">

                        Inicio de postulaciones:

                        <strong>

                            <?= htmlspecialchars($fechaInicio->format("d/m/Y H:i")) ?>

                        </strong>

                    </p>

                <?php endif; ?>

            </div>


        <?php elseif ($estadoPeriodo === "sin_configurar"): ?>


            <div class="resultado-box" role="status">

                <div class="resultado-icono revision" aria-hidden="true">
                    ⚠
                </div>

                <h1>
                    Postulación no disponible
                </h1>


                <p>
                    Actualmente no existe un proceso
                    de beca disponible para postular.
                </p>

            </div>


        <?php elseif ($postulacion && $postulacion["estado"] === "pendiente"): ?>


            <div class="portal-card">

                <div class="portal-card-icono">
                    📝
                </div>

                <h2>
                    Postulación pendiente
                </h2>

                <p>
                    Tu postulación todavía está pendiente
                    de completar.
                </p>

                <a
                    href="formulario_postulacion.php"
                    class="btn-portal">

                    Continuar postulación

                </a>

            </div>


        <?php else: ?>


            <div class="portal-card">

                <div class="portal-card-icono">
                    📝
                </div>

                <h2>
                    Comenzar postulación
                </h2>

                <p>
                    Aún no tienes una postulación registrada.
                    Para comenzar deberás completar todos los
                    campos y adjuntar los documentos obligatorios.
                </p>

                <a
                    href="formulario_postulacion.php"
                    class="btn-portal">

                    Comenzar postulación

                </a>

            </div>


        <?php endif;
        ?>


        <?php if ($mostrarNoDisponible): ?></div><?php endif; ?></main>


    <footer class="footer">

        <p>
            © <?= date("Y") ?> Becas Estudiantiles
        </p>

    </footer>


</body>

</html>
