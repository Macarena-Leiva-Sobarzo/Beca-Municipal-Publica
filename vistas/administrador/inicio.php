<?php
/**
 * Variables recibidas desde:
 * administrador/inicio.php
 *
 * @var string $busqueda
 * @var array $estadisticas
 * @var array $estadisticasRendiciones
 * @var mysqli_result $postulaciones
 * @var int $porPaginaInicio
 * @var int $totalInicio
 * @var int $paginasInicio
 * @var int $paginaInicio
 * @var int $offsetInicio
 * @var Closure(int): string $urlPaginaInicio
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
        Panel administrativo
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

            <a href="inicio.php" aria-current="page">
                Inicio
            </a>

            <a href="postulaciones.php">
                Postulaciones
            </a>

            <a href="rendiciones.php">
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


    <main class="admin-pagina">



        <div class="admin-encabezado">

            <h1>
                Panel administrativo
            </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>

            <p>
                Gestiona las postulaciones, revisa los antecedentes
                de los postulantes y configura el proceso de la beca
            </p>

        </div>


        <section class="admin-buscador-principal">

            <div class="admin-buscador-texto">

                <h2>
                    Buscar postulante
                </h2>

                <p>
                    Escribe el nombre o RUT de la persona que deseas revisar
                </p>

            </div>


            <form
                action="inicio.php"
                method="GET"
                class="admin-form-busqueda">

                <div class="admin-input-busqueda">

                    <span class="admin-icono-busqueda">
                        🔎
                    </span>


                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        placeholder="Ej: María González o 12.345.678-9"
                        value="<?= e($busqueda) ?>"
                        autocomplete="off">

                </div>


                <button
                    type="submit"
                    class="admin-btn-buscar">
                    Buscar
                </button>


                <?php if ($busqueda !== ""): ?>

                    <a
                        href="inicio.php"
                        class="admin-btn-limpiar">
                        Limpiar
                    </a>

                <?php endif; ?>


            </form>

        </section>


        <div class="admin-estadisticas">


            <div class="admin-stat stat-total">

                <span>
                    Total de postulaciones
                </span>

                <strong>
                    <?= $estadisticas["total"] ?>
                </strong>

            </div>


            <div class="admin-stat stat-revision">

                <span>
                    En revisión
                </span>

                <strong>
                    <?= $estadisticas["revision"] ?>
                </strong>

            </div>


            <div class="admin-stat stat-beneficiados">

                <span>
                    Beneficiados
                </span>

                <strong>
                    <?= $estadisticas["beneficiados"] ?>
                </strong>

            </div>


            <div class="admin-stat stat-rechazados">

                <span>
                    No beneficiados
                </span>

                <strong>
                    <?= $estadisticas["rechazados"] ?>
                </strong>

            </div>


        </div>


        <div class="admin-estadisticas">


            <div class="admin-stat stat-total">

                <span>
                    Total de rendiciones
                </span>

                <strong>
                    <?= $estadisticasRendiciones["total"] ?>
                </strong>

            </div>


            <div class="admin-stat stat-revision">

                <span>
                    En revisión
                </span>

                <strong>
                    <?= $estadisticasRendiciones["revision"] ?>
                </strong>

            </div>


            <div class="admin-stat stat-beneficiados">

                <span>
                    Aprobadas
                </span>

                <strong>
                    <?= $estadisticasRendiciones["aprobadas"] ?>
                </strong>

            </div>


            <div class="admin-stat stat-rechazados">

                <span>
                    Rechazadas
                </span>

                <strong>
                    <?= $estadisticasRendiciones["rechazadas"] ?>
                </strong>

            </div>


        </div>


        <div class="admin-listado-resumen">Mostrando <strong><?= $postulaciones->num_rows > 0
            ? $offsetInicio + 1
            : 0 ?></strong> a <strong><?= $postulaciones->num_rows > 0
    ? $offsetInicio + $postulaciones->num_rows
    : 0 ?></strong> de <strong><?= $totalInicio ?></strong> registros. · Total general: <strong><?= $estadisticas[
    "total"
] ?></strong></div>

        <section class="admin-seccion">


            <div class="admin-seccion-titulo">

                <div>


                    <?php if ($busqueda !== ""): ?>


                        <h2>
                            Resultados de búsqueda
                        </h2>


                        <p>

                            Resultados encontrados para:

                            <strong>
                                "<?= e($busqueda) ?>"
                            </strong>

                        </p>


                    <?php else: ?>


                        <h2>
                            Últimas postulaciones
                        </h2>


                        <p>
                            Postulaciones recibidas recientemente
                        </p>


                    <?php endif; ?>


                </div>


                <a
                    href="postulaciones.php"
                    class="btn-portal">
                    Ver todas las postulaciones
                </a>

            </div>


            <div class="admin-tabla-contenedor">

                <table class="admin-tabla">

                    <thead>

                        <tr>

                            <th>
                                Postulante
                            </th>

                            <th>
                                RUT
                            </th>

                            <th>
                                Carrera
                            </th>

                            <th>
                                Institución
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acción
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if ($postulaciones && $postulaciones->num_rows > 0): ?>


                            <?php while ($postulacion = $postulaciones->fetch_assoc()): ?>


                                <tr>


                                    <td>

                                        <div class="admin-postulante-tabla">

                                            <strong>

                                                <?= e(
                                                    $postulacion["nombres"] .
                                                        " " .
                                                        $postulacion["apellido_paterno"] .
                                                        " " .
                                                        $postulacion["apellido_materno"],
                                                ) ?>

                                            </strong>


                                            <span>

                                                <?= e($postulacion["correo_electronico"]) ?>

                                            </span>

                                        </div>

                                    </td>


                                    <td>

                                        <span class="rut-completo"><?= e(
                                            $postulacion["rut"],
                                        ) ?></span>

                                    </td>


                                    <td>

                                        <?= e($postulacion["carrera"]) ?>

                                    </td>


                                    <td>

                                        <?= e($postulacion["nombre_establecimiento"]) ?>

                                    </td>


                                    <td>

                                        <?php if (!empty($postulacion["fecha_postulacion"])): ?>

                                            <?= e(
                                                date(
                                                    "d/m/Y",
                                                    strtotime($postulacion["fecha_postulacion"]),
                                                ),
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <span
                                            class="admin-estado estado-<?= e(
                                                $postulacion["estado"],
                                            ) ?>">

                                            <?= e(textoEstado($postulacion["estado"])) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <a
                                            href="detalle_postulacion.php?id=<?= (int) $postulacion[
                                                "id"
                                            ] ?>"
                                            class="admin-btn-revisar">
                                            Revisar
                                        </a>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="7"
                                    class="admin-vacio">

                                    <?php if ($busqueda !== ""): ?>

                                        No se encontraron postulantes que coincidan
                                        con la búsqueda

                                    <?php else: ?>

                                        Todavía no existen postulaciones registradas

                                    <?php endif; ?>

                                </td>

                            </tr>


                        <?php endif; ?>


                    </tbody>

                </table>

            </div>


        </section>



        <nav class="postulaciones-paginacion" aria-label="Páginas de postulaciones del inicio">
            <?php if ($paginaInicio > 1): ?>
                <a class="postulaciones-paginacion-control" href="<?= e(
                    $urlPaginaInicio($paginaInicio - 1),
                ) ?>">‹ Anterior</a>
            <?php else: ?><span class="postulaciones-paginacion-control deshabilitado">‹ Anterior</span><?php endif; ?>
            <div class="postulaciones-paginacion-paginas">
                <?php
                $paginasMostrarInicio = array_unique(
                    array_merge(
                        [1],
                        range(max(1, $paginaInicio - 2), min($paginasInicio, $paginaInicio + 2)),
                        [$paginasInicio],
                    ),
                );
                $anteriorInicio = 0;
                foreach ($paginasMostrarInicio as $numeroInicio):

                    if (
                        $anteriorInicio &&
                        $numeroInicio > $anteriorInicio + 1
                    ): ?><span class="postulaciones-paginacion-puntos">…</span><?php endif;
                    $anteriorInicio = $numeroInicio;
                    ?>
                    <?php if (
                        $numeroInicio === $paginaInicio
                    ): ?><span class="postulaciones-paginacion-numero activo" aria-current="page"><?= $numeroInicio ?></span>
                    <?php else: ?><a class="postulaciones-paginacion-numero" href="<?= e(
    $urlPaginaInicio($numeroInicio),
) ?>"><?= $numeroInicio ?></a><?php endif; ?>
                <?php
                endforeach;
                ?>
            </div>
            <?php if ($paginaInicio < $paginasInicio): ?>
                <a class="postulaciones-paginacion-control" href="<?= e(
                    $urlPaginaInicio($paginaInicio + 1),
                ) ?>">Siguiente ›</a>
            <?php else: ?><span class="postulaciones-paginacion-control deshabilitado">Siguiente ›</span><?php endif; ?>
        </nav>
        <p class="historial-pagina-actual">Página <?= $paginaInicio ?> de <?= $paginasInicio ?></p>

    </main>


    <footer class="footer">

        <p>
            © <?= date("Y") ?> Becas Estudiantiles
        </p>

    </footer>


</body>

</html>
