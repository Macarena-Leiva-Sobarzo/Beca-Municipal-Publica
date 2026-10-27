<?php

/** Valida campos y plazo, exige la verificación previa de los tres PDF y guarda la postulación y sus archivos como una operación. Ante errores, conserva el formulario para corregirlo. */

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

require_once __DIR__ . "/../validaciones/verificacion_postulacion.php";
require_once __DIR__ . "/../validaciones/validar_pdf.php";

const MAX_ARCHIVO = 5 * 1024 * 1024;

function volverFormulario(string $mensaje): void
{
    $_SESSION["error_postulacion"] = $mensaje;

    header("Location: formulario_postulacion.php");

    exit();
}

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");

    exit();
}

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "usuario") {
    header("Location: ../index.php");

    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: formulario_postulacion.php");

    exit();
}

$usuario_id = (int) $_SESSION["usuario_id"];

$nombres = trim($_POST["nombres"] ?? "");

$apellido_paterno = trim($_POST["apellido_paterno"] ?? "");

$apellido_materno = trim($_POST["apellido_materno"] ?? "");

$rut = trim($_POST["rut"] ?? "");

$fecha_nacimiento = trim($_POST["fecha_nacimiento"] ?? "");

$telefono = trim($_POST["telefono"] ?? "");

$direccion = trim($_POST["direccion"] ?? "");

$correo = trim($_POST["correo_electronico"] ?? "");

$carrera = trim($_POST["carrera"] ?? "");

$establecimiento = trim($_POST["nombre_establecimiento"] ?? "");

$region = trim($_POST["region"] ?? "");

$comuna = trim($_POST["comuna"] ?? "");

$gratuidad = trim($_POST["tiene_gratuidad"] ?? "");

$promedioRecibido = trim($_POST["promedio_ultimo_semestre"] ?? "");

$bancoRecibido = trim($_POST["banco_id"] ?? "");

$bancoOtro = trim($_POST["banco_otro"] ?? "");

$tipoCuentaRecibido = trim($_POST["tipo_cuenta_id"] ?? "");

$numeroCuenta = trim($_POST["numero_cuenta"] ?? "");

$_SESSION["old_postulacion"] = $_POST;

$campos = [
    $nombres,
    $apellido_paterno,
    $apellido_materno,
    $rut,
    $fecha_nacimiento,
    $telefono,
    $direccion,
    $correo,
    $carrera,
    $establecimiento,
    $region,
    $comuna,
    $gratuidad,
    $promedioRecibido,
    $bancoRecibido,
    $tipoCuentaRecibido,
    $numeroCuenta,
];

foreach ($campos as $campo) {
    if ($campo === "") {
        volverFormulario("Todos los campos son obligatorios.");
    }
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    volverFormulario("El correo electrónico no es válido.");
}

if (!in_array($gratuidad, ["si", "no"], true)) {
    volverFormulario("La opción de gratuidad no es válida.");
}

if (!is_numeric($promedioRecibido)) {
    volverFormulario("El promedio debe ser numérico.");
}

$promedio = (float) $promedioRecibido;

if ($promedio < 1 || $promedio > 7) {
    volverFormulario("El promedio debe encontrarse entre 1.00 y 7.00.");
}

function validarRutPostulante(string $rut): bool
{
    $rut = strtoupper(preg_replace("/[^0-9K]/i", "", $rut));

    if (strlen($rut) < 2) {
        return false;
    }

    $numero = substr($rut, 0, -1);

    $dv = substr($rut, -1);

    if (!ctype_digit($numero)) {
        return false;
    }

    $suma = 0;

    $multiplicador = 2;

    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $suma += ((int) $numero[$i]) * $multiplicador;

        $multiplicador++;

        if ($multiplicador > 7) {
            $multiplicador = 2;
        }
    }

    $resultado = 11 - ($suma % 11);

    if ($resultado === 11) {
        $calculado = "0";
    } elseif ($resultado === 10) {
        $calculado = "K";
    } else {
        $calculado = (string) $resultado;
    }

    return $dv === $calculado;
}

if (!validarRutPostulante($rut)) {
    volverFormulario("El RUT ingresado no es válido.");
}

// Normalizar el RUT sin depender de las funciones de lectura/OCR de documentos.
$rutLimpio = strtoupper(preg_replace("/[^0-9K]/i", "", $rut));

$numeroRut = substr($rutLimpio, 0, -1);

$dvRut = substr($rutLimpio, -1);

$rutFormateado = number_format((int) $numeroRut, 0, "", ".") . "-" . $dvRut;

$stmt = $conn->prepare(
    "
        SELECT
            id,
            nombre,
            fecha_inicio,
            fecha_cierre
        FROM procesos_beca
        WHERE activo = 1
        ORDER BY id DESC
        LIMIT 1
        ",
);

if (!$stmt) {
    volverFormulario("No fue posible comprobar el proceso de beca vigente.");
}

$stmt->execute();

$proceso = $stmt->get_result()->fetch_assoc();

$stmt->close();

require_once __DIR__ . "/../config/reglas_beca.php";
if (!$proceso || !plazoBecaAbierto($proceso, "fecha_inicio", "fecha_cierre")) {
    volverFormulario(
        $proceso
            ? mensajeAnioProcesoBeca($proceso, "fecha_inicio") ??
                "Fuera de plazo: el período de postulación no está abierto."
            : "Actualmente no existe un proceso de postulación abierto.",
    );
}

$procesoId = (int) $proceso["id"];

require_once __DIR__ . "/../config/restriccion_postulacion.php";
$restriccion = restriccionPostulacion($conn, $usuario_id, $proceso, $rutFormateado);
if ($restriccion) {
    volverFormulario($restriccion);
}
$stmt = $conn->prepare("SELECT id FROM postulaciones WHERE usuario_id=? AND proceso_id=? LIMIT 1");
$stmt->bind_param("ii", $usuario_id, $procesoId);
$stmt->execute();
$existePostulacion = $stmt->get_result()->num_rows > 0;
$stmt->close();
if ($existePostulacion) {
    header("Location: postulacion.php");
    exit();
}

$bancoId = null;

$bancoOtroGuardar = null;

if ($bancoRecibido === "otro") {
    if ($bancoOtro === "") {
        volverFormulario("Debes escribir el nombre del banco.");
    }

    $bancoOtroGuardar = $bancoOtro;
} else {
    if (!ctype_digit($bancoRecibido)) {
        volverFormulario("El banco seleccionado no es válido.");
    }

    $bancoId = (int) $bancoRecibido;

    $stmt = $conn->prepare(
        "
            SELECT id
            FROM bancos
            WHERE id = ?
            AND activo = 1
            LIMIT 1
            ",
    );

    $stmt->bind_param("i", $bancoId);

    $stmt->execute();

    if ($stmt->get_result()->num_rows !== 1) {
        $stmt->close();

        volverFormulario("El banco seleccionado no existe.");
    }

    $stmt->close();
}

if (!ctype_digit($tipoCuentaRecibido)) {
    volverFormulario("El tipo de cuenta no es válido.");
}

$tipoCuenta = (int) $tipoCuentaRecibido;

$stmt = $conn->prepare(
    "
        SELECT id
        FROM tipos_cuenta
        WHERE id = ?
        AND activo = 1
        LIMIT 1
        ",
);

$stmt->bind_param("i", $tipoCuenta);

$stmt->execute();

if ($stmt->get_result()->num_rows !== 1) {
    $stmt->close();

    volverFormulario("El tipo de cuenta seleccionado no existe.");
}

$stmt->close();

$documentos = [
    "carnet" => "Carnet de identidad",

    "alumno_regular" => "Certificado de alumno regular",

    "certificado_notas" => "Certificado de notas",
    "registro_social" => "Registro Social de Hogares",
    "acreditacion_gratuidad" => "Acreditación de gratuidad",
];

foreach ($documentos as $campo => $nombre) {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]["error"] !== UPLOAD_ERR_OK) {
        volverFormulario("Debes adjuntar " . $nombre . ".");
    }

    $archivo = $_FILES[$campo];
    if (!is_uploaded_file($archivo["tmp_name"])) {
        volverFormulario($nombre . " no fue recibido correctamente.");
    }
    try {
        validarArchivoPDF($archivo["tmp_name"], $archivo["name"], MAX_ARCHIVO);
    } catch (Throwable $e) {
        volverFormulario($nombre . ": " . $e->getMessage());
    }
}

$resultadoCarnet = recuperarVerificacionPostulacion(
    "carnet",
    $_FILES["carnet"]["tmp_name"],
    $_POST,
);
$resultadoAlumno = recuperarVerificacionPostulacion(
    "alumno_regular",
    $_FILES["alumno_regular"]["tmp_name"],
    $_POST,
);
$resultadoNotas = recuperarVerificacionPostulacion(
    "certificado_notas",
    $_FILES["certificado_notas"]["tmp_name"],
    $_POST,
);

$erroresDocumentos = [];

if (!isset($resultadoCarnet["estado"]) || $resultadoCarnet["estado"] !== "correcto") {
    $erroresDocumentos["carnet"] =
        $resultadoCarnet["mensaje"] ?? "No fue posible verificar el carnet.";
}

if (!isset($resultadoAlumno["estado"]) || $resultadoAlumno["estado"] !== "correcto") {
    $erroresDocumentos["alumno_regular"] =
        $resultadoAlumno["mensaje"] ?? "No fue posible verificar el certificado de alumno regular.";
}

if (!isset($resultadoNotas["estado"]) || $resultadoNotas["estado"] !== "correcto") {
    $erroresDocumentos["certificado_notas"] =
        $resultadoNotas["mensaje"] ?? "No fue posible verificar la concentración de notas.";
}

foreach (["registro_social", "acreditacion_gratuidad"] as $campoNuevo) {
    $resultadoNuevo = recuperarVerificacionPostulacion(
        $campoNuevo,
        $_FILES[$campoNuevo]["tmp_name"],
        $_POST,
    );
    if (($resultadoNuevo["estado"] ?? "") !== "correcto") {
        $erroresDocumentos[$campoNuevo] =
            $resultadoNuevo["mensaje"] ?? "Debes verificar el documento.";
    }
}
if (!empty($erroresDocumentos)) {
    $_SESSION["errores_documentos"] = $erroresDocumentos;

    $_SESSION["error_postulacion"] =
        "No se pudo enviar la postulación. " . "Revisa los documentos indicados en rojo.";

    header("Location: formulario_postulacion.php");

    exit();
}

$directorioPrincipal = dirname(__DIR__) . DIRECTORY_SEPARATOR . "documentos";

if (!is_dir($directorioPrincipal)) {
    if (!mkdir($directorioPrincipal, 0755, true)) {
        volverFormulario("No fue posible crear la carpeta de documentos.");
    }
}

function generarFolioPostulacion(): string
{
    return "BEC-" . date("Y") . "-" . strtoupper(bin2hex(random_bytes(6)));
}

$archivosGuardados = [];

$carpetaPostulacion = null;

$conn->begin_transaction();

try {
    // Serializar los envíos de una cuenta evita duplicar su postulación en el proceso.
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id=? FOR UPDATE");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $stmt->get_result();
    $stmt->close();
    $stmt = $conn->prepare("SELECT id FROM postulaciones WHERE usuario_id=? AND proceso_id=?");
    $stmt->bind_param("ii", $usuario_id, $procesoId);
    $stmt->execute();
    $duplicada = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($duplicada) {
        throw new RuntimeException("Ya enviaste una postulación para este proceso.");
    }
    $restriccion = restriccionPostulacion($conn, $usuario_id, $proceso, $rutFormateado);
    if ($restriccion) {
        throw new RuntimeException($restriccion);
    }

    do {
        $folio = generarFolioPostulacion();

        $stmt = $conn->prepare(
            "
                SELECT id
                FROM postulaciones
                WHERE folio = ?
                LIMIT 1
                ",
        );

        $stmt->bind_param("s", $folio);

        $stmt->execute();

        $existe = $stmt->get_result()->num_rows > 0;

        $stmt->close();
    } while ($existe);

    $stmt = $conn->prepare(
        "
            INSERT INTO postulaciones
            (
                folio,
                usuario_id,
                proceso_id,
                estado,
                fecha_postulacion
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'en_revision',
                NOW()
            )
            ",
    );

    $stmt->bind_param("sii", $folio, $usuario_id, $procesoId);

    if (!$stmt->execute()) {
        throw new Exception("No se pudo guardar la postulación.");
    }

    $postulacionId = (int) $conn->insert_id;

    $stmt->close();

    $stmt = $conn->prepare(
        "
            INSERT INTO datos_personales
            (
                postulacion_id,
                nombres,
                apellido_paterno,
                apellido_materno,
                rut,
                fecha_nacimiento,
                direccion,
                telefono,
                correo_electronico
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ",
    );

    $stmt->bind_param(
        "issssssss",
        $postulacionId,
        $nombres,
        $apellido_paterno,
        $apellido_materno,
        $rutFormateado,
        $fecha_nacimiento,
        $direccion,
        $telefono,
        $correo,
    );

    if (!$stmt->execute()) {
        throw new Exception("No se pudieron guardar los datos personales.");
    }

    $stmt->close();

    $stmt = $conn->prepare(
        "
            INSERT INTO datos_educacionales
            (
                postulacion_id,
                carrera,
                nombre_establecimiento,
                region,
                comuna,
                tiene_gratuidad,
                promedio_ultimo_semestre
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ",
    );

    $stmt->bind_param(
        "isssssd",
        $postulacionId,
        $carrera,
        $establecimiento,
        $region,
        $comuna,
        $gratuidad,
        $promedio,
    );

    if (!$stmt->execute()) {
        throw new Exception("No se pudieron guardar los datos educacionales.");
    }

    $stmt->close();

    $stmt = $conn->prepare(
        "
            INSERT INTO datos_bancarios
            (
                postulacion_id,
                banco_id,
                banco_otro,
                numero_cuenta,
                tipo_cuenta_id
            )
            VALUES (?, ?, ?, ?, ?)
            ",
    );

    $stmt->bind_param(
        "iissi",
        $postulacionId,
        $bancoId,
        $bancoOtroGuardar,
        $numeroCuenta,
        $tipoCuenta,
    );

    if (!$stmt->execute()) {
        throw new Exception("No se pudieron guardar los datos bancarios.");
    }

    $stmt->close();

    $carpetaPostulacion = $directorioPrincipal . DIRECTORY_SEPARATOR . $postulacionId;

    if (!is_dir($carpetaPostulacion) && !mkdir($carpetaPostulacion, 0755, true)) {
        throw new Exception("No se pudo crear la carpeta de documentos.");
    }

    foreach ($documentos as $tipo => $nombreVisible) {
        $archivo = $_FILES[$tipo];

        $nombreOriginal = basename($archivo["name"]);

        $nombreSeguro = $tipo . "_" . bin2hex(random_bytes(16)) . ".pdf";

        $rutaFisica = $carpetaPostulacion . DIRECTORY_SEPARATOR . $nombreSeguro;

        $rutaBD = "documentos/" . $postulacionId . "/" . $nombreSeguro;

        if (!move_uploaded_file($archivo["tmp_name"], $rutaFisica)) {
            throw new Exception("No se pudo guardar " . $nombreVisible . ".");
        }

        $archivosGuardados[] = $rutaFisica;

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $mime = $finfo->file($rutaFisica);

        $tamano = filesize($rutaFisica);

        $extension = "pdf";

        $stmt = $conn->prepare(
            "
        INSERT INTO documentos
        (
            postulacion_id,
            tipo_documento,
            nombre_archivo,
            nombre_archivo_original,
            ruta_archivo,
            tipo_mime,
            extension,
            tamano_bytes,
            estado_validacion,
            observacion,
            fecha_validacion
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?,
            'pendiente',
            NULL,
            NULL
        )
        ",
        );

        $stmt->bind_param(
            "issssssi",
            $postulacionId,
            $tipo,
            $nombreSeguro,
            $nombreOriginal,
            $rutaBD,
            $mime,
            $extension,
            $tamano,
        );

        if (!$stmt->execute()) {
            throw new Exception("No se pudo registrar " . $nombreVisible . ".");
        }

        $stmt->close();
    }

    $stmt = $conn->prepare(
        "
            INSERT INTO rendiciones
            (
                postulacion_id,
                estado
            )
            VALUES
            (
                ?,
                'no_iniciada'
            )
            ",
    );

    $stmt->bind_param("i", $postulacionId);

    if (!$stmt->execute()) {
        throw new Exception("No se pudo crear la rendición.");
    }

    $stmt->close();

    $conn->commit();
    unset($_SESSION["verificacion_postulacion"]);

    unset(
        $_SESSION["old_postulacion"],
        $_SESSION["errores_documentos"],
        $_SESSION["error_postulacion"],
    );

    header("Location: postulacion.php?postulacion=exito");

    exit();
} catch (Throwable $e) {
    // La transacción revierte la base; los PDF se eliminan aparte porque el disco no participa en ella.
    $conn->rollback();

    foreach ($archivosGuardados as $ruta) {
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    if ($carpetaPostulacion && is_dir($carpetaPostulacion)) {
        @rmdir($carpetaPostulacion);
    }

    error_log("Error postulación: " . $e->getMessage());

    volverFormulario("Ocurrió un error al guardar la postulación.");
}
