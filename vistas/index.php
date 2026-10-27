<?php
/**
 * @var array|null $proceso
 * @var array $requisitos
 * @var DateTimeZone $zonaHoraria
 * @var bool $procesoAbierto
 * @var bool $procesoNoIniciado
 * @var bool $procesoCerrado
 * @var DateTime|null $fechaInicioProceso
 * @var DateTime|null $fechaCierreProceso
 * @var bool $esSoloRendicion
 * @var string $nombreEtapa
 * @var string $nombreEtapaPlural
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
        Becas Estudiantiles
    </title>

    <link
        rel="stylesheet"
        href="css/estilos.css?v=fechas-importantes-20261020">

</head>


<body>


    <header class="header">

        <div class="logo">
            Becas Estudiantiles
        </div>


        <nav class="nav">

            <a href="#inicio">
                Inicio
            </a>

            <a href="#informacion">
                Información
            </a>


            <?php if (isset($_SESSION["usuario_id"])): ?>


                <?php if (isset($_SESSION["rol"]) && $_SESSION["rol"] === "administrador"): ?>


                    <a href="administrador/inicio.php">
                        Administración
                    </a>


                <?php else: ?>


                    <a href="usuario/inicio.php">
                        Mi cuenta
                    </a>


                <?php endif; ?>


                <a href="auth/cerrar_sesion.php">
                    Cerrar sesión
                </a>


            <?php else: ?>


                <a href="auth/login.php">
                    Iniciar sesión
                </a>


            <?php endif; ?>


        </nav>

    </header>


    <main id="inicio">


        <section class="hero">


            <div class="hero-contenido">


                <div class="etiqueta">


                    <?php if ($proceso): ?>


                        <?= htmlspecialchars(strtoupper($proceso["nombre"]), ENT_QUOTES, "UTF-8") ?>


                    <?php else: ?>


                        BECAS ESTUDIANTILES


                    <?php endif; ?>


                </div>


                <h1>
                    Apoyo para continuar tus estudios
                </h1>


                <?php if ($proceso): ?>


                    <?php if ($procesoAbierto): ?>


                        <p>

                            <?php if ($esSoloRendicion): ?>
                                El proceso de rendición se encuentra abierto. Revisa las fechas antes de comenzar.
                            <?php else: ?>
                                El proceso de postulación se encuentra abierto.
                                Revisa la información, requisitos y fechas antes de comenzar.
                            <?php endif; ?>

                        </p>


                    <?php elseif ($procesoNoIniciado): ?>


                        <p>

                            Las <?= htmlspecialchars($nombreEtapaPlural, ENT_QUOTES, "UTF-8") ?> comenzarán el

                            <strong>

                                <?= htmlspecialchars(
                                    mostrarFechaPublica($proceso["fecha_inicio"], $zonaHoraria),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>

                            </strong>

                        </p>


                    <?php elseif ($procesoCerrado): ?>


                        <p>

                            El período de <?= htmlspecialchars($nombreEtapa, ENT_QUOTES, "UTF-8") ?>
                            finalizó el

                            <strong>

                                <?= htmlspecialchars(
                                    mostrarFechaPublica($proceso["fecha_cierre"], $zonaHoraria),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>

                            </strong>

                        </p>


                    <?php else: ?>


                        <p>

                            Revisa la información del proceso antes de comenzar tu <?= htmlspecialchars($nombreEtapa, ENT_QUOTES, "UTF-8") ?>.

                        </p>


                    <?php endif; ?>


                <?php else: ?>


                    <p>

                        La información del próximo
                        proceso estará disponible
                        próximamente.

                    </p>


                <?php endif; ?>


                <?php if (
                    isset($_SESSION["usuario_id"]) &&
                    isset($_SESSION["rol"]) &&
                    $_SESSION["rol"] === "administrador"
                ): ?>


                    <a
                        href="administrador/inicio.php"
                        class="btn-principal">
                        Ir a administración
                    </a>


                <?php elseif ($procesoAbierto): ?>


                    <?php if (isset($_SESSION["usuario_id"])): ?>


                        <a
                            href="<?= $esSoloRendicion ? "usuario/rendicion.php" : "usuario/postulacion.php" ?>"
                            class="btn-principal">
                            <?= $esSoloRendicion ? "Realizar rendición" : "Postular ahora" ?>
                        </a>


                    <?php else: ?>


                        <a
                            href="auth/login.php"
                            class="btn-principal">
                            <?= $esSoloRendicion ? "Realizar rendición" : "Postular ahora" ?>
                        </a>


                    <?php endif; ?>


                <?php else: ?>


                    <span
                        class="btn-principal"
                        style="
                        opacity: 0.65;
                        cursor: not-allowed;
                    ">


                        <?php if ($procesoNoIniciado): ?>


                            <?= $esSoloRendicion ? "Rendición aún no disponible" : "Postulación aún no disponible" ?>


                        <?php else: ?>


                            <?= $esSoloRendicion ? "Rendición cerrada" : "Postulación cerrada" ?>


                        <?php endif; ?>


                    </span>


                <?php endif; ?>


            </div>


        </section>


        <section
            class="informacion"
            id="informacion">


            <div class="seccion-titulo">

                <h2>
                    Todo lo que necesitas saber
                </h2>


                <p>

                    Revisa la información configurada para el proceso de <?= htmlspecialchars($nombreEtapa, ENT_QUOTES, "UTF-8") ?> vigente.

                </p>

            </div>


            <div class="carrusel-contenedor">


                <button
                    type="button"
                    class="carrusel-btn izquierda"
                    id="btnAnterior"
                    aria-label="Información anterior">
                    ❮
                </button>


                <div
                    class="carrusel"
                    id="carrusel">





                    <?php if (!$esSoloRendicion): ?>
                    <article class="tarjeta">


                        <div class="icono">
                            ✓
                        </div>


                        <h3>
                            Requisitos de postulación
                        </h3>


                        <?php if (!empty($requisitos)): ?>


                            <ul>


                                <?php foreach ($requisitos as $requisito): ?>


                                    <li>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $requisito["titulo"],
                                                ENT_QUOTES,
                                                "UTF-8",
                                            ) ?>

                                        </strong>


                                        <?php if (trim($requisito["descripcion"]) !== ""): ?>


                                            <br>


                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $requisito["descripcion"],
                                                    ENT_QUOTES,
                                                    "UTF-8",
                                                ),
                                            ) ?>


                                        <?php endif; ?>


                                    </li>


                                <?php endforeach; ?>


                            </ul>


                        <?php else: ?>


                            <p>

                                No existen requisitos
                                registrados para este proceso.

                            </p>


                        <?php endif; ?>


                    </article>
                    <?php endif; ?>


                    <article class="tarjeta">


                        <div class="icono">
                            📅
                        </div>


                        <h3>
                            Fechas importantes
                        </h3>


                        <?php if ($proceso): ?>


                            <div class="fecha">

                                <strong>
                                    Inicio de la <?= htmlspecialchars($nombreEtapa, ENT_QUOTES, "UTF-8") ?>
                                </strong>


                                <span>

                                    <?= htmlspecialchars(
                                        mostrarFechaPublica($proceso["fecha_inicio"], $zonaHoraria),
                                        ENT_QUOTES,
                                        "UTF-8",
                                    ) ?>

                                </span>

                            </div>


                            <div class="fecha">

                                <strong>
                                    Cierre de la <?= htmlspecialchars($nombreEtapa, ENT_QUOTES, "UTF-8") ?>
                                </strong>


                                <span>

                                    <?= htmlspecialchars(
                                        mostrarFechaPublica($proceso["fecha_cierre"], $zonaHoraria),
                                        ENT_QUOTES,
                                        "UTF-8",
                                    ) ?>

                                </span>

                            </div>


                            <div class="fecha">

                                <strong>
                                    Resultado de la <?= htmlspecialchars($nombreEtapa, ENT_QUOTES, "UTF-8") ?>
                                </strong>


                                <span>

                                    <?= htmlspecialchars(
                                        mostrarFechaPublica(
                                            $proceso["fecha_resultados"],
                                            $zonaHoraria,
                                        ),
                                        ENT_QUOTES,
                                        "UTF-8",
                                    ) ?>

                                </span>

                            </div>


                        <?php else: ?>


                            <p>

                                No existen fechas configuradas
                                para un proceso activo.

                            </p>


                        <?php endif; ?>


                    </article>


                </div>


                <button
                    type="button"
                    class="carrusel-btn derecha"
                    id="btnSiguiente"
                    aria-label="Información siguiente">
                    ❯
                </button>


            </div>


            <div
                class="indicadores"
                id="indicadores"></div>


        </section>


    </main>


    <footer class="footer">

        <p>
            © <?= date("Y") ?> Becas Estudiantiles
        </p>

    </footer>


    <script src="js/script.js"></script>


</body>

</html>
