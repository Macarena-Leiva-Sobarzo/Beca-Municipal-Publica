<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("La configuración está reservada al superior.");
}
$permiso = $conn->prepare("SELECT id FROM usuarios WHERE id=? AND rol='superior' AND activo=1");
$permiso->bind_param("i", $_SESSION["usuario_id"]);
$permiso->execute();
if (!$permiso->get_result()->fetch_assoc()) {
    http_response_code(403);
    mostrarAvisoAccesoBecas("La cuenta no tiene permisos vigentes.");
}
$permiso->close();

// Controlador propio del superior: esta ruta no ejecuta controladores de administrador.
require_once __DIR__ . "/../config/sesion.php";
if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso reservado al superior.");
}

/** Valida y guarda fechas, requisitos e instrucciones del proceso. La publicación de postulaciones y la de rendiciones tienen plazos independientes. */

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    !in_array($_SESSION["rol"], ["administrador", "superior"], true)
) {
    header("Location: ../auth/login.php");

    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header(
        "Location: configuracion.php" .
            (!empty($_POST["proceso_id"]) ? "?proceso_id=" . (int) $_POST["proceso_id"] : ""),
    );

    exit();
}

function volverConfiguracion(string $mensaje, bool $exito = false): void
{
    if ($exito) {
        $_SESSION["admin_exito"] = $mensaje;
    } else {
        $_SESSION["admin_error"] = $mensaje;
    }

    header(
        "Location: configuracion.php" .
            (!empty($_POST["proceso_id"]) ? "?proceso_id=" . (int) $_POST["proceso_id"] : ""),
    );

    exit();
}

function rutaFisicaProyecto(string $rutaRelativa): string
{
    return dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $rutaRelativa);
}

$accion = trim($_POST["accion"] ?? "");

if (in_array($accion, ["desactivar_proceso", "eliminar_proceso"], true)) {
    if (
        !is_string($_POST["csrf"] ?? null) ||
        empty($_SESSION["csrf_proceso"]) ||
        !hash_equals($_SESSION["csrf_proceso"], $_POST["csrf"])
    ) {
        volverConfiguracion("La sesión del formulario venció. Recarga la página.");
    }
    $id = filter_input(INPUT_POST, "proceso_id", FILTER_VALIDATE_INT);
    if (!$id) {
        volverConfiguracion("Selecciona un proceso válido.");
    }
    try {
        if ($accion === "eliminar_proceso") {
            if (($_POST["confirmar_eliminacion"] ?? "") !== "1") {
                volverConfiguracion("Debes confirmar la eliminación del proceso.");
            }
            require_once __DIR__ . "/eliminar_proceso.php";
            $pendientes = eliminarProcesoCompleto($conn, $id, dirname(__DIR__));
            $_SESSION["admin_exito"] = $pendientes
                ? "Proceso y registros eliminados. Algunos archivos requieren revisar los permisos del servidor."
                : "Proceso eliminado junto con sus postulaciones, rendiciones y documentos. Las cuentas se conservaron.";
            header("Location: configuracion.php");
            exit();
        }
        $stmt = $conn->prepare("UPDATE procesos_beca SET activo=0 WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $cambios = $stmt->affected_rows;
        $stmt->close();
        volverConfiguracion(
            $cambios
                ? "Proceso desactivado correctamente."
                : "El proceso ya estaba inactivo o no existe.",
            (bool) $cambios,
        );
    } catch (Throwable $error) {
        error_log("Proceso: " . $error->getMessage());
        volverConfiguracion(
            "No se pudo completar la operación. Los cambios no confirmados se revirtieron.",
        );
    }
}

// Crear un borrador no altera la convocatoria actualmente activa.
if ($accion === "crear_proceso" || $accion === "activar_proceso") {
    if (
        !is_string($_POST["csrf"] ?? null) ||
        empty($_SESSION["csrf_proceso"]) ||
        !hash_equals($_SESSION["csrf_proceso"], $_POST["csrf"])
    ) {
        volverConfiguracion("La sesión del formulario venció. Recarga la página.");
    }
    try {
        if ($accion === "crear_proceso") {
            $nombre = trim((string) ($_POST["nombre"] ?? ""));
            if ($nombre === "" || mb_strlen($nombre) > 200) {
                volverConfiguracion("Indica un nombre de proceso de hasta 200 caracteres.");
            }
            $tipo =
                ($_POST["tipo_proceso"] ?? "") === "solo_rendicion" ? "solo_rendicion" : "completo";
            $stmt = $conn->prepare(
                "INSERT INTO procesos_beca (nombre,descripcion,activo,tipo_proceso) VALUES (?,'',0,?)",
            );
            $stmt->bind_param("ss", $nombre, $tipo);
            $stmt->execute();
            $procesoId = $conn->insert_id;
            $stmt->close();
            $_SESSION["admin_exito"] =
                "Proceso creado. Configura sus fechas y requisitos antes de activarlo.";
        } else {
            $procesoId = (int) ($_POST["proceso_id"] ?? 0);
            $conn->begin_transaction();
            // Serializa activaciones para mantener una sola convocatoria activa.
            $conn->query("SELECT id FROM procesos_beca ORDER BY id FOR UPDATE");
            $stmt = $conn->prepare("SELECT * FROM procesos_beca WHERE id=?");
            $stmt->bind_param("i", $procesoId);
            $stmt->execute();
            $proceso = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (
                !$proceso ||
                ($proceso["tipo_proceso"] === "completo" &&
                    (!$proceso["fecha_inicio"] ||
                        !$proceso["fecha_cierre"] ||
                        !$proceso["fecha_resultados"])) ||
                !$proceso["fecha_inicio_rendicion"] ||
                !$proceso["fecha_cierre_rendicion"] ||
                !$proceso["fecha_resultados_rendicion"]
            ) {
                throw new RuntimeException(
                    "Configura todas las fechas antes de activar el proceso.",
                );
            }
            $conn->query("UPDATE procesos_beca SET activo=0");
            $stmt = $conn->prepare("UPDATE procesos_beca SET activo=1 WHERE id=?");
            $stmt->bind_param("i", $procesoId);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            $_SESSION["admin_exito"] = "Proceso activado correctamente.";
        }
        header("Location: configuracion.php?proceso_id=" . $procesoId);
        exit();
    } catch (Throwable $error) {
        if ($accion === "activar_proceso") {
            $conn->rollback();
        }
        error_log("Proceso: " . $error->getMessage());
        volverConfiguracion(
            $error instanceof RuntimeException
                ? $error->getMessage()
                : "No se pudo guardar el proceso.",
        );
    }
}

if ($accion === "actualizar_rendicion_sola") {
    if (
        !is_string($_POST["csrf"] ?? null) ||
        empty($_SESSION["csrf_proceso"]) ||
        !hash_equals($_SESSION["csrf_proceso"], $_POST["csrf"])
    ) {
        volverConfiguracion("Recarga la página antes de guardar.");
    }
    $id = (int) ($_POST["proceso_id"] ?? 0);
    $nombre = trim((string) ($_POST["nombre"] ?? ""));
    $fechas = [];
    foreach (
        ["fecha_inicio_rendicion", "fecha_cierre_rendicion", "fecha_resultados_rendicion"]
        as $campo
    ) {
        $fecha = DateTimeImmutable::createFromFormat(
            "!Y-m-d\TH:i",
            (string) ($_POST[$campo] ?? ""),
            new DateTimeZone("America/Santiago"),
        );
        if (!$fecha || $fecha->format("Y-m-d\TH:i") !== ($_POST[$campo] ?? "")) {
            volverConfiguracion("Completa las fechas con valores válidos.");
        }
        $fechas[] = $fecha->format("Y-m-d H:i:s");
    }
    if ($nombre === "" || mb_strlen($nombre) > 200 || $fechas[1] <= $fechas[0]) {
        volverConfiguracion(
            "Revisa el nombre y comprueba que el cierre sea posterior al inicio de rendición.",
        );
    }
    $procesoId = $id;
    $consultaProceso = $conn->prepare(
        "SELECT id FROM procesos_beca WHERE id=? AND tipo_proceso='solo_rendicion'",
    );
    $consultaProceso->bind_param("i", $id);
    $consultaProceso->execute();
    $procesoValido = $consultaProceso->get_result()->num_rows === 1;
    $consultaProceso->close();
    if (!$procesoValido) {
        volverConfiguracion("Selecciona un proceso de solo rendición válido.");
    }
    $nuevoNombreOriginal = null;

    $nuevoNombreArchivo = null;

    $nuevaRutaBD = null;

    $nuevaRutaFisica = null;

    if (
        isset($_FILES["instrucciones_pdf"]) &&
        $_FILES["instrucciones_pdf"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {
        $archivo = $_FILES["instrucciones_pdf"];

        if ($archivo["error"] !== UPLOAD_ERR_OK) {
            volverConfiguracion("No fue posible recibir el PDF de instrucciones.");
        }

        if (!is_uploaded_file($archivo["tmp_name"])) {
            volverConfiguracion("El PDF de instrucciones no fue recibido correctamente.");
        }

        if ((int) $archivo["size"] <= 0) {
            volverConfiguracion("El PDF de instrucciones está vacío.");
        }

        if ((int) $archivo["size"] > 5 * 1024 * 1024) {
            volverConfiguracion("El PDF de instrucciones no puede superar los 5 MB.");
        }

        $extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));

        if ($extension !== "pdf") {
            volverConfiguracion("Las instrucciones deben estar en formato PDF.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $mime = $finfo->file($archivo["tmp_name"]);

        if ($mime !== "application/pdf") {
            volverConfiguracion("El archivo seleccionado no corresponde a un PDF válido.");
        }

        $fp = fopen($archivo["tmp_name"], "rb");

        if ($fp === false) {
            volverConfiguracion("No fue posible analizar el PDF de instrucciones.");
        }

        $cabecera = fread($fp, 5);

        fclose($fp);

        if ($cabecera !== "%PDF-") {
            volverConfiguracion("El archivo no posee una estructura PDF válida.");
        }

        $directorio =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            "documentos" .
            DIRECTORY_SEPARATOR .
            "procesos" .
            DIRECTORY_SEPARATOR .
            $procesoId;

        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            volverConfiguracion("No fue posible crear la carpeta de instrucciones.");
        }

        $nuevoNombreArchivo = "instrucciones_" . bin2hex(random_bytes(16)) . ".pdf";

        $nuevoNombreOriginal = basename($archivo["name"]);

        $nuevaRutaFisica = $directorio . DIRECTORY_SEPARATOR . $nuevoNombreArchivo;

        $nuevaRutaBD = "documentos/procesos/" . $procesoId . "/" . $nuevoNombreArchivo;

        if (!move_uploaded_file($archivo["tmp_name"], $nuevaRutaFisica)) {
            volverConfiguracion("No fue posible guardar el PDF de instrucciones.");
        }
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "UPDATE procesos_beca SET nombre=?,fecha_inicio_rendicion=?,fecha_cierre_rendicion=?,fecha_resultados_rendicion=?,instrucciones_nombre_original=COALESCE(?,instrucciones_nombre_original),instrucciones_nombre_archivo=COALESCE(?,instrucciones_nombre_archivo),instrucciones_ruta=COALESCE(?,instrucciones_ruta) WHERE id=? AND tipo_proceso='solo_rendicion'",
        );
        $stmt->bind_param(
            "sssssssi",
            $nombre,
            $fechas[0],
            $fechas[1],
            $fechas[2],
            $nuevoNombreOriginal,
            $nuevoNombreArchivo,
            $nuevaRutaBD,
            $id,
        );
        $stmt->execute();
        $stmt->close();
        require_once __DIR__ . "/../config/resultados_rendicion.php";
        sincronizarResultadosRendicion($conn, $id);
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        if ($nuevaRutaFisica && is_file($nuevaRutaFisica)) {
            unlink($nuevaRutaFisica);
        }
        error_log("Configuración de rendición: " . $error->getMessage());
        volverConfiguracion("No fue posible guardar la configuración de rendición.");
    }
    volverConfiguracion("Configuración de rendición guardada correctamente.", true);
}

if ($accion === "actualizar_proceso") {
    $procesoId = filter_input(INPUT_POST, "proceso_id", FILTER_VALIDATE_INT);

    $nombre = trim($_POST["nombre"] ?? "");

    $fechaInicio = trim($_POST["fecha_inicio"] ?? "");

    $fechaCierre = trim($_POST["fecha_cierre"] ?? "");

    $fechaResultados = trim($_POST["fecha_resultados"] ?? "");

    $fechaInicioRendicion = trim($_POST["fecha_inicio_rendicion"] ?? "");

    $fechaCierreRendicion = trim($_POST["fecha_cierre_rendicion"] ?? "");

    $fechaResultadoRendicionTexto = trim($_POST["fecha_resultados_rendicion"] ?? "");
    $fechaResultadoRendicionTs =
        $fechaResultadoRendicionTexto === "" ? null : strtotime($fechaResultadoRendicionTexto);
    // La publicación de rendición es independiente del cierre; permite revisiones anticipadas.
    if ($fechaResultadoRendicionTs === false) {
        volverConfiguracion("La fecha de publicación del resultado de rendición no es válida.");
    }
    $resultadoRendicion =
        $fechaResultadoRendicionTs === null
            ? null
            : date("Y-m-d H:i:s", $fechaResultadoRendicionTs);

    if (
        !$procesoId ||
        $nombre === "" ||
        $fechaInicio === "" ||
        $fechaCierre === "" ||
        $fechaResultados === "" ||
        $fechaInicioRendicion === "" ||
        $fechaCierreRendicion === ""
    ) {
        volverConfiguracion(
            "Debes completar todos los datos del proceso, incluyendo las fechas de rendición.",
        );
    }

    $inicioTs = strtotime($fechaInicio);

    $cierreTs = strtotime($fechaCierre);

    $resultadosTs = strtotime($fechaResultados);

    $inicioRendicionTs = strtotime($fechaInicioRendicion);

    $cierreRendicionTs = strtotime($fechaCierreRendicion);

    if (
        $inicioTs === false ||
        $cierreTs === false ||
        $resultadosTs === false ||
        $inicioRendicionTs === false ||
        $cierreRendicionTs === false
    ) {
        volverConfiguracion("Una o más fechas del proceso no son válidas.");
    }

    if ($cierreTs <= $inicioTs) {
        volverConfiguracion(
            "La fecha de cierre de postulaciones debe ser posterior a la fecha de inicio.",
        );
    }

    if ($resultadosTs < $cierreTs) {
        volverConfiguracion(
            "La fecha de publicación de resultados no puede ser anterior al cierre de postulaciones.",
        );
    }

    if ($inicioRendicionTs < $resultadosTs) {
        volverConfiguracion(
            "La fecha de inicio de la rendición no puede ser anterior a la publicación de resultados.",
        );
    }

    if ($cierreRendicionTs <= $inicioRendicionTs) {
        volverConfiguracion(
            "La fecha de cierre de la rendición debe ser posterior a la fecha de inicio.",
        );
    }

    $inicio = date("Y-m-d H:i:s", $inicioTs);

    $cierre = date("Y-m-d H:i:s", $cierreTs);

    $resultados = date("Y-m-d H:i:s", $resultadosTs);

    $inicioRendicion = date("Y-m-d H:i:s", $inicioRendicionTs);

    $cierreRendicion = date("Y-m-d H:i:s", $cierreRendicionTs);

    $stmt = $conn->prepare(
        "
        SELECT

            id,
            fecha_inicio,
            instrucciones_ruta

        FROM procesos_beca

        WHERE id = ?

        LIMIT 1
        ",
    );

    if (!$stmt) {
        volverConfiguracion("No fue posible comprobar el proceso.");
    }

    $stmt->bind_param("i", $procesoId);

    $stmt->execute();

    $procesoActual = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$procesoActual) {
        volverConfiguracion("El proceso seleccionado no existe.");
    }

    $nuevoNombreOriginal = null;

    $nuevoNombreArchivo = null;

    $nuevaRutaBD = null;

    $nuevaRutaFisica = null;

    if (
        isset($_FILES["instrucciones_pdf"]) &&
        $_FILES["instrucciones_pdf"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {
        $archivo = $_FILES["instrucciones_pdf"];

        if ($archivo["error"] !== UPLOAD_ERR_OK) {
            volverConfiguracion("No fue posible recibir el PDF de instrucciones.");
        }

        if (!is_uploaded_file($archivo["tmp_name"])) {
            volverConfiguracion("El PDF de instrucciones no fue recibido correctamente.");
        }

        if ((int) $archivo["size"] <= 0) {
            volverConfiguracion("El PDF de instrucciones está vacío.");
        }

        if ((int) $archivo["size"] > 20 * 1024 * 1024) {
            volverConfiguracion("El PDF de instrucciones no puede superar los 20 MB.");
        }

        $extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));

        if ($extension !== "pdf") {
            volverConfiguracion("Las instrucciones deben estar en formato PDF.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $mime = $finfo->file($archivo["tmp_name"]);

        if ($mime !== "application/pdf") {
            volverConfiguracion("El archivo seleccionado no corresponde a un PDF válido.");
        }

        $fp = fopen($archivo["tmp_name"], "rb");

        if ($fp === false) {
            volverConfiguracion("No fue posible analizar el PDF de instrucciones.");
        }

        $cabecera = fread($fp, 5);

        fclose($fp);

        if ($cabecera !== "%PDF-") {
            volverConfiguracion("El archivo no posee una estructura PDF válida.");
        }

        $directorio =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            "documentos" .
            DIRECTORY_SEPARATOR .
            "procesos" .
            DIRECTORY_SEPARATOR .
            $procesoId;

        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            volverConfiguracion("No fue posible crear la carpeta de instrucciones.");
        }

        $nuevoNombreArchivo = "instrucciones_" . bin2hex(random_bytes(16)) . ".pdf";

        $nuevoNombreOriginal = basename($archivo["name"]);

        $nuevaRutaFisica = $directorio . DIRECTORY_SEPARATOR . $nuevoNombreArchivo;

        $nuevaRutaBD = "documentos/procesos/" . $procesoId . "/" . $nuevoNombreArchivo;

        if (!move_uploaded_file($archivo["tmp_name"], $nuevaRutaFisica)) {
            volverConfiguracion("No fue posible guardar el PDF de instrucciones.");
        }
    }

    $conn->begin_transaction();

    try {
        // Bloquear el proceso mientras se actualiza su configuración.
        $stmt = $conn->prepare(
            "SELECT fecha_inicio,activo FROM procesos_beca WHERE id=? FOR UPDATE",
        );
        $stmt->bind_param("i", $procesoId);
        $stmt->execute();
        $estadoProceso = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$estadoProceso) {
            throw new RuntimeException("El proceso ya no existe.");
        }
        $procesoActual["fecha_inicio"] = $estadoProceso["fecha_inicio"];
        if ($nuevaRutaBD !== null) {
            $stmt = $conn->prepare(
                "
                UPDATE procesos_beca

                SET

                    nombre = ?,
                    instrucciones_nombre_original = ?,
                    instrucciones_nombre_archivo = ?,
                    instrucciones_ruta = ?,
                    fecha_inicio = ?,
                    fecha_cierre = ?,
                    fecha_resultados = ?,
                    fecha_inicio_rendicion = ?,
                    fecha_cierre_rendicion = ?, fecha_resultados_rendicion = ?

                WHERE id = ?
                ",
            );

            if (!$stmt) {
                throw new RuntimeException("No fue posible preparar la actualización del proceso.");
            }

            $stmt->bind_param(
                "ssssssssssi",
                $nombre,
                $nuevoNombreOriginal,
                $nuevoNombreArchivo,
                $nuevaRutaBD,
                $inicio,
                $cierre,
                $resultados,
                $inicioRendicion,
                $cierreRendicion,
                $resultadoRendicion,
                $procesoId,
            );
        } else {
            $stmt = $conn->prepare(
                "
                UPDATE procesos_beca

                SET

                    nombre = ?,
                    fecha_inicio = ?,
                    fecha_cierre = ?,
                    fecha_resultados = ?,
                    fecha_inicio_rendicion = ?,
                    fecha_cierre_rendicion = ?, fecha_resultados_rendicion = ?

                WHERE id = ?
                ",
            );

            if (!$stmt) {
                throw new RuntimeException("No fue posible preparar la actualización del proceso.");
            }

            $stmt->bind_param(
                "sssssssi",
                $nombre,
                $inicio,
                $cierre,
                $resultados,
                $inicioRendicion,
                $cierreRendicion,
                $resultadoRendicion,
                $procesoId,
            );
        }

        if (!$stmt->execute()) {
            throw new RuntimeException("No fue posible actualizar el proceso.");
        }

        $stmt->close();

        require_once __DIR__ . "/../config/resultados_rendicion.php";
        sincronizarResultadosRendicion($conn, (int) $procesoId);
        $conn->commit();

        if (
            $nuevaRutaBD !== null &&
            !empty($procesoActual["instrucciones_ruta"]) &&
            $procesoActual["instrucciones_ruta"] !== $nuevaRutaBD
        ) {
            $rutaAnterior = rutaFisicaProyecto($procesoActual["instrucciones_ruta"]);

            if (is_file($rutaAnterior)) {
                $stmt = $conn->prepare(
                    "SELECT COUNT(*) FROM procesos_beca WHERE instrucciones_ruta=?",
                );
                $stmt->bind_param("s", $procesoActual["instrucciones_ruta"]);
                $stmt->execute();
                $referencias = (int) $stmt->get_result()->fetch_row()[0];
                $stmt->close();
                if ($referencias === 0) {
                    @unlink($rutaAnterior);
                }
            }
        }

        volverConfiguracion("La información del proceso fue actualizada correctamente.", true);
    } catch (Throwable $e) {
        $conn->rollback();

        if ($nuevaRutaFisica !== null && is_file($nuevaRutaFisica)) {
            @unlink($nuevaRutaFisica);
        }

        error_log("Error configuración: " . $e->getMessage());

        volverConfiguracion("No fue posible guardar la configuración del proceso.");
    }
}

if ($accion === "crear_requisito") {
    $procesoId = filter_input(INPUT_POST, "proceso_id", FILTER_VALIDATE_INT);

    $titulo = trim($_POST["titulo"] ?? "");

    $descripcion = trim($_POST["descripcion"] ?? "");

    $orden = filter_input(INPUT_POST, "orden", FILTER_VALIDATE_INT);

    if (!$procesoId || $titulo === "" || $descripcion === "" || !$orden) {
        volverConfiguracion("Completa correctamente el requisito.");
    }

    $stmt = $conn->prepare(
        "
            INSERT INTO requisitos_beca
            (
                proceso_id,
                titulo,
                descripcion,
                obligatorio,
                orden,
                activo
            )
            VALUES
            (
                ?,
                ?,
                ?,
                1,
                ?,
                1
            )
            ",
    );

    if (!$stmt) {
        volverConfiguracion("No fue posible preparar el requisito.");
    }

    $stmt->bind_param("issi", $procesoId, $titulo, $descripcion, $orden);

    if (!$stmt->execute()) {
        $stmt->close();

        volverConfiguracion("No fue posible guardar el requisito.");
    }

    $stmt->close();

    volverConfiguracion("El requisito fue agregado correctamente.", true);
}

if ($accion === "actualizar_requisito") {
    $requisitoId = filter_input(INPUT_POST, "requisito_id", FILTER_VALIDATE_INT);

    $titulo = trim($_POST["titulo"] ?? "");

    $descripcion = trim($_POST["descripcion"] ?? "");

    $orden = filter_input(INPUT_POST, "orden", FILTER_VALIDATE_INT);

    $activo = isset($_POST["activo"]) ? 1 : 0;

    if (!$requisitoId || $titulo === "" || $descripcion === "" || !$orden) {
        volverConfiguracion("El requisito contiene datos inválidos.");
    }

    $stmt = $conn->prepare(
        "
            UPDATE requisitos_beca

            SET

                titulo = ?,
                descripcion = ?,
                orden = ?,
                activo = ?

            WHERE id = ?
            ",
    );

    if (!$stmt) {
        volverConfiguracion("No fue posible preparar la actualización del requisito.");
    }

    $stmt->bind_param("ssiii", $titulo, $descripcion, $orden, $activo, $requisitoId);

    if (!$stmt->execute()) {
        $stmt->close();

        volverConfiguracion("No fue posible actualizar el requisito.");
    }

    $stmt->close();

    volverConfiguracion("El requisito fue actualizado correctamente.", true);
}

if ($accion === "eliminar_requisito") {
    $requisitoId = filter_input(INPUT_POST, "requisito_id", FILTER_VALIDATE_INT);

    if (!$requisitoId) {
        volverConfiguracion("El requisito no es válido.");
    }

    $stmt = $conn->prepare(
        "
            DELETE FROM requisitos_beca

            WHERE id = ?
            ",
    );

    if (!$stmt) {
        volverConfiguracion("No fue posible preparar la eliminación del requisito.");
    }

    $stmt->bind_param("i", $requisitoId);

    if (!$stmt->execute()) {
        $stmt->close();

        volverConfiguracion("No fue posible eliminar el requisito.");
    }

    $stmt->close();

    volverConfiguracion("El requisito fue eliminado correctamente.", true);
}

volverConfiguracion("La operación solicitada no es válida.");
