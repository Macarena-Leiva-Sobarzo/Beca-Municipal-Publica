<?php
/**
 * Variables recibidas desde:
 * superior/postulaciones.php
 *
 * @var string $busqueda
 * @var string $estado
 * @var int $porPagina
 * @var int $paginaActual
 * @var int $totalPostulaciones
 * @var int $totalPaginas
 * @var int $offset
 * @var mysqli_result $resultado
 * @var string|null $mensajeExito
 * @var string|null $mensajeError
 * @var array $estadisticasPostulaciones
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
        Postulaciones
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


    <main class="admin-rendiciones">



        <div class="portal-encabezado">

            <h1>
                Revisar postulaciones
            </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>

            <p>
                Busca por nombre, RUT o correo y revisa los
                antecedentes y documentos de cada postulante.
            </p>

        </div>


        <div class="admin-estadisticas">


            <div class="admin-stat stat-total">

                <span>
                    Total de postulaciones
                </span>

                <strong>
                    <?= $estadisticasPostulaciones["total"] ?>
                </strong>

            </div>


            <div class="admin-stat stat-revision">

                <span>
                    En revisión
                </span>

                <strong>
                    <?= $estadisticasPostulaciones["revision"] ?>
                </strong>

            </div>


            <div class="admin-stat stat-beneficiados">

                <span>
                    Beneficiados
                </span>

                <strong>
                    <?= $estadisticasPostulaciones["beneficiados"] ?>
                </strong>

            </div>


            <div class="admin-stat stat-rechazados">

                <span>
                    No beneficiados
                </span>

                <strong>
                    <?= $estadisticasPostulaciones["rechazados"] ?>
                </strong>

            </div>


        </div>


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


        <div class="admin-rendiciones-filtros">

            <form
                method="GET"
                action="postulaciones.php">

                <div class="campo">

                    <input
                        type="text"
                        name="buscar"
                        placeholder="Buscar por nombre, RUT o correo"
                        value="<?= e($busqueda) ?>">

                </div>


                <div class="campo">

                    <select
                        name="estado">

                        <option value="">
                            Todos los estados
                        </option>


                        <option
                            value="en_revision"
                            <?= $estado === "en_revision" ? "selected" : "" ?>>
                            En revisión
                        </option>


                        <option
                            value="beneficiado"
                            <?= $estado === "beneficiado" ? "selected" : "" ?>>
                            Beneficiado
                        </option>


                        <option
                            value="no_beneficiado"
                            <?= $estado === "no_beneficiado" ? "selected" : "" ?>>
                            No beneficiado
                        </option>

                    </select>

                </div>


                <button
                    type="submit"
                    class="admin-btn-buscar">
                    Buscar
                </button>


                <a
                    href="postulaciones.php"
                    class="admin-btn-limpiar">
                    Limpiar
                </a>

            </form>

        </div>


        <div class="admin-listado-resumen">Mostrando <strong><?= $totalPostulaciones > 0
            ? $offset + 1
            : 0 ?></strong> a <strong><?= min(
    $offset + $porPagina,
    $totalPostulaciones,
) ?></strong> de <strong><?= $totalPostulaciones ?></strong> registros. · Total general: <strong><?= $estadisticasPostulaciones[
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
                            Carnet
                        </th>

                        <th>
                            Alumno regular
                        </th>

                        <th>
                            Concentración de notas
                        </th>
<th>Registro Social de Hogares</th><th>Acreditación de gratuidad</th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Acción
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if ($resultado->num_rows === 0): ?>


                        <tr>

                            <td colspan="10">

                                No se encontraron postulaciones.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php while ($fila = $resultado->fetch_assoc()): ?>


                            <tr>

                                <td>

                                    <span class="admin-persona-nombre">

                                        <?= e(
                                            $fila["nombres"] .
                                                " " .
                                                $fila["apellido_paterno"] .
                                                " " .
                                                $fila["apellido_materno"],
                                        ) ?>

                                    </span>


                                    <span class="admin-persona-correo">

                                        <?= e($fila["correo_electronico"]) ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="rut-completo"><?= e($fila["rut"]) ?></span>

                                </td>


                                <td>

                                    <?= e(fecha($fila["fecha_postulacion"])) ?>

                                </td>


                                <td>

                                    <?= mostrarEstadoDocumento($fila["carnet_estado"] ?? null) ?>

                                </td>


                                <td>

                                    <?= mostrarEstadoDocumento(
                                        $fila["alumno_regular_estado"] ?? null,
                                    ) ?>

                                </td>


                                <td>

                                    <?= mostrarEstadoDocumento(
                                        $fila["certificado_notas_estado"] ?? null,
                                    ) ?>

                                </td>
<td><?= mostrarEstadoDocumento(
    $fila["registro_social_estado"] ?? null,
) ?></td><td><?= mostrarEstadoDocumento($fila["acreditacion_gratuidad_estado"] ?? null) ?></td>


                                <td>

                                    <span
                                        class="
                                        admin-estado-rendicion
                                        <?= e(claseEstadoPostulacion($fila["estado"])) ?>
                                    ">

                                        <?= e(textoEstadoPostulacion($fila["estado"])) ?>

                                    </span>

                                </td>


                                <td>

                                    <a
                                        href="detalle_postulacion.php?id=<?= (int) $fila["id"] ?>"
                                        class="admin-btn-revisar">
                                        Revisar
                                    </a>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>


        <?php if ($totalPaginas >= 1): ?>

            <nav
                class="postulaciones-paginacion"
                aria-label="Paginación de postulaciones">


                <?php if ($paginaActual > 1): ?>

                    <a
                        href="<?= e(urlPagina($paginaActual - 1, $busqueda, $estado)) ?>"
                        class="postulaciones-paginacion-control">

                        <span aria-hidden="true">
                            ‹
                        </span>

                        Anterior

                    </a>

                <?php else: ?>

                    <span
                        class="postulaciones-paginacion-control deshabilitado">

                        <span aria-hidden="true">
                            ‹
                        </span>

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
                            href="<?= e(urlPagina(1, $busqueda, $estado)) ?>"
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
                                href="<?= e(urlPagina($i, $busqueda, $estado)) ?>"
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
                            href="<?= e(urlPagina($totalPaginas, $busqueda, $estado)) ?>"
                            class="postulaciones-paginacion-numero">
                            <?= $totalPaginas ?>
                        </a>


                    <?php endif; ?>


                </div>


                <?php if ($paginaActual < $totalPaginas): ?>

                    <a
                        href="<?= e(urlPagina($paginaActual + 1, $busqueda, $estado)) ?>"
                        class="postulaciones-paginacion-control">

                        Siguiente

                        <span aria-hidden="true">
                            ›
                        </span>

                    </a>

                <?php else: ?>

                    <span
                        class="postulaciones-paginacion-control deshabilitado">

                        Siguiente

                        <span aria-hidden="true">
                            ›
                        </span>

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
