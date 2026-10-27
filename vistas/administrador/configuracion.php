<?php
/**
 * Variables recibidas desde:
 * administrador/configuracion.php
 *
 * @var array $procesosDisponibles
 * @var array|null $proceso
 * @var mysqli_result|null $requisitos
 * @var string|null $mensajeExito
 * @var string|null $mensajeError
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
        Configuración del proceso
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


        <form method="get" class="portal-card">
            <label for="selector_proceso">Proceso que deseas configurar</label>
            <select id="selector_proceso" name="proceso_id">
                <?php foreach ($procesosDisponibles as $item): ?>
                    <option value="<?= (int) $item["id"] ?>" <?= (int) ($proceso["id"] ?? 0) ===
(int) $item["id"]
    ? "selected"
    : "" ?>><?= htmlspecialchars($item["nombre"], ENT_QUOTES, "UTF-8") .
    ($item["activo"] ? "" : " (histórico)") ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-portal">Ver proceso</button>
        </form>



        <div class="admin-encabezado">

            <h1>
                Información del proceso
            </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>

            <p>
                Administra la información pública,
                fechas, instrucciones y requisitos
                del proceso de beca.
            </p>

        </div>


        <?php if ($mensajeExito): ?>

            <div class="mensaje exito">

                <?= htmlspecialchars($mensajeExito, ENT_QUOTES, "UTF-8") ?>

            </div>

        <?php endif; ?>


        <?php if ($mensajeError): ?>

            <div class="mensaje error">

                <?= htmlspecialchars($mensajeError, ENT_QUOTES, "UTF-8") ?>

            </div>

        <?php endif; ?>


        <?php if ($proceso): ?>


            <section class="admin-detalle-card">


                <h2>
                    Configuración del proceso
                </h2>


                <form
                    action="procesar_configuracion.php"
                    method="POST"
                    enctype="multipart/form-data"
                    class="admin-evaluacion">


                    <input
                        type="hidden"
                        name="accion"
                        value="actualizar_proceso">


                    <input
                        type="hidden"
                        name="proceso_id"
                        value="<?= (int) $proceso["id"] ?>">




                    <div class="campo-form">

                        <label for="nombre">
                            Nombre del proceso
                        </label>


                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            maxlength="200"
                            value="<?= htmlspecialchars($proceso["nombre"], ENT_QUOTES, "UTF-8") ?>"
                            required>

                    </div>





                    <div class="campo-form">

                        <label for="instrucciones_pdf">
                            PDF de instrucciones de rendición
                        </label>


                        <input
                            type="file"
                            id="instrucciones_pdf"
                            name="instrucciones_pdf"
                            accept="application/pdf">


                        <small class="campo-ayuda">

                            Solo PDF, máximo 20 MB.

                            Si seleccionas un archivo nuevo,
                            reemplazará al documento actual.

                        </small>


                        <?php if (!empty($proceso["instrucciones_ruta"])): ?>


                            <div class="admin-instrucciones-actual">


                                <div>

                                    <strong>
                                        Documento actualmente publicado
                                    </strong>


                                    <span>

                                        <?= htmlspecialchars(
                                            $proceso["instrucciones_nombre_original"] ?:
                                            "Instrucciones de rendición",
                                            ENT_QUOTES,
                                            "UTF-8",
                                        ) ?>

                                    </span>

                                </div>


                                <div class="admin-instrucciones-botones">


                                    <button
                                        type="button"
                                        class="btn-admin-documento"
                                        id="btnVerInstruccionesAdmin"
                                        data-url="ver_instrucciones.php?proceso=<?= (int) $proceso[
                                            "id"
                                        ] ?>">
                                        Ver PDF
                                    </button>


                                </div>


                            </div>


                        <?php else: ?>


                            <div class="admin-instrucciones-sin-pdf">

                                Aún no existe un PDF de instrucciones
                                publicado para este proceso.

                            </div>


                        <?php endif; ?>


                    </div>


                    <div class="form-grid">


                        <div class="campo-form">

                            <label for="fecha_inicio">
                                Inicio de postulaciones
                            </label>


                            <input
                                type="datetime-local"
                                id="fecha_inicio"
                                name="fecha_inicio"
                                value="<?= htmlspecialchars(
                                    fechaInput($proceso["fecha_inicio"]),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="fecha_cierre">
                                Cierre de postulaciones
                            </label>


                            <input
                                type="datetime-local"
                                id="fecha_cierre"
                                name="fecha_cierre"
                                value="<?= htmlspecialchars(
                                    fechaInput($proceso["fecha_cierre"]),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="fecha_resultados">
                                Publicación de resultados publicación
                            </label>


                            <input
                                type="datetime-local"
                                id="fecha_resultados"
                                name="fecha_resultados"
                                value="<?= htmlspecialchars(
                                    fechaInput($proceso["fecha_resultados"]),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="fecha_inicio_rendicion">
                                Inicio de rendición
                            </label>


                            <input
                                type="datetime-local"
                                id="fecha_inicio_rendicion"
                                name="fecha_inicio_rendicion"
                                value="<?= htmlspecialchars(
                                    fechaInput($proceso["fecha_inicio_rendicion"]),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="fecha_cierre_rendicion">
                                Cierre de rendición
                            </label>


                            <input
                                type="datetime-local"
                                id="fecha_cierre_rendicion"
                                name="fecha_cierre_rendicion"
                                value="<?= htmlspecialchars(
                                    fechaInput($proceso["fecha_cierre_rendicion"]),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>"
                                required>

                        </div>

                        <div class="campo-form">

                            <label for="fecha_resultados_rendicion">Publicación de resultados rendición</label>
                            <input type="datetime-local" id="fecha_resultados_rendicion" name="fecha_resultados_rendicion"
                                value="<?= htmlspecialchars(
                                    str_replace(
                                        " ",
                                        "T",
                                        substr($proceso["fecha_resultados_rendicion"] ?? "", 0, 16),
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>">
                            <small class="campo-ayuda">Fecha independiente del resultado de postulación. Al publicarse, se habilita el envío de correos de rendición.</small>
                        </div>


                    </div>


                    <button
                        type="submit"
                        class="btn-formulario-principal">
                        Guardar configuración
                    </button>


                </form>


            </section>


            <section class="admin-detalle-card">


                <div class="admin-seccion-titulo">

                    <div>

                        <h2>
                            Requisitos de postulación
                        </h2>

                        <p>
                            Agrega, modifica o elimina los requisitos
                            que verá el postulante.
                        </p>

                    </div>

                </div>


                <form
                    action="procesar_configuracion.php"
                    method="POST"
                    class="admin-evaluacion">


                    <input
                        type="hidden"
                        name="accion"
                        value="crear_requisito">


                    <input
                        type="hidden"
                        name="proceso_id"
                        value="<?= (int) $proceso["id"] ?>">


                    <div class="campo-form">

                        <label>
                            Título
                        </label>


                        <input
                            type="text"
                            name="titulo"
                            placeholder="Ej: Ser alumno regular"
                            required>

                    </div>


                    <div class="campo-form">

                        <label>
                            Descripción
                        </label>


                        <textarea
                            name="descripcion"
                            rows="4"
                            required></textarea>

                    </div>


                    <div class="campo-form">

                        <label>
                            Orden
                        </label>


                        <input
                            type="number"
                            name="orden"
                            min="1"
                            value="1"
                            required>

                    </div>


                    <button
                        type="submit"
                        class="btn-formulario-principal">
                        Agregar requisito
                    </button>


                </form>


                <?php if ($requisitos && $requisitos->num_rows > 0): ?>


                    <div class="admin-info-lista">


                        <?php while ($requisito = $requisitos->fetch_assoc()): ?>


                            <div class="admin-info-item">


                                <form
                                    action="procesar_configuracion.php"
                                    method="POST">


                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="actualizar_requisito">


                                    <input
                                        type="hidden"
                                        name="requisito_id"
                                        value="<?= (int) $requisito["id"] ?>">


                                    <div class="campo-form">

                                        <label>
                                            Título
                                        </label>


                                        <input
                                            type="text"
                                            name="titulo"
                                            value="<?= htmlspecialchars(
                                                $requisito["titulo"],
                                                ENT_QUOTES,
                                                "UTF-8",
                                            ) ?>"
                                            required>

                                    </div>


                                    <div class="campo-form">

                                        <label>
                                            Descripción
                                        </label>


                                        <textarea
                                            name="descripcion"
                                            rows="4"
                                            required><?= htmlspecialchars(
                                                $requisito["descripcion"],
                                                ENT_QUOTES,
                                                "UTF-8",
                                            ) ?></textarea>

                                    </div>


                                    <div class="form-grid">


                                        <div class="campo-form">

                                            <label>
                                                Orden
                                            </label>


                                            <input
                                                type="number"
                                                name="orden"
                                                min="1"
                                                value="<?= (int) $requisito["orden"] ?>"
                                                required>

                                        </div>


                                        <div class="campo-form">

                                            <label>
                                                Visible
                                            </label>


                                            <input
                                                type="checkbox"
                                                name="activo"
                                                value="1"
                                                <?= (int) $requisito["activo"] === 1
                                                    ? "checked"
                                                    : "" ?>>

                                        </div>


                                    </div>


                                    <button
                                        type="submit"
                                        class="btn-guardar-requisito">
                                        Guardar requisito
                                    </button>


                                </form>


                                <form
                                    action="procesar_configuracion.php"
                                    method="POST"
                                    class="form-eliminar-requisito">


                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="eliminar_requisito">


                                    <input
                                        type="hidden"
                                        name="requisito_id"
                                        value="<?= (int) $requisito["id"] ?>">


                                    <button
                                        type="submit"
                                        class="btn-eliminar-requisito"
                                        onclick="return confirm(
                                        '¿Deseas eliminar este requisito?'
                                    );">
                                        Eliminar
                                    </button>


                                </form>


                            </div>


                        <?php endwhile; ?>


                    </div>


                <?php else: ?>


                    <div class="admin-instrucciones-sin-pdf">

                        No existen requisitos registrados
                        para este proceso.

                    </div>


                <?php endif; ?>


            </section>


        <?php else: ?>


            <div class="mensaje error">

                No existe un proceso de beca activo.

            </div>


        <?php endif; ?>


        <?php if (($_SESSION["rol"] ?? "") === "superior") {
            require __DIR__ . "/gestion_usuarios.php";
        } ?>
    </main>


    <?php if ($proceso && !empty($proceso["instrucciones_ruta"])): ?>


        <div
            class="admin-modal"
            id="modalInstruccionesAdmin"
            aria-hidden="true">


            <div
                class="admin-modal-contenido"
                role="dialog"
                aria-modal="true"
                aria-labelledby="tituloModalInstrucciones">

                <div class="admin-modal-header">


                    <div>

                        <h2 id="tituloModalInstrucciones">
                            Instrucciones de rendición
                        </h2>

                        <p>
                            Documento actualmente publicado
                            para este proceso.
                        </p>

                    </div>


                    <button
                        type="button"
                        class="admin-modal-cerrar"
                        id="cerrarInstruccionesAdmin"
                        aria-label="Cerrar visor">
                        ×
                    </button>


                </div>

                <iframe
                    id="visorInstruccionesAdmin"
                    class="admin-documento-visor"
                    src=""
                    title="PDF de instrucciones de rendición"></iframe>


            </div>


        </div>


    <?php endif; ?>


    <footer class="footer">

        <p>
            © <?= date("Y") ?> Becas Estudiantiles
        </p>

    </footer>


    <script>
        document.addEventListener(
            "DOMContentLoaded",
            function() {

                const botonVerPdf =
                    document.getElementById(
                        "btnVerInstruccionesAdmin"
                    );


                const modal =
                    document.getElementById(
                        "modalInstruccionesAdmin"
                    );


                const visor =
                    document.getElementById(
                        "visorInstruccionesAdmin"
                    );


                const botonCerrar =
                    document.getElementById(
                        "cerrarInstruccionesAdmin"
                    );

                if (
                    !botonVerPdf ||
                    !modal ||
                    !visor ||
                    !botonCerrar
                ) {

                    return;
                }


                function abrirModalPdf() {

                    const url =
                        botonVerPdf.dataset.url;


                    if (!url) {

                        return;
                    }


                    visor.src =
                        url;


                    modal.classList.add(
                        "visible"
                    );


                    modal.setAttribute(
                        "aria-hidden",
                        "false"
                    );


                    document.body.classList.add(
                        "modal-abierto"
                    );
                }


                function cerrarModalPdf() {

                    modal.classList.remove(
                        "visible"
                    );


                    modal.setAttribute(
                        "aria-hidden",
                        "true"
                    );


                    document.body.classList.remove(
                        "modal-abierto"
                    );

                    visor.src =
                        "";
                }

                window.addEventListener("message", function(evento) {
                    if (
                        evento.origin === window.location.origin &&
                        evento.data &&
                        evento.data.tipo === "becas-cerrar-visor-documento"
                    ) {
                        cerrarModalPdf();
                    }
                });


                botonVerPdf.addEventListener(
                    "click",
                    abrirModalPdf
                );


                botonCerrar.addEventListener(
                    "click",
                    cerrarModalPdf
                );

                modal.addEventListener(
                    "click",
                    function(event) {

                        if (
                            event.target ===
                            modal
                        ) {

                            cerrarModalPdf();
                        }
                    }
                );


                document.addEventListener(
                    "keydown",
                    function(event) {

                        if (
                            event.key === "Escape" &&
                            modal.classList.contains(
                                "visible"
                            )
                        ) {

                            cerrarModalPdf();
                        }
                    }
                );

            }
        );
    </script>


</body>

</html>
