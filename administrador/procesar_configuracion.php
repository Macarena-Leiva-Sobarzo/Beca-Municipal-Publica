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
    header("Location: configuracion.php");

    exit();
}

function volverConfiguracion(string $mensaje, bool $exito = false): void
{
    if ($exito) {
        $_SESSION["admin_exito"] = $mensaje;
    } else {
        $_SESSION["admin_error"] = $mensaje;
    }

    header("Location: configuracion.php");

    exit();
}

function rutaFisicaProyecto(string $rutaRelativa): string
{
    return dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $rutaRelativa);
}

$accion = trim($_POST["accion"] ?? "");

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
    if (
        $fechaResultadoRendicionTs === false ||
        ($fechaResultadoRendicionTs !== null &&
            $fechaResultadoRendicionTs < strtotime($fechaCierreRendicion))
    ) {
        volverConfiguracion(
            "La publicación del resultado de rendición debe ser posterior al cierre de rendiciones.",
        );
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
