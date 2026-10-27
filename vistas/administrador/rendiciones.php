<?php
/**
 * Variables recibidas desde:
 * administrador/rendiciones.php
 *
 * @var string $buscar
 * @var string $estadoFiltro
 * @var int $porPagina
 * @var int $paginaActual
 * @var int $totalRendicionesFiltradas
 * @var int $totalPaginas
 * @var int $offset
 * @var array $rendiciones
 * @var int $totalRendiciones
 * @var int $totalGeneralesBoletas
 * @var int $totalGeneralesAceptadas
 * @var int $totalGeneralesRechazadas
 * @var array $estadisticasRendiciones
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
        Rendiciones
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



        <div class="portal-encabezado">

            <h1>
                Revisar rendiciones
            </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>

            <p>
                Busca por nombre o RUT y revisa las boletas
                y comprobantes enviados por cada beneficiario.
            </p>

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


        <?php if (isset($_GET["ok"])): ?>

            <div class="mensaje exito">

                <?= e($_GET["ok"]) ?>

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["error"])): ?>

            <div class="mensaje error">

                <?= e($_GET["error"]) ?>

            </div>

        <?php endif; ?>


        <div class="admin-rendiciones-filtros">

            <form
                method="GET"
                action="rendiciones.php">

                <div class="campo">

                    <input
                        type="text"
                        name="buscar"
                        placeholder="Buscar por nombre, RUT o correo"
                        value="<?= e($buscar) ?>">

                </div>


                <div class="campo">

                    <select
                        name="estado">

                        <option value="">
                            Todos los estados
                        </option>


                        <option
                            value="en_revision"
                            <?= $estadoFiltro === "en_revision" ? "selected" : "" ?>>
                            En revisión
                        </option>


                        <option
                            value="aprobada"
                            <?= $estadoFiltro === "aprobada" ? "selected" : "" ?>>
                            Aprobada
                        </option>


                        <option
                            value="rechazada"
                            <?= $estadoFiltro === "rechazada" ? "selected" : "" ?>>
                            Rechazada
                        </option>

                    </select>

                </div>


                <button
                    type="submit"
                    class="admin-btn-buscar">
                    Buscar
                </button>


                <a
                    href="rendiciones.php"
                    class="admin-btn-limpiar">
                    Limpiar
                </a>

            </form>

        </div>


        <div class="admin-listado-resumen">Mostrando <strong><?= $totalRendicionesFiltradas > 0
            ? $offset + 1
            : 0 ?></strong> a <strong><?= min(
    $offset + $porPagina,
    $totalRendicionesFiltradas,
) ?></strong> de <strong><?= $totalRendicionesFiltradas ?></strong> registros. · Total general: <strong><?= $estadisticasRendiciones[
    "total"
] ?></strong></div>


        <div class="admin-tabla-contenedor">

            <table class="admin-rendiciones-tabla">

                <thead>

                    <tr>

                        <th>
                            Nombre
                        </th>

                        <th>
                            RUT
                        </th>

                        <th>
                            Fecha
                        </th>

                        <th>
                            Boletas
                        </th>

                        <th>
                            Aceptadas
                        </th>

                        <th>
                            Rechazadas
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


                    <?php if (empty($rendiciones)): ?>


                        <tr>

                            <td
                                colspan="8"
                                style="
                                text-align: center;
                                padding: 45px;
                            ">

                                No se encontraron rendiciones.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($rendiciones as $rendicionLista): ?>


                            <tr>

                                <td>

                                    <span class="admin-persona-nombre">

                                        <?= e(
                                            $rendicionLista["nombres"] .
                                                " " .
                                                $rendicionLista["apellido_paterno"] .
                                                " " .
                                                $rendicionLista["apellido_materno"],
                                        ) ?>

                                    </span>


                                    <span class="admin-persona-correo">

                                        <?= e($rendicionLista["correo_electronico"]) ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="rut-completo"><?= e(
                                        $rendicionLista["rut"],
                                    ) ?></span>

                                </td>


                                <td>

                                    <?= e(fecha($rendicionLista["fecha_envio"])) ?>

                                </td>


                                <td>

                                    <span class="admin-badge revision">

                                        <?= (int) $rendicionLista["total_boletas"] ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="admin-badge aceptado">

                                        ✓

                                        <?= (int) $rendicionLista["boletas_validadas"] ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="admin-badge rechazado">

                                        ×

                                        <?= (int) $rendicionLista["boletas_rechazadas"] ?>

                                    </span>

                                </td>


                                <td>

                                    <span
                                        class="
                                        admin-estado-rendicion
                                        <?= $rendicionLista["estado"] === "en_revision"
                                            ? "pendiente"
                                            : e($rendicionLista["estado"]) ?>
                                    ">

                                        <?= e(textoEstadoRendicion($rendicionLista["estado"])) ?>

                                    </span>

                                </td>


                                <td>

                                    <a
                                        href="detalle_rendicion.php?id=<?= (int) $rendicionLista[
                                            "id"
                                        ] ?>"
                                        class="admin-btn-revisar">

                                        Revisar

                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>


        <?php if ($totalPaginas >= 1): ?>

            <nav
                class="postulaciones-paginacion"
                aria-label="Paginación de rendiciones">

                <?php if ($paginaActual > 1): ?>

                    <a
                        href="<?= e(
                            urlPaginaRendiciones($paginaActual - 1, $buscar, $estadoFiltro),
                        ) ?>"
                        class="postulaciones-paginacion-control">
                        <span aria-hidden="true">‹</span>
                        Anterior
                    </a>

                <?php else: ?>

                    <span
                        class="postulaciones-paginacion-control deshabilitado">
                        <span aria-hidden="true">‹</span>
                        Anterior
                    </span>

                <?php endif; ?>


                <div class="postulaciones-paginacion-paginas">

                    <?php
                    $inicioPaginas = max(1, $paginaActual - 2);

                    $finPaginas = min($totalPaginas, $paginaActual + 2);
                    ?>


                    <?php if ($inicioPaginas > 1): ?>

                        <a
                            href="<?= e(urlPaginaRendiciones(1, $buscar, $estadoFiltro)) ?>"
                            class="postulaciones-paginacion-numero">
                            1
                        </a>

                        <?php if ($inicioPaginas > 2): ?>

                            <span class="postulaciones-paginacion-puntos">
                                …
                            </span>

                        <?php endif; ?>

                    <?php endif; ?>


                    <?php for ($i = $inicioPaginas; $i <= $finPaginas; $i++): ?>

                        <?php if ($i === $paginaActual): ?>

                            <span
                                class="postulaciones-paginacion-numero activo"
                                aria-current="page">
                                <?= $i ?>
                            </span>

                        <?php else: ?>

                            <a
                                href="<?= e(urlPaginaRendiciones($i, $buscar, $estadoFiltro)) ?>"
                                class="postulaciones-paginacion-numero">
                                <?= $i ?>
                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>


                    <?php if ($finPaginas < $totalPaginas): ?>

                        <?php if ($finPaginas < $totalPaginas - 1): ?>

                            <span class="postulaciones-paginacion-puntos">
                                …
                            </span>

                        <?php endif; ?>


                        <a
                            href="<?= e(
                                urlPaginaRendiciones($totalPaginas, $buscar, $estadoFiltro),
                            ) ?>"
                            class="postulaciones-paginacion-numero">
                            <?= $totalPaginas ?>
                        </a>

                    <?php endif; ?>

                </div>


                <?php if ($paginaActual < $totalPaginas): ?>

                    <a
                        href="<?= e(
                            urlPaginaRendiciones($paginaActual + 1, $buscar, $estadoFiltro),
                        ) ?>"
                        class="postulaciones-paginacion-control">
                        Siguiente
                        <span aria-hidden="true">›</span>
                    </a>

                <?php else: ?>

                    <span
                        class="postulaciones-paginacion-control deshabilitado">
                        Siguiente
                        <span aria-hidden="true">›</span>
                    </span>

                <?php endif; ?>

            </nav>
            <p class="historial-pagina-actual">Página <?= $paginaActual ?> de <?= $totalPaginas ?></p>

        <?php endif; ?>


    </main>


    <footer class="footer">

        <p>
            © <?= date("Y") ?>
            Becas Estudiantiles
        </p>

    </footer>


</body>

</html>
