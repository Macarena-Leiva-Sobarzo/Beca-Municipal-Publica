<?php

/**
 * Variables recibidas desde:
 * usuario/inicio.php
 *
 * @var int $usuario_id
 * @var array|null $postulacion
 * @var DateTimeZone $zonaHoraria
 * @var DateTime $ahora
 * @var bool $resultadosDisponibles
 * @var string $estadoPeriodoRendicion
 * @var DateTime|false|null $fechaResultados
 * @var DateTime|false|null $fechaInicioRendicion
 * @var DateTime|false|null $fechaCierreRendicion
 * @var bool $esBeneficiario
 * @var array|null $rendicion
 * @var bool $rendicionRealizada
 * @var string|null $mensajeAnioRendicion
 */

$mensajeAnioRendicion = $mensajeAnioRendicion ?? null; ?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Portal del postulante
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=mobile-20261020&inicio-cuatro-tarjetas-20261006">

</head>

<body>


    <header class="header">

        <div class="logo">
            Becas Estudiantiles
        </div>


        <?php require __DIR__ . "/menu.php"; ?>

    </header>


    <main class="portal-usuario portal-inicio">



        <div class="portal-encabezado">

            <h1>
                Bienvenido
            </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>


            <p>
                Desde este espacio podrás completar tu postulación,
                consultar el resultado y, si eres beneficiario,
                realizar la rendición correspondiente.
            </p>

        </div>


        <?php if ($esBeneficiario && $mensajeAnioRendicion): ?>
            <div class="portal-card portal-aviso-rendicion" role="alert">
                <p><?= htmlspecialchars($mensajeAnioRendicion, ENT_QUOTES, "UTF-8") ?></p>
            </div>
        <?php endif; ?>

        <div class="portal-grid">


            <div class="portal-card">

                <div class="portal-card-icono">
                    📝
                </div>


                <h2>
                    Mi postulación
                </h2>


                <p>
                    Completa los datos personales,
                    educacionales, bancarios y documentos
                    necesarios para postular.
                </p>


                <a
                    href="postulacion.php"
                    class="btn-portal">

                    Ver postulación

                </a>

            </div>

            <div class="portal-card">

                <div class="portal-card-icono">
                    📊
                </div>


                <h2>
                    Resultado
                </h2>


                <p>
                    Consulta el estado de tu postulación,
                    si fuiste beneficiado y el monto asignado.
                </p>


                <?php if ($postulacion): ?><a
                    href="resultado.php"
                    class="btn-portal">

                    Ver resultado

                </a><?php endif; ?>

            </div>


            <div class="portal-card portal-card-segundo-semestre">

                <div class="portal-card-icono">
                    🎓
                </div>


                <h2>
                    Certificado segundo semestre
                </h2>


                <p>
                    Debes enviar tu certificado de alumno regular
                    correspondiente al segundo semestre.
                </p>


                <?php if ($esBeneficiario): ?>

                    <a
                        href="segundo_semestre.php"
                        class="btn-portal">
                        Enviar certificado
                    </a>

                <?php else: ?>

                    <p class="portal-card-disponibilidad">
                        Este módulo estará disponible si resultas beneficiado.
                    </p>

                <?php endif; ?>

            </div>


            <div class="portal-card">

                <div class="portal-card-icono">
                    📄
                </div>


                <h2>
                    Rendición
                </h2>


                <?php if (!$postulacion): ?>

                    <p>
                        El formulario de rendición estará
                        disponible únicamente si resultas
                        beneficiado.
                    </p>


                <?php elseif (!$resultadosDisponibles): ?>

                    <p>
                        El formulario de rendición estará
                        disponible únicamente si resultas
                        beneficiado.
                    </p>


                <?php elseif ($postulacion["estado"] === "beneficiado"): ?>


                    <?php if ($estadoPeriodoRendicion === "sin_configurar"): ?>

                        <p>
                            Has resultado beneficiado.
                        </p>

                        <p>
                            La fecha de inicio del periodo
                            de rendición aún no ha sido informada.
                        </p>


                    <?php elseif ($estadoPeriodoRendicion === "no_iniciado"): ?>

                        <p>Has resultado beneficiado.</p>
                        <a href="rendicion.php" class="btn-portal">Ver rendición</a>

                    <?php elseif ($estadoPeriodoRendicion === "abierto"): ?>

                        <p>
                            El periodo de rendición
                            se encuentra disponible
                        </p>


                        <?php if ($fechaCierreRendicion instanceof DateTime): ?>

                            <p>
                                Puedes realizar tu rendición
                                hasta el
                                <strong>
                                    <?= htmlspecialchars(
                                        $fechaCierreRendicion->format("d/m/Y H:i"),
                                    ) ?>
                                </strong>
                            </p>

                        <?php endif; ?>


                        <a
                            href="rendicion.php"
                            class="btn-portal">

                            Ver rendición

                        </a>


                    <?php elseif ($estadoPeriodoRendicion === "cerrado"): ?>

                        <p>
                            El periodo establecido para realizar
                            la rendición ha finalizado.
                        </p>


                        <?php if ($fechaCierreRendicion instanceof DateTime): ?>

                            <p>
                                El plazo finalizó el
                                <strong>
                                    <?= htmlspecialchars(
                                        $fechaCierreRendicion->format("d/m/Y H:i"),
                                    ) ?>
                                </strong>
                            </p>

                        <?php endif; ?>


                        <?php if ($rendicionRealizada): ?>

                            <a
                                href="rendicion.php"
                                class="btn-portal">
                                Ver rendición
                            </a>

                        <?php endif; ?>


                    <?php endif; ?>


                <?php elseif ($postulacion["estado"] === "no_beneficiado"): ?>

                    <p>
                        El formulario de rendición no se
                        encuentra disponible para tu postulación.
                    </p>


                <?php else: ?>

                    <p>
                        El formulario de rendición estará
                        disponible únicamente si resultas
                        beneficiado
                    </p>

                <?php endif; ?>


            </div>


        </div>


    </main>


    <footer class="footer">

        <p>
            © <?= date("Y") ?> Becas Estudiantiles
        </p>

    </footer>


</body>

</html>
