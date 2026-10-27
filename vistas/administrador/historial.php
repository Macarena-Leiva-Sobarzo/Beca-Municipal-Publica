<?php
/**
 * Datos preparados por el controlador de historial del rol actual.
 * @var array $listados
 * @var array $anios
 * @var array $filtro
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Historial por año</title>
    <link rel="stylesheet" href="../css/estilos.css?v=mobile-20261020&espaciado-20261004">
</head>

<body>
    <header class="header">
        <div class="logo">Becas Estudiantiles</div>
        <nav class="nav">
            <span class="sesion-rol">Rol: <?= htmlspecialchars(
                (string) ($_SESSION["rol"] ?? ""),
                ENT_QUOTES,
                "UTF-8",
            ) ?></span>
            <a href="inicio.php">Inicio</a>
            <a href="postulaciones.php">Postulaciones</a>
            <a href="rendiciones.php">Rendiciones</a>
            <a href="archivos_descargar.php">Descargar</a>
            <a href="historial.php" aria-current="page">Historial</a>
            <a href="segundo_semestre.php">Segundo semestre</a>

            <a href="../auth/cerrar_sesion.php">Cerrar sesión</a>
        </nav>
    </header>
    <main class="admin-rendiciones historial-pagina">

        <div class="portal-encabezado">
            <h1>Historial por año</h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>
            <p>Consulta las postulaciones y las rendiciones enviadas por año de la beca.</p>
        </div>
        <div class="admin-estadisticas">
            <div class="admin-stat stat-total"><span>Postulaciones encontradas</span><strong><?= $listados[
                "postulaciones"
            ]["total"] ?></strong></div>
            <div class="admin-stat stat-beneficiados"><span>Beneficiarios</span><strong><?= $listados[
                "rendiciones"
            ][
                "beneficiarios"
            ] ?></strong></div><div class="admin-stat stat-revision"><span>Rendiciones</span><strong><?= $listados[
    "rendiciones"
]["rendiciones_enviadas"] ?></strong></div>
        </div>
        <section class="admin-rendiciones-filtros">
            <form method="get" class="historial-filtros">
                <div class="campo"><label for="anio">Año de postulación</label><select id="anio" name="anio">
                        <option value="">Todos los años</option><?php foreach (
                            $anios
                            as $a
                        ): ?><option value="<?= (int) $a["anio"] ?>" <?= $filtro["anio"] ===
(int) $a["anio"]
    ? "selected"
    : "" ?>><?= (int) $a["anio"] ?></option><?php endforeach; ?>
                    </select></div>
                <div class="campo"><label for="buscar">Nombre o RUT</label><input id="buscar" name="buscar" maxlength="150" value="<?= escHistorial(
                    $filtro["buscar"],
                ) ?>" placeholder="Escribe el nombre o RUT"></div>
                <button class="admin-btn-buscar" type="submit">Buscar</button><a class="admin-btn-limpiar" href="historial.php">Limpiar</a>
            </form>
            <form action="descargar_historial.php" method="post" class="historial-exportar">
                <input type="hidden" name="token" value="<?= escHistorial(
                    $_SESSION["token_historial"],
                ) ?>"><input type="hidden" name="anio" value="<?= escHistorial(
    $filtro["anio"],
) ?>"><input type="hidden" name="buscar" value="<?= escHistorial($filtro["buscar"]) ?>">
                <button class="admin-btn-revisar" name="formato" value="pdf">Descargar PDF</button><button class="admin-btn-revisar" name="formato" value="excel">Descargar Excel</button>
                <span>Incluye ambos listados completos con los filtros aplicados.</span>
            </form>
        </section>
        <?php foreach ($listados as $tipo => $lista): ?>
            <section class="historial-listado" id="<?= $tipo ?>">
                <h2><?= ucfirst($tipo) ?></h2>
                <div class="admin-listado-resumen">Mostrando <strong><?= $lista["total"] > 0
                    ? ($lista["pagina"] - 1) * 25 + 1
                    : 0 ?></strong> a <strong><?= min(
    $lista["pagina"] * 25,
    $lista["total"],
) ?></strong> de <strong><?= $lista[
    "total"
] ?></strong> registros. · Total general: <strong><?= $lista["total_general"] ?></strong></div>
                <div class="admin-tabla-contenedor">
                    <table class="admin-rendiciones-tabla">
                        <thead>
                            <tr>
                                <th>Año</th>
                                <th>Nombre</th>
                                <th>RUT</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th><?= $tipo === "postulaciones"
                                    ? "Resultado de postulación"
                                    : "Rendición enviada" ?></th><?php if (
    $tipo === "rendiciones"
): ?><th>Estado de revisión</th><?php endif; ?><th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista["filas"] as $fila): ?><tr>
                                    <td><?= escHistorial($fila["anio"] ?? "Sin año") ?></td>
                                    <td><?= escHistorial($fila["nombre"]) ?></td>
                                    <td><span class="rut-completo"><?= escHistorial(
                                        $fila["rut"],
                                    ) ?></span></td>
                                    <td><?= escHistorial($fila["correo"]) ?></td>
                                    <td><?= escHistorial($fila["telefono"]) ?></td>
                                    <td><span class="admin-estado-rendicion <?= $tipo ===
                                    "postulaciones"
                                        ? ($fila["estado"] === "beneficiado"
                                            ? "aprobada"
                                            : ($fila["estado"] === "no_beneficiado"
                                                ? "rechazada"
                                                : "en_revision"))
                                        : (!$fila["fecha_envio"]
                                            ? "pendiente"
                                            : match ($fila["estado"]) {
                                                "rechazada" => "rechazada",
                                                "en_revision", "enviada" => "en_revision",
                                                default => "aprobada",
                                            }) ?>"><?= escHistorial(
    textoHistorial($fila, $tipo),
) ?></span></td><?php if (
    $tipo === "rendiciones"
): ?><td><span class="admin-estado-rendicion <?= match ($fila["estado"]) {
    "aprobada" => "aprobada",
    "rechazada" => "rechazada",
    "en_revision", "enviada" => "en_revision",
    default => "pendiente",
} ?>"><?= escHistorial(
    ucfirst(str_replace("_", " ", $fila["estado"])),
) ?></span></td><?php endif; ?><td><?php if (
    $fila["id"]
): ?><a class="admin-btn-revisar" href="<?= $tipo === "postulaciones"
    ? "detalle_postulacion.php"
    : "detalle_rendicion.php" ?>?id=<?= (int) $fila[
    "id"
] ?>">Revisar</a><?php else: ?>Sin envío<?php endif; ?></td>
                                </tr><?php endforeach; ?>
                            <?php if (!$lista["filas"]): ?><tr>
                                    <td colspan="<?= $tipo === "postulaciones"
                                        ? 7
                                        : 8 ?>">No hay registros que coincidan con los filtros.</td>
                                </tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                $params = [
                    "anio" => $filtro["anio"] ?? "",
                    "buscar" => $filtro["buscar"],
                    "pagina_postulaciones" => $listados["postulaciones"]["pagina"],
                    "pagina_rendiciones" => $listados["rendiciones"]["pagina"],
                ];
                $urlPagina = static function (int $pagina) use ($params, $tipo): string {
                    $params["pagina_" . $tipo] = $pagina;
                    return "?" . http_build_query($params) . "#" . $tipo;
                };
                ?>
                <nav class="postulaciones-paginacion" aria-label="Páginas de <?= $tipo ?>">
                    <?php if (
                        $lista["pagina"] > 1
                    ): ?><a class="postulaciones-paginacion-control" href="<?= escHistorial(
    $urlPagina($lista["pagina"] - 1),
) ?>">‹ Anterior</a><?php else: ?><span class="postulaciones-paginacion-control deshabilitado">‹ Anterior</span><?php endif; ?>
                    <div class="postulaciones-paginacion-paginas">
                        <?php
                        $paginasMostrar = array_unique(
                            array_merge(
                                [1],
                                range(
                                    max(1, $lista["pagina"] - 2),
                                    min($lista["paginas"], $lista["pagina"] + 2),
                                ),
                                [$lista["paginas"]],
                            ),
                        );
                        $anterior = 0;
                        foreach ($paginasMostrar as $pagina): ?>
                            <?php if (
                                $anterior &&
                                $pagina > $anterior + 1
                            ): ?><span class="postulaciones-paginacion-puntos">…</span><?php endif; ?>
                            <?php
                            if (
                                $pagina === $lista["pagina"]
                            ): ?><span class="postulaciones-paginacion-numero activo" aria-current="page"><?= $pagina ?></span><?php else: ?><a class="postulaciones-paginacion-numero" href="<?= escHistorial(
    $urlPagina($pagina),
) ?>"><?= $pagina ?></a><?php endif;
                            $anterior = $pagina;
                            endforeach;
                        ?>
                    </div>
                    <?php if (
                        $lista["pagina"] < $lista["paginas"]
                    ): ?><a class="postulaciones-paginacion-control" href="<?= escHistorial(
    $urlPagina($lista["pagina"] + 1),
) ?>">Siguiente ›</a><?php else: ?><span class="postulaciones-paginacion-control deshabilitado">Siguiente ›</span><?php endif; ?>
                </nav>
                <p class="historial-pagina-actual">Página <?= $lista["pagina"] ?> de <?= $lista[
     "paginas"
 ] ?></p>
            </section><?php endforeach; ?>
    </main>
    <footer class="footer">
        <p>© <?= date("Y") ?> Becas Estudiantiles</p>
    </footer>
</body>

</html>
