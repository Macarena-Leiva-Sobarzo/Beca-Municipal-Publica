<?php
/**
 * Datos preparados por el controlador de configuración y gestión de cuentas.
 * @var string|null $avisoUsuarios
 * @var array|null $editarUsuario
 * @var string $buscarUsuario
 * @var string $rolFiltro
 * @var string $estadoFiltro
 * @var array $listaUsuarios
 * @var int $totalUsuariosFiltrados
 * @var int $offsetUsuarios
 * @var int $totalUsuariosGeneral
 * @var int $paginaUsuarios
 * @var int $paginasUsuarios
 * @var Closure(int): string $urlPaginaUsuarios
 */
?>
<?php if (($_SESSION["rol"] ?? "") === "superior"): ?>
    <div id="panel-usuarios">
        <section id="gestion-usuarios" class="admin-detalle-card gestion-usuarios">
            <h2>Gestión de usuarios</h2>
            <p><?= !empty($procesoSoloRendicionUsuarios)
                ? "En este proceso de solo rendición, las cuentas con rol usuario ingresan solo con RUT. Administradores y superiores requieren contraseña."
                : "Crea y administra las cuentas de postulantes, administradores y superiores. Todas las cuentas requieren contraseña." ?></p>
            <?php if ($avisoUsuarios): ?><p role="status" class="usuarios-aviso"><?= escUsuario(
    $avisoUsuarios,
) ?></p><?php endif; ?>
            <h3><?= $editarUsuario ? "Editar usuario" : "Crear usuario" ?></h3>
            <form method="post" action="configuracion.php#gestion-usuarios" class="form-grid" data-solo-rendicion="<?= !empty($procesoSoloRendicionUsuarios) ? "1" : "0" ?>">
                <input type="hidden" name="usuarios_accion" value="<?= $editarUsuario
                    ? "editar"
                    : "crear" ?>"><input type="hidden" name="csrf" value="<?= escUsuario(
    $_SESSION["csrf_usuarios"],
) ?>"><input type="hidden" name="id" value="<?= (int) ($editarUsuario["id"] ?? 0) ?>">
                <div class="campo-form"><label for="cuenta-rut">RUT *</label><input id="cuenta-rut" name="rut" inputmode="text" autocapitalize="characters" maxlength="12" placeholder="12.345.678-5" required value="<?= escUsuario(
                    $editarUsuario["rut"] ?? "",
                ) ?>"></div>
                <div class="campo-form"><label for="cuenta-nombres">Nombres</label><input id="cuenta-nombres" name="nombres" maxlength="100" value="<?= escUsuario(
                    $editarUsuario["nombres"] ?? "",
                ) ?>"></div>
                <div class="campo-form"><label for="cuenta-paterno">Apellido paterno</label><input id="cuenta-paterno" name="apellido_paterno" maxlength="100" value="<?= escUsuario(
                    $editarUsuario["apellido_paterno"] ?? "",
                ) ?>"></div>
                <div class="campo-form"><label for="cuenta-materno">Apellido materno</label><input id="cuenta-materno" name="apellido_materno" maxlength="100" value="<?= escUsuario(
                    $editarUsuario["apellido_materno"] ?? "",
                ) ?>"></div>
                <div class="campo-form"><label for="cuenta-rol">Rol</label><select id="cuenta-rol" name="rol" required>
                    <?php foreach (
                        [
                            "usuario" => "Usuario",
                            "administrador" => "Administrador",
                            "superior" => "Superior",
                        ]
                        as $valor => $texto
                    ): ?><option value="<?= $valor ?>" <?= ($editarUsuario["rol"] ?? "usuario") ===
$valor
    ? "selected"
    : "" ?>><?= $texto ?></option><?php endforeach; ?>
                </select></div>
                <div class="campo-form"><label id="etiqueta-cuenta-clave" for="cuenta-clave"><?= $editarUsuario
                    ? "Nueva contraseña (opcional)"
                    : "Contraseña" ?></label><input id="cuenta-clave" type="password" name="password" minlength="8" autocomplete="new-password"><small id="ayuda-cuenta-clave"><?= $editarUsuario
    ? "Déjala vacía para conservar la contraseña actual."
    : "Mínimo ocho caracteres." ?></small></div>
                <div class="campo-form"><label for="cuenta-confirmacion">Confirmar contraseña</label><input id="cuenta-confirmacion" type="password" name="password_confirmacion" minlength="8" autocomplete="new-password"></div>
                <p class="form-grid-completo">Los nombres y apellidos son opcionales para usuarios normales.</p>
                <div class="form-grid-completo"><button class="btn-formulario-principal" type="submit"><?= $editarUsuario
                    ? "Guardar cambios"
                    : "Crear usuario" ?></button><?php if (
    $editarUsuario
): ?> <a class="admin-btn-limpiar" href="configuracion.php#gestion-usuarios">Cancelar edición</a><?php endif; ?></div>
            </form>
        </section>
        <section id="lista-usuarios" class="admin-detalle-card gestion-usuarios">
            <h2>Usuarios del sistema</h2>
            <p class="usuarios-ayuda-listado">Por defecto se muestran administradores y superiores. Busca por nombre o RUT, o selecciona un rol, para consultar cuentas de usuario.</p>
            <form method="get" action="configuracion.php#lista-usuarios" class="usuarios-filtros">
                <label>Buscar por nombre o RUT<input type="search" placeholder="Buscar por nombre o RUT" name="buscar_usuario" value="<?= escUsuario(
                    $buscarUsuario,
                ) ?>"></label>
                <label>Rol<select name="rol_usuario">
                        <option value="">Todos</option><?php foreach (
                            ["usuario", "administrador", "superior"]
                            as $rol
                        ): ?><option <?= $rolFiltro === $rol
    ? "selected"
    : "" ?> value="<?= $rol ?>"><?= ucfirst($rol) ?></option><?php endforeach; ?>
                    </select></label>
                <label>Estado<select name="estado_usuario">
                        <option value="">Todos</option>
                        <option value="1" <?= $estadoFiltro === "1"
                            ? "selected"
                            : "" ?>>Activo</option>
                        <option value="0" <?= $estadoFiltro === "0"
                            ? "selected"
                            : "" ?>>Desactivado</option>
                    </select></label>
                <button type="submit" class="admin-btn-buscar">Buscar</button><a class="admin-btn-limpiar" href="configuracion.php#lista-usuarios" data-usuarios-limpiar>Limpiar</a>
            </form>
            <div class="admin-listado-resumen">Mostrando <strong><?= $totalUsuariosFiltrados > 0
                ? $offsetUsuarios + 1
                : 0 ?></strong> a <strong><?= min(
    $offsetUsuarios + 25,
    $totalUsuariosFiltrados,
) ?></strong> de <strong><?= $totalUsuariosFiltrados ?></strong> registros. · Total general: <strong><?= $totalUsuariosGeneral ?></strong></div>
            <div class="tabla-contenedor">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>RUT</th><th>Nombre</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (
                            !$listaUsuarios
                        ): ?><tr><td colspan="5">No hay usuarios que coincidan con los filtros.</td></tr><?php endif; ?>
                        <?php foreach ($listaUsuarios as $u): ?><tr>
                                <td><span class="rut-completo"><?= escUsuario(
                                    $u["rut"] ?: "Sin RUT",
                                ) ?></span></td><td><?= escUsuario(
    $u["nombre_completo"] ?: "Sin nombre",
) ?></td>
                                <td><?= escUsuario(ucfirst($u["rol"])) ?></td>
                                <td><span class="<?= $u["activo"]
                                    ? "estado-activo"
                                    : "estado-inactivo" ?>"><?= $u["activo"]
    ? "Activo"
    : "Desactivado" ?></span></td>
                                <td>
                                    <div class="acciones-tabla"><a class="btn-tabla" data-usuarios-editar href="configuracion.php?editar_usuario=<?= (int) $u[
                                        "id"
                                    ] ?>#gestion-usuarios">Editar</a>
                                        <?php if (
                                            (int) $u["id"] !== (int) $_SESSION["usuario_id"]
                                        ): ?>
                                            <form method="post" action="configuracion.php#gestion-usuarios"><input type="hidden" name="usuarios_accion" value="estado"><input type="hidden" name="csrf" value="<?= escUsuario(
                                                $_SESSION["csrf_usuarios"],
                                            ) ?>"><input type="hidden" name="id" value="<?= (int) $u[
    "id"
] ?>"><input type="hidden" name="activo" value="<?= $u["activo"]
    ? 0
    : 1 ?>"><button class="btn-tabla" type="submit"><?= $u["activo"]
    ? "Desactivar"
    : "Activar" ?></button></form>
                                            <form method="post" action="configuracion.php#gestion-usuarios" onsubmit="return confirm('¿Eliminar definitivamente esta cuenta y todas sus postulaciones, rendiciones, datos y documentos? También se eliminarán los documentos pendientes de revisión. Esta acción no se puede deshacer.');"><input type="hidden" name="usuarios_accion" value="eliminar"><input type="hidden" name="csrf" value="<?= escUsuario(
                                                $_SESSION["csrf_usuarios"],
                                            ) ?>"><input type="hidden" name="id" value="<?= (int) $u[
    "id"
] ?>"><button class="btn-tabla eliminar" type="submit">Eliminar</button></form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr><?php endforeach; ?>
                        <?php if (!$listaUsuarios): ?><tr>
                                <td colspan="5">No hay cuentas que coincidan con la búsqueda.</td>
                            </tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <nav class="postulaciones-paginacion" aria-label="Páginas de usuarios del sistema">
                <?php if (
                    $paginaUsuarios > 1
                ): ?><a class="postulaciones-paginacion-control" href="<?= escUsuario(
    $urlPaginaUsuarios($paginaUsuarios - 1),
) ?>">‹ Anterior</a><?php else: ?><span class="postulaciones-paginacion-control deshabilitado">‹ Anterior</span><?php endif; ?>
                <div class="postulaciones-paginacion-paginas">
                    <?php
                    $anteriorUsuarios = 0;
                    for (
                        $numeroUsuarios = 1;
                        $numeroUsuarios <= $paginasUsuarios;
                        $numeroUsuarios++
                    ):

                        if (
                            $numeroUsuarios !== 1 &&
                            $numeroUsuarios !== $paginasUsuarios &&
                            abs($numeroUsuarios - $paginaUsuarios) > 2
                        ) {
                            continue;
                        }
                        if (
                            $anteriorUsuarios &&
                            $numeroUsuarios > $anteriorUsuarios + 1
                        ): ?><span class="postulaciones-paginacion-puntos">…</span><?php endif;
                        $anteriorUsuarios = $numeroUsuarios;
                        ?>
                    <?php if (
                        $numeroUsuarios === $paginaUsuarios
                    ): ?><span class="postulaciones-paginacion-numero activo" aria-current="page"><?= $numeroUsuarios ?></span><?php else: ?><a class="postulaciones-paginacion-numero" href="<?= escUsuario(
    $urlPaginaUsuarios($numeroUsuarios),
) ?>"><?= $numeroUsuarios ?></a><?php endif; ?>
                    <?php
                    endfor;
                    ?>
                </div>
                <?php if (
                    $paginaUsuarios < $paginasUsuarios
                ): ?><a class="postulaciones-paginacion-control" href="<?= escUsuario(
    $urlPaginaUsuarios($paginaUsuarios + 1),
) ?>">Siguiente ›</a><?php else: ?><span class="postulaciones-paginacion-control deshabilitado">Siguiente ›</span><?php endif; ?>
            </nav>
            <p class="historial-pagina-actual">Página <?= $paginaUsuarios ?> de <?= $paginasUsuarios ?></p>
            <p>Eliminar una cuenta borra también sus postulaciones, rendiciones y documentos. Si deseas conservar el historial, utiliza Desactivar.</p>
        </section>
    </div>
    <div id="aviso-accion-usuarios" role="status" aria-live="polite" hidden></div>
    <dialog id="editar-cuenta-dialog" aria-label="Editar usuario"><button type="button" data-cerrar-edicion class="btn-cerrar-edicion">Cerrar</button>
        <div id="contenido-editar-cuenta"></div>
    </dialog>
    <script src="../js/gestion_usuarios.js?v=20261001" defer></script>
<?php endif; ?>
<script src="../js/rut_cuenta.js?v=20261006" defer></script>
