<?php
/**
 * Variables recibidas desde:
 * superior/archivos_descargar.php
 *
 * @var string $token
 * @var string|null $mensajeError
 * @var int $totalPostulantes
 * @var int $totalRendiciones
 * @var string $versionCss
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
        Descargar información
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=mobile-20261020&espaciado-20261004 e($versionCss) ?>">

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

            <a href="rendiciones.php">
                Rendiciones
            </a>

            <a href="configuracion.php">
                Configuración
            </a>

            <a href="archivos_descargar.php" aria-current="page">
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



        <div class="portal-encabezado">

            <h1>
                Descargar información
            </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>

            <p>
                Selecciona los datos que deseas descargar
                y el formato del archivo.
            </p>

        </div>


        <div class="admin-estadisticas">


            <div class="admin-stat stat-total">

                <span>
                    Postulantes
                </span>

                <strong>
                    <?= $totalPostulantes ?>
                </strong>

            </div>


            <div class="admin-stat stat-revision">

                <span>
                    Rendiciones realizadas
                </span>

                <strong>
                    <?= $totalRendiciones ?>
                </strong>

            </div>


        </div>


        <?php if ($mensajeError): ?>

            <div class="mensaje error">

                <?= e($mensajeError) ?>

            </div>

        <?php endif; ?>


        <section class="admin-detalle-card">

            <h2>
                Generar archivo
            </h2>


            <form
                action="generar_archivo.php"
                method="POST"
                class="admin-evaluacion">


                <input
                    type="hidden"
                    name="token"
                    value="<?= e($token) ?>">


                <div class="form-grid">


                    <div class="campo-form">

                        <label for="tipo_datos">
                            Información
                        </label>

                        <select
                            name="tipo_datos"
                            id="tipo_datos"
                            required>

                            <option value="">
                                Selecciona una opción
                            </option>

                            <option value="postulantes">
                                Postulantes
                            </option>

                            <option value="rendiciones">
                                Rendiciones
                            </option>

                            <option value="ambos">
                                Postulantes y rendiciones
                            </option>

                            <option value="resumen_financiero">
                                Resumen financiero
                            </option>

                        </select>

                    </div>


                    <div class="campo-form">

                        <label for="formato">
                            Formato
                        </label>

                        <select
                            name="formato"
                            id="formato"
                            required>

                            <option value="">
                                Selecciona un formato
                            </option>

                            <option value="pdf">
                                PDF
                            </option>

                            <option value="excel">
                                Excel
                            </option>

                        </select>

                    </div>


                </div>


                <div
                    style="
                    margin-top: 24px;
                    display: flex;
                    gap: 12px;
                    flex-wrap: wrap;
                ">

                    <button
                        type="submit"
                        class="btn-formulario-principal">
                        Descargar archivo
                    </button>

                    <a
                        href="inicio.php"
                        class="btn-formulario-secundario">
                        Volver
                    </a>

                </div>


            </form>

        </section>


    </main>


    <footer class="footer">

        <p>
            © <?= date("Y") ?>
            Becas Estudiantiles
        </p>

    </footer>


</body>

</html>
