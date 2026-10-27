<?php

/**
 * Atiende la verificación de PDF y el envío definitivo de la rendición.
 * La verificación responde JSON; el guardado conserva los datos escritos si falla.
 */

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";
// El acceso por RUT corresponde únicamente a convocatorias de solo rendición.
// Las convocatorias completas exigen la autorización de la postulación en el otro portal.
$modalidadActual = $conn
    ->query(
        "SELECT tipo_proceso FROM procesos_beca WHERE activo=1 ORDER BY id DESC LIMIT 1",
    )
    ->fetch_assoc();
if (($modalidadActual["tipo_proceso"] ?? "") !== "solo_rendicion") {
    http_response_code(403);
    exit(
        "Esta convocatoria requiere ingresar al portal de postulación y rendición con una postulación beneficiada."
    );
}

require_once __DIR__ . "/../validaciones/validar_pdf.php";

/** @var mysqli $conn */

date_default_timezone_set("America/Santiago");

const MAX_BOLETA = 5 * 1024 * 1024;

const MAX_DEVOLUCION = 5 * 1024 * 1024;

/** Conserva campos de texto en sesión; el navegador exige volver a seleccionar los archivos. */
function guardarFormularioRendicionAnterior(): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }

    $campos = [
        "nombres",
        "apellido_paterno",
        "apellido_materno",
        "fecha_nacimiento",
        "direccion",
        "telefono",
        "correo_electronico",
        "carrera",
        "nombre_establecimiento",
        "region",
        "comuna",
        "tiene_gratuidad",
        "promedio_ultimo_semestre",
        "anio_cursa",
        "monto_anual",
        "monto_devuelto",
        "fecha_deposito",
        "numero_comprobante_devolucion",
    ];

    $datos = [];

    foreach ($campos as $campo) {
        $datos[$campo] = $_POST[$campo] ?? "";
    }

    $datos["gastos"] =
        isset($_POST["gastos"]) && is_array($_POST["gastos"])
            ? $_POST["gastos"]
            : [];

    if (isset($_POST["tiene_devolucion"])) {
        $datos["tiene_devolucion"] = "1";
    }

    if (isset($_POST["acepta_declaracion"])) {
        $datos["acepta_declaracion"] = "1";
    }

    if (isset($_POST["confirmacion"])) {
        $datos["confirmacion"] = "1";
    }

    $_SESSION["rendicion_form_anterior"] = $datos;
}

function errorRendicion(string $mensaje, array $erroresGastos = []): void
{
    guardarFormularioRendicionAnterior();

    $_SESSION["rendicion_error"] = $mensaje;

    if (!empty($erroresGastos)) {
        $_SESSION["rendicion_errores_gastos"] = $erroresGastos;
    }

    header("Location: rendicion.php");

    exit();
}

/** Convierte la estructura de $_FILES del grupo de gastos en un archivo individual. */
function archivoAnidado(
    string $grupo,
    int|string $indice,
    string $campo,
): ?array {
    if (!isset($_FILES[$grupo]) || !is_array($_FILES[$grupo])) {
        return null;
    }

    $archivo = $_FILES[$grupo];

    if (
        !isset($archivo["name"][$indice][$campo]) ||
        !isset($archivo["tmp_name"][$indice][$campo]) ||
        !isset($archivo["error"][$indice][$campo]) ||
        !isset($archivo["size"][$indice][$campo])
    ) {
        return null;
    }

    return [
        "name" => $archivo["name"][$indice][$campo],

        "tmp_name" => $archivo["tmp_name"][$indice][$campo],

        "error" => $archivo["error"][$indice][$campo],

        "size" => $archivo["size"][$indice][$campo],

        "type" => $archivo["type"][$indice][$campo] ?? "",
    ];
}

/** Exige una carga HTTP válida y aplica las mismas reglas PDF que la verificación previa. */
function validarPDFSubido(array $archivo, int $maximo, string $nombre): array
{
    $error = (int) ($archivo["error"] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Debes adjuntar " . $nombre . ".");
    }

    $tmp = (string) ($archivo["tmp_name"] ?? "");

    if ($tmp === "" || !is_uploaded_file($tmp)) {
        throw new RuntimeException(
            ucfirst($nombre) . " no fue recibido correctamente.",
        );
    }

    return validarArchivoPDF($tmp, (string) ($archivo["name"] ?? ""), $maximo);
}

class RendicionGastosException extends RuntimeException
{
    private array $erroresGastos;
    public function __construct(string $mensaje, array $erroresGastos)
    {
        parent::__construct($mensaje);

        $this->erroresGastos = $erroresGastos;
    }

    public function getErroresGastos(): array
    {
        return $this->erroresGastos;
    }
}

function responderVerificacion(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

/** Identifica el contenido del PDF, incluso si el usuario conserva el mismo nombre de archivo. */
function hashArchivoTemporal(string $ruta): string
{
    if ($ruta === "" || !is_file($ruta)) {
        return "";
    }

    $hash = hash_file("sha256", $ruta);
    return is_string($hash) ? $hash : "";
}

function normalizarNumeroVerificacion(string $valor): string
{
    return preg_replace("/[^0-9]/", "", $valor) ?? "";
}

/** Asocia el resultado de verificación a esta sesión y a los datos del documento. */
function guardarTokenVerificacion(string $token, array $datos): void
{
    if (
        !isset($_SESSION["rendicion_verificaciones"]) ||
        !is_array($_SESSION["rendicion_verificaciones"])
    ) {
        $_SESSION["rendicion_verificaciones"] = [];
    }
    $_SESSION["rendicion_verificaciones"][$token] = $datos;
}

function obtenerTokenVerificacion(string $token): ?array
{
    $datos = $_SESSION["rendicion_verificaciones"][$token] ?? null;
    return is_array($datos) ? $datos : null;
}

/** Reúne errores por gasto y comprueba documentos, verificaciones y límite de monto anual. */
function prepararGastos(string $grupo, array $datos, float $montoMaximo): array
{
    $categoriasPermitidas = [
        "arancel",
        "matricula",
        "materiales",
        "vestuario",
        "alimentacion",
    ];

    if (empty($datos)) {
        throw new RuntimeException(
            "Debes registrar al menos un gasto para la rendición anual.",
        );
    }

    $resultado = [];

    $errores = [];

    $total = 0.0;

    $numeroVisual = 0;

    foreach ($datos as $indice => $gasto) {
        $numeroVisual++;

        if (!is_array($gasto)) {
            $errores[] = "Gasto " . $numeroVisual . ": información inválida.";

            continue;
        }

        $prefijo = "Gasto " . $numeroVisual;

        $fecha = trim((string) ($gasto["fecha"] ?? ""));

        $categoria = trim((string) ($gasto["categoria"] ?? ""));

        $categoriaOtro = null;

        if ($categoria === "otro") {
            $categoriaOtro = trim((string) ($gasto["categoria_otro"] ?? ""));

            if ($categoriaOtro === "") {
                $errores[] = $prefijo . ": debes especificar la categoría.";

                continue;
            }
        }

        $numeroDocumento = trim((string) ($gasto["numero_documento"] ?? ""));

        $montoRecibido = trim((string) ($gasto["monto"] ?? ""));

        $prefijo = "Gasto " . $numeroVisual;

        if (
            $fecha === "" ||
            $categoria === "" ||
            $numeroDocumento === "" ||
            $montoRecibido === ""
        ) {
            $errores[] = $prefijo . ": completa todos los campos.";

            continue;
        }

        if (
            in_array($categoria, $categoriasPermitidas, true) === false &&
            $categoria === ""
        ) {
            $errores[] = $prefijo . ": la categoría seleccionada no es válida.";

            continue;
        }

        $fechaObjeto = DateTime::createFromFormat("Y-m-d", $fecha);

        if (!$fechaObjeto || $fechaObjeto->format("Y-m-d") !== $fecha) {
            $errores[] = $prefijo . ": la fecha del gasto no es válida.";

            continue;
        }

        if (strtotime($fecha) > strtotime(date("Y-m-d"))) {
            $errores[] = $prefijo . ": la fecha del gasto no puede ser futura.";

            continue;
        }

        if (mb_strlen($numeroDocumento, "UTF-8") > 100) {
            $errores[] =
                $prefijo . ": el número de documento es demasiado largo.";

            continue;
        }

        if (!is_numeric($montoRecibido)) {
            $errores[] = $prefijo . ": el monto no es válido.";

            continue;
        }

        $monto = (float) $montoRecibido;

        if ($monto <= 0) {
            $errores[] = $prefijo . ": el monto debe ser mayor que cero.";

            continue;
        }

        $archivo = archivoAnidado($grupo, $indice, "boleta");

        if ($archivo === null) {
            $errores[] = $prefijo . ": debes adjuntar la boleta PDF.";

            continue;
        }

        try {
            $datosArchivo = validarPDFSubido(
                $archivo,
                MAX_BOLETA,
                "el documento",
            );
        } catch (Throwable $e) {
            $errores[] = $prefijo . ": " . $e->getMessage();

            continue;
        }

        // La huella debe coincidir: cambiar el PDF obliga a verificarlo nuevamente.
        $tokenVerificacion = trim((string) ($gasto["verificacion"] ?? ""));

        $verificacion = obtenerTokenVerificacion($tokenVerificacion);

        $hashActual = hashArchivoTemporal((string) $archivo["tmp_name"]);

        if (
            !$verificacion ||
            ($verificacion["tipo"] ?? "") !== "documento" ||
            (int) ($verificacion["rendicion_id"] ?? 0) !==
                (int) ($_POST["rendicion_id"] ?? 0) ||
            ($verificacion["hash"] ?? "") !== $hashActual
        ) {
            $errores[] =
                $prefijo .
                ": debes verificar este documento antes de enviar la rendición. Si cambiaste el PDF, vuelve a verificarlo.";
            continue;
        }

        $total += $monto;

        $resultado[] = [
            "fecha" => $fecha,

            "categoria" => $categoria,

            "categoria_otro" => $categoriaOtro ?? null,

            "numero_documento" => $numeroDocumento,

            "monto" => $monto,

            "archivo" => $archivo,

            "mime" => (string) $datosArchivo["mime"],

            "extension" => (string) $datosArchivo["extension"],

            "tamano" => (int) $datosArchivo["tamano"],

            "monto_detectado" => null,

            "numero_detectado" => null,
        ];
    }

    if (!empty($errores)) {
        throw new RendicionGastosException(
            "Hay gastos o documentos que debes corregir.",
            $errores,
        );
    }

    if ($total > $montoMaximo + 0.01) {
        throw new RuntimeException(
            "El total de gastos supera el monto anual recibido.",
        );
    }

    return [
        "gastos" => $resultado,

        "total" => $total,
    ];
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["accion"]) &&
    in_array(
        (string) $_POST["accion"],
        ["verificar_boleta", "verificar_comprobante_devolucion"],
        true,
    )
) {
    if (
        !isset($_SESSION["rol"], $_SESSION["acceso_rut_id"]) ||
        $_SESSION["rol"] !== "usuario"
    ) {
        responderVerificacion(
            [
                "ok" => false,
                "estado" => "incorrecto",
                "mensaje" => "Sesión no autorizada.",
            ],
            403,
        );
    }

    $archivo = $_FILES["archivo"] ?? null;
    if (!is_array($archivo)) {
        responderVerificacion(
            [
                "ok" => false,
                "estado" => "incorrecto",
                "mensaje" => "Debes seleccionar un archivo PDF.",
            ],
            400,
        );
    }

    try {
        validarPDFSubido(
            $archivo,
            (string) $_POST["accion"] === "verificar_boleta"
                ? MAX_BOLETA
                : MAX_DEVOLUCION,
            (string) $_POST["accion"] === "verificar_boleta"
                ? "el documento"
                : "el comprobante de devolución",
        );
    } catch (Throwable $e) {
        responderVerificacion(
            [
                "ok" => false,
                "estado" => "incorrecto",
                "mensaje" => $e->getMessage(),
            ],
            400,
        );
    }

    $hash = hashArchivoTemporal((string) $archivo["tmp_name"]);
    if ($hash === "") {
        responderVerificacion(
            [
                "ok" => false,
                "estado" => "incorrecto",
                "mensaje" => "No fue posible identificar el archivo.",
            ],
            400,
        );
    }

    $rendicionId = (int) ($_POST["rendicion_id"] ?? 0);
    $token = trim((string) ($_POST["verificacion_id"] ?? ""));
    if ($rendicionId <= 0 || !preg_match('/^[a-f0-9]{32,64}$/i', $token)) {
        responderVerificacion(
            [
                "ok" => false,
                "estado" => "incorrecto",
                "mensaje" => "La solicitud de verificación no es válida.",
            ],
            400,
        );
    }

    $stmt = $conn->prepare(
        "SELECT id, estado, acceso_rut_id FROM rendiciones WHERE id = ? LIMIT 1",
    );
    if (!$stmt) {
        responderVerificacion(
            [
                "ok" => false,
                "estado" => "incorrecto",
                "mensaje" => "No fue posible validar la rendición.",
            ],
            500,
        );
    }
    $stmt->bind_param("i", $rendicionId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        !$r ||
        (int) $r["acceso_rut_id"] !== (int) $_SESSION["acceso_rut_id"] ||
        !in_array($r["estado"], ["no_iniciada"], true)
    ) {
        responderVerificacion(
            [
                "ok" => false,
                "estado" => "incorrecto",
                "mensaje" =>
                    "La rendición no está disponible para verificación.",
            ],
            403,
        );
    }

    if ($_POST["accion"] === "verificar_boleta") {
        $resultado = [
            "estado" => "correcto",
            "mensaje" =>
                "PDF verificado: formato válido y archivo no vacío. Su contenido será revisado por el administrador.",
        ];

        guardarTokenVerificacion($token, [
            "tipo" => "documento",
            "rendicion_id" => $rendicionId,
            "hash" => $hash,
            "tamano" => (int) $archivo["size"],
            "nombre" => basename((string) $archivo["name"]),
            "creado" => time(),
        ]);

        responderVerificacion([
            "ok" => true,
            "estado" => "correcto",
            "token" => $token,
            "mensaje" => $resultado["mensaje"],
        ]);
    }

    $monto = (float) ($_POST["monto"] ?? 0);
    $numero = trim((string) ($_POST["numero_comprobante"] ?? ""));

    if ($monto <= 0 || $numero === "") {
        responderVerificacion(
            [
                "ok" => false,
                "estado" => "incorrecto",
                "mensaje" =>
                    "Ingresa el monto y el número de comprobante antes de verificar.",
            ],
            400,
        );
    }

    $resultado = [
        "estado" => "correcto",
        "mensaje" =>
            "PDF verificado: formato válido y archivo no vacío. El monto y el número de operación serán revisados por el administrador.",
    ];

    guardarTokenVerificacion($token, [
        "tipo" => "devolucion",
        "rendicion_id" => $rendicionId,
        "hash" => $hash,
        "tamano" => (int) $archivo["size"],
        "nombre" => basename((string) $archivo["name"]),
        "monto" => $monto,
        "numero" => normalizarNumeroVerificacion($numero),
        "creado" => time(),
    ]);

    responderVerificacion([
        "ok" => true,
        "estado" => "correcto",
        "token" => $token,
        "mensaje" => $resultado["mensaje"],
    ]);
}

if (
    !isset($_SESSION["rol"], $_SESSION["acceso_rut_id"], $_SESSION["rut"]) ||
    $_SESSION["rol"] !== "usuario"
) {
    header("Location: ../index.php");

    exit();
}

$accesoRutId = (int) $_SESSION["acceso_rut_id"];

$procesoSolo = procesoSoloRendicionActivo($conn);
if (!$procesoSolo || !usuarioHabilitadoSoloRendicion($conn, (int) $procesoSolo["id"], $accesoRutId)) {
    http_response_code(403);
    exit("Tu RUT no está habilitado para este proceso de rendición.");
}

$rutSesion = trim((string) $_SESSION["rut"]);

if ($accesoRutId <= 0 || $rutSesion === "") {
    header("Location: ../auth/cerrar_sesion.php");

    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: rendicion.php");

    exit();
}

$stmt = $conn->prepare(
    "
        SELECT

            id,
            rut,
            rol,
            activo

        FROM accesos_rut

        WHERE id = ?
        AND rol = 'usuario'
        AND activo = 1

        LIMIT 1
        ",
);

if (!$stmt) {
    errorRendicion("No fue posible comprobar tu acceso.");
}

$stmt->bind_param("i", $accesoRutId);

$stmt->execute();

$acceso = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$acceso) {
    header("Location: ../auth/cerrar_sesion.php");

    exit();
}

$rutBD = strtoupper(str_replace([".", " "], "", trim((string) $acceso["rut"])));

$rutSesionNormalizado = strtoupper(str_replace([".", " "], "", $rutSesion));

if ($rutBD !== $rutSesionNormalizado) {
    header("Location: ../auth/cerrar_sesion.php");

    exit();
}

$stmt = $conn->prepare(
    "
        SELECT

            r.id,
            r.acceso_rut_id,
            r.proceso_id,
            r.estado,
            r.devolucion_ruta,
            pb.fecha_inicio_rendicion,
            pb.fecha_cierre_rendicion,
            pb.activo AS proceso_activo

        FROM rendiciones r

        INNER JOIN procesos_beca pb
            ON pb.id = r.proceso_id

        WHERE r.acceso_rut_id = ?
        AND r.proceso_id = ?

        LIMIT 1
        ",
);

if (!$stmt) {
    errorRendicion("No fue posible comprobar la rendición.");
}

$procesoSoloId = (int) $procesoSolo["id"];

$stmt->bind_param("ii", $accesoRutId, $procesoSoloId);

$stmt->execute();

$rendicion = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$rendicion) {
    errorRendicion("No fue posible encontrar la rendición asociada a tu RUT.");
}

if ($rendicion["estado"] !== "no_iniciada") {
    errorRendicion("La rendición ya fue enviada y no puede modificarse.");
}

$ahora = new DateTime("now", new DateTimeZone("America/Santiago"));

$fechaInicio = null;

$fechaCierre = null;

if (!empty($rendicion["fecha_inicio_rendicion"])) {
    $fechaInicio = DateTime::createFromFormat(
        "Y-m-d H:i:s",
        $rendicion["fecha_inicio_rendicion"],
        new DateTimeZone("America/Santiago"),
    );
}

if (!empty($rendicion["fecha_cierre_rendicion"])) {
    $fechaCierre = DateTime::createFromFormat(
        "Y-m-d H:i:s",
        $rendicion["fecha_cierre_rendicion"],
        new DateTimeZone("America/Santiago"),
    );
}

if (!$fechaInicio || !$fechaCierre) {
    errorRendicion("El periodo de rendición todavía no ha sido configurado.");
}

if ($ahora < $fechaInicio) {
    errorRendicion("El periodo de rendición todavía no ha comenzado.");
}

if ($ahora > $fechaCierre) {
    errorRendicion("El periodo de rendición ya finalizó.");
}

$nombres = trim((string) ($_POST["nombres"] ?? ""));

$apellidoPaterno = trim((string) ($_POST["apellido_paterno"] ?? ""));

$apellidoMaterno = trim((string) ($_POST["apellido_materno"] ?? ""));

$fechaNacimiento = trim((string) ($_POST["fecha_nacimiento"] ?? ""));

$direccion = trim((string) ($_POST["direccion"] ?? ""));

$telefono = trim((string) ($_POST["telefono"] ?? ""));

$correo = trim((string) ($_POST["correo_electronico"] ?? ""));

$carrera = trim((string) ($_POST["carrera"] ?? ""));

$establecimiento = trim((string) ($_POST["nombre_establecimiento"] ?? ""));

$region = trim((string) ($_POST["region"] ?? ""));

$comuna = trim((string) ($_POST["comuna"] ?? ""));

$gratuidad = trim((string) ($_POST["tiene_gratuidad"] ?? ""));

$promedioRecibido = trim((string) ($_POST["promedio_ultimo_semestre"] ?? ""));

$camposObligatorios = [
    $nombres,
    $apellidoPaterno,
    $apellidoMaterno,
    $fechaNacimiento,
    $direccion,
    $telefono,
    $correo,
    $carrera,
    $establecimiento,
    $region,
    $comuna,
    $gratuidad,
    $promedioRecibido,
];

foreach ($camposObligatorios as $campo) {
    if (trim((string) $campo) === "") {
        errorRendicion(
            "Debes completar todos los datos personales y educacionales.",
        );
    }
}

if (mb_strlen($nombres, "UTF-8") > 150) {
    errorRendicion("Los nombres ingresados son demasiado largos.");
}

if (
    mb_strlen($apellidoPaterno, "UTF-8") > 100 ||
    mb_strlen($apellidoMaterno, "UTF-8") > 100
) {
    errorRendicion("Uno de los apellidos supera el máximo permitido.");
}

if (mb_strlen($direccion, "UTF-8") > 300) {
    errorRendicion("La dirección supera el máximo permitido.");
}

if (mb_strlen($telefono, "UTF-8") > 30) {
    errorRendicion("El teléfono supera el máximo permitido.");
}

if (mb_strlen($correo, "UTF-8") > 150) {
    errorRendicion("El correo electrónico supera el máximo permitido.");
}

if (mb_strlen($carrera, "UTF-8") > 200) {
    errorRendicion("El nombre de la carrera supera el máximo permitido.");
}

if (mb_strlen($establecimiento, "UTF-8") > 250) {
    errorRendicion("El nombre del establecimiento supera el máximo permitido.");
}

if (mb_strlen($region, "UTF-8") > 150 || mb_strlen($comuna, "UTF-8") > 150) {
    errorRendicion("La región o comuna supera el máximo permitido.");
}

$fechaNacimientoObjeto = DateTime::createFromFormat("Y-m-d", $fechaNacimiento);

if (
    !$fechaNacimientoObjeto ||
    $fechaNacimientoObjeto->format("Y-m-d") !== $fechaNacimiento
) {
    errorRendicion("La fecha de nacimiento no es válida.");
}

if (strtotime($fechaNacimiento) >= strtotime(date("Y-m-d"))) {
    errorRendicion("La fecha de nacimiento no es válida.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    errorRendicion("El correo electrónico ingresado no es válido.");
}

if (!in_array($gratuidad, ["si", "no"], true)) {
    errorRendicion("Debes seleccionar correctamente si tienes gratuidad.");
}

if (!is_numeric($promedioRecibido)) {
    errorRendicion("El promedio ingresado no es válido.");
}

$promedio = (float) $promedioRecibido;

if ($promedio < 1 || $promedio > 7) {
    errorRendicion("El promedio debe estar entre 1.0 y 7.0.");
}

$anioCursa = filter_input(INPUT_POST, "anio_cursa", FILTER_VALIDATE_INT);

if (!$anioCursa || $anioCursa < 1 || $anioCursa > 10) {
    errorRendicion("El año que cursas no es válido.");
}

$montoAnualRecibido = trim((string) ($_POST["monto_anual"] ?? ""));

if (
    $montoAnualRecibido === "" ||
    !is_numeric($montoAnualRecibido) ||
    (float) $montoAnualRecibido <= 0
) {
    errorRendicion("El monto anual recibido no es válido.");
}

$montoAnual = (float) $montoAnualRecibido;

$montoTotal = $montoAnual;

if (!isset($_POST["acepta_declaracion"])) {
    errorRendicion("Debes aceptar la declaración jurada.");
}

if (!isset($_POST["confirmacion"])) {
    errorRendicion("Debes confirmar que revisaste todos los antecedentes.");
}

$gastosPost = $_POST["gastos"] ?? [];

try {
    $gastosPreparados = prepararGastos(
        "gastos",
        is_array($gastosPost) ? $gastosPost : [],
        $montoAnual,
    )["gastos"];
} catch (RendicionGastosException $e) {
    errorRendicion($e->getMessage(), $e->getErroresGastos());
} catch (Throwable $e) {
    errorRendicion($e->getMessage());
}

$totalGastosPreparados = 0.0;

foreach ($gastosPreparados as $gastoPreparado) {
    $totalGastosPreparados += (float) ($gastoPreparado["monto"] ?? 0);
}

$tieneDevolucion = isset($_POST["tiene_devolucion"]);

$montoDevuelto = null;

$fechaDeposito = null;

$numeroComprobante = null;

/** @var array|null $archivoDevolucion */
$archivoDevolucion = null;

if ($tieneDevolucion) {
    $montoDevueltoRecibido = trim((string) ($_POST["monto_devuelto"] ?? ""));

    $fechaDeposito = trim((string) ($_POST["fecha_deposito"] ?? ""));

    $numeroComprobante = trim(
        (string) ($_POST["numero_comprobante_devolucion"] ?? ""),
    );

    if (
        $montoDevueltoRecibido === "" ||
        !is_numeric($montoDevueltoRecibido) ||
        (float) $montoDevueltoRecibido <= 0
    ) {
        errorRendicion("El monto devuelto no es válido.");
    }

    $montoDevuelto = (float) $montoDevueltoRecibido;

    $fechaObjeto = DateTime::createFromFormat("Y-m-d", $fechaDeposito);

    if (!$fechaObjeto || $fechaObjeto->format("Y-m-d") !== $fechaDeposito) {
        errorRendicion("La fecha de devolución no es válida.");
    }

    if (strtotime($fechaDeposito) > strtotime(date("Y-m-d"))) {
        errorRendicion("La fecha de devolución no puede ser futura.");
    }

    if ($numeroComprobante === "") {
        errorRendicion(
            "Debes ingresar el número del comprobante de devolución.",
        );
    }

    if (mb_strlen($numeroComprobante, "UTF-8") > 100) {
        errorRendicion(
            "El número del comprobante de devolución es demasiado largo.",
        );
    }

    $sumaRendida = $totalGastosPreparados + $montoDevuelto;

    if ($sumaRendida > $montoTotal + 0.01) {
        errorRendicion(
            "La suma de los gastos y la devolución supera el monto total recibido.",
        );
    }

    if (
        !isset($_FILES["comprobante_devolucion"]) ||
        !is_array($_FILES["comprobante_devolucion"])
    ) {
        errorRendicion("Debes adjuntar el comprobante de devolución.");
    }

    $archivoDevolucion = $_FILES["comprobante_devolucion"];

    try {
        validarPDFSubido(
            $archivoDevolucion,
            MAX_DEVOLUCION,
            "el comprobante de devolución",
        );
    } catch (Throwable $e) {
        errorRendicion($e->getMessage());
    }

    // La devolución debe conservar el archivo y los datos registrados en la verificación.
    $tokenDevolucion = trim(
        (string) ($_POST["token_comprobante_devolucion"] ?? ""),
    );

    $verificacionDevolucion = obtenerTokenVerificacion($tokenDevolucion);

    $hashDevolucion = hashArchivoTemporal(
        (string) $archivoDevolucion["tmp_name"],
    );

    $montoDevueltoVerificacion = (float) $montoDevuelto;

    $numeroDevolucionVerificacion = normalizarNumeroVerificacion(
        $numeroComprobante,
    );

    if (
        !$verificacionDevolucion ||
        ($verificacionDevolucion["tipo"] ?? "") !== "devolucion" ||
        (int) ($verificacionDevolucion["rendicion_id"] ?? 0) !==
            (int) ($_POST["rendicion_id"] ?? 0) ||
        ($verificacionDevolucion["hash"] ?? "") !== $hashDevolucion ||
        abs(
            (float) ($verificacionDevolucion["monto"] ?? 0) -
                $montoDevueltoVerificacion,
        ) > 0.01 ||
        ($verificacionDevolucion["numero"] ?? "") !==
            $numeroDevolucionVerificacion
    ) {
        errorRendicion(
            "Debes verificar el comprobante de devolución antes de enviar la rendición. Si cambiaste el monto, número o PDF, vuelve a verificarlo.",
        );
    }
}

$rendicionId = (int) $rendicion["id"];

$directorioBase =
    dirname(__DIR__) .
    DIRECTORY_SEPARATOR .
    "documentos" .
    DIRECTORY_SEPARATOR .
    "rendiciones" .
    DIRECTORY_SEPARATOR .
    $rendicionId;

if (
    !is_dir($directorioBase) &&
    !mkdir($directorioBase, 0755, true) &&
    !is_dir($directorioBase)
) {
    errorRendicion("No fue posible crear la carpeta de la rendición.");
}

$archivosAntiguos = [];

$stmt = $conn->prepare(
    "
        SELECT
            ruta_archivo

        FROM rendicion_gastos

        WHERE rendicion_id = ?
        ",
);

if ($stmt) {
    $stmt->bind_param("i", $rendicionId);

    $stmt->execute();

    $resultadoAntiguos = $stmt->get_result();

    while ($fila = $resultadoAntiguos->fetch_assoc()) {
        $rutaBD = trim((string) ($fila["ruta_archivo"] ?? ""));

        if ($rutaBD !== "") {
            $archivosAntiguos[] =
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $rutaBD);
        }
    }

    $stmt->close();
}

$rutaDevolucionAntigua = trim((string) ($rendicion["devolucion_ruta"] ?? ""));

if ($rutaDevolucionAntigua !== "") {
    $archivosAntiguos[] =
        dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $rutaDevolucionAntigua);
}

$archivosNuevos = [];

// Guardar datos y gastos como una unidad; los archivos se compensan aparte si falla SQL.
$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "
            INSERT INTO datos_rendicion_usuario
            (
                acceso_rut_id,
                nombres,
                apellido_paterno,
                apellido_materno,
                fecha_nacimiento,
                direccion,
                telefono,
                correo_electronico,
                carrera,
                nombre_establecimiento,
                region,
                comuna,
                tiene_gratuidad,
                promedio_ultimo_semestre
            )

            VALUES
            (
                ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            )

            ON DUPLICATE KEY UPDATE

                nombres =
                    VALUES(nombres),

                apellido_paterno =
                    VALUES(apellido_paterno),

                apellido_materno =
                    VALUES(apellido_materno),

                fecha_nacimiento =
                    VALUES(fecha_nacimiento),

                direccion =
                    VALUES(direccion),

                telefono =
                    VALUES(telefono),

                correo_electronico =
                    VALUES(correo_electronico),

                carrera =
                    VALUES(carrera),

                nombre_establecimiento =
                    VALUES(nombre_establecimiento),

                region =
                    VALUES(region),

                comuna =
                    VALUES(comuna),

                tiene_gratuidad =
                    VALUES(tiene_gratuidad),

                promedio_ultimo_semestre =
                    VALUES(promedio_ultimo_semestre)
            ",
    );

    if (!$stmt) {
        throw new RuntimeException(
            "No fue posible preparar los datos del beneficiario.",
        );
    }

    $stmt->bind_param(
        "issssssssssssd",

        $accesoRutId,
        $nombres,
        $apellidoPaterno,
        $apellidoMaterno,
        $fechaNacimiento,
        $direccion,
        $telefono,
        $correo,
        $carrera,
        $establecimiento,
        $region,
        $comuna,
        $gratuidad,
        $promedio,
    );

    if (!$stmt->execute()) {
        throw new RuntimeException(
            "No fue posible guardar los datos del beneficiario.",
        );
    }

    $stmt->close();

    $stmt = $conn->prepare(
        "
            DELETE FROM rendicion_gastos

            WHERE rendicion_id = ?
            ",
    );

    if (!$stmt) {
        throw new RuntimeException(
            "No fue posible preparar los gastos de la rendición.",
        );
    }

    $stmt->bind_param("i", $rendicionId);

    if (!$stmt->execute()) {
        throw new RuntimeException(
            "No fue posible reemplazar los gastos anteriores.",
        );
    }

    $stmt->close();

    // Compatibilidad con bases antiguas que aún conservan este campo obligatorio.
    $columnasGasto = $conn
        ->query("SHOW COLUMNS FROM rendicion_gastos")
        ->fetch_all(MYSQLI_ASSOC);
    $tieneCampoAnterior = in_array(
        "concepto",
        array_column($columnasGasto, "Field"),
        true,
    );
    $columnaAnterior = $tieneCampoAnterior ? "concepto," : "";
    $valorAnterior = $tieneCampoAnterior ? "''," : "";

    foreach ($gastosPreparados as $gasto) {
        $nombreSeguro = "documento_" . bin2hex(random_bytes(16)) . ".pdf";

        $rutaFisica = $directorioBase . DIRECTORY_SEPARATOR . $nombreSeguro;

        if (
            !move_uploaded_file(
                (string) $gasto["archivo"]["tmp_name"],
                $rutaFisica,
            )
        ) {
            throw new RuntimeException(
                "No fue posible guardar una de las boletas.",
            );
        }

        if (!is_file($rutaFisica) || filesize($rutaFisica) <= 0) {
            @unlink($rutaFisica);

            throw new RuntimeException(
                "Una de las boletas guardadas está vacía.",
            );
        }

        $archivosNuevos[] = $rutaFisica;

        $rutaBD =
            "documentos/rendiciones/" . $rendicionId . "/" . $nombreSeguro;

        $nombreOriginal = basename((string) $gasto["archivo"]["name"]);

        $fecha = (string) $gasto["fecha"];

        $categoria = (string) $gasto["categoria"];

        $categoriaOtro = !empty($gasto["categoria_otro"])
            ? (string) $gasto["categoria_otro"]
            : null;

        $numero = (string) $gasto["numero_documento"];

        $monto = (float) $gasto["monto"];

        $mime = (string) $gasto["mime"];

        $extension = (string) $gasto["extension"];

        $tamano = (int) $gasto["tamano"];

        $montoDetectado = null;
        $numeroDetectado = null;

        $montoDetectadoBD =
            $montoDetectado !== null ? (float) $montoDetectado : null;

        $numeroDetectadoBD =
            $numeroDetectado !== null ? (string) $numeroDetectado : null;

        $estadoInicial = "pendiente";

        $stmt = $conn->prepare(
            "
                INSERT INTO rendicion_gastos
                (
                    rendicion_id,
                    fecha_gasto,
                    categoria,
                    categoria_otro,
                    tipo_documento,
                    numero_documento,
                    {$columnaAnterior}
                    monto,
                    nombre_archivo,
                    nombre_archivo_original,
                    ruta_archivo,
                    tipo_mime,
                    extension,
                    tamano_bytes,
                    estado_validacion,
                    monto_detectado,
                    numero_documento_detectado,
                    observacion_validacion,
                    fecha_validacion
                )

                VALUES
                (
                    ?,
                    ?, ?, ?,
                    'boleta',
                    ?, {$valorAnterior} ?,
                    ?, ?, ?, ?, ?, ?,
                    ?,
                    ?, ?,
                    NULL,
                    NULL
                )
                ",
        );

        if (!$stmt) {
            throw new RuntimeException(
                "No fue posible preparar una de las boletas.",
            );
        }

        $stmt->bind_param(
            "issssdsssssisds",
            $rendicionId,
            $fecha,
            $categoria,
            $categoriaOtro,
            $numero,
            $monto,
            $nombreSeguro,
            $nombreOriginal,
            $rutaBD,
            $mime,
            $extension,
            $tamano,
            $estadoInicial,
            $montoDetectadoBD,
            $numeroDetectadoBD,
        );

        if (!$stmt->execute()) {
            throw new RuntimeException(
                "No fue posible registrar una de las boletas.",
            );
        }

        $stmt->close();
    }

    $devolucionNombreArchivo = null;

    $devolucionNombreOriginal = null;

    $devolucionRuta = null;

    if ($tieneDevolucion && $archivoDevolucion !== null) {
        $devolucionNombreArchivo =
            "devolucion_" . bin2hex(random_bytes(16)) . ".pdf";

        $rutaDevolucionFisica =
            $directorioBase . DIRECTORY_SEPARATOR . $devolucionNombreArchivo;

        if (
            !move_uploaded_file(
                (string) $archivoDevolucion["tmp_name"],
                $rutaDevolucionFisica,
            )
        ) {
            throw new RuntimeException(
                "No fue posible guardar el comprobante de devolución.",
            );
        }

        if (
            !is_file($rutaDevolucionFisica) ||
            filesize($rutaDevolucionFisica) <= 0
        ) {
            @unlink($rutaDevolucionFisica);

            throw new RuntimeException(
                "El comprobante de devolución guardado está vacío.",
            );
        }

        $archivosNuevos[] = $rutaDevolucionFisica;

        $devolucionNombreOriginal = basename(
            (string) $archivoDevolucion["name"],
        );

        $devolucionRuta =
            "documentos/rendiciones/" .
            $rendicionId .
            "/" .
            $devolucionNombreArchivo;
    }

    $stmt = $conn->prepare(
        "
            UPDATE rendiciones

                SET

                    estado = 'enviada',
                    anio_cursa = ?,
                    monto_anual = ?,
                    fecha_envio = NOW(),
                    fecha_revision = NULL,
                    observaciones = NULL,
                    acepta_declaracion = 1,
                    monto_devuelto = ?,
                    fecha_deposito_devolucion = ?,
                    numero_comprobante_devolucion = ?,
                    devolucion_nombre_archivo = ?,
                    devolucion_nombre_original = ?,
                    devolucion_ruta = ?,
                    devolucion_estado_validacion = NULL,
                    devolucion_observacion_validacion = NULL,
                    devolucion_fecha_validacion = NULL

                WHERE id = ?

                AND acceso_rut_id = ?

                AND estado = 'no_iniciada'

                LIMIT 1
            ",
    );

    if (!$stmt) {
        throw new RuntimeException(
            "No fue posible preparar el envío de la rendición.",
        );
    }

    $stmt->bind_param(
        "iddsssssii",
        $anioCursa,
        $montoAnual,
        $montoDevuelto,
        $fechaDeposito,
        $numeroComprobante,
        $devolucionNombreArchivo,
        $devolucionNombreOriginal,
        $devolucionRuta,
        $rendicionId,
        $accesoRutId,
    );

    if (!$stmt->execute()) {
        throw new RuntimeException("No fue posible enviar la rendición.");
    }

    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException(
            "La rendición cambió de estado mientras se procesaba. Vuelve a cargar la página.",
        );
    }

    $stmt->close();

    // Solo después del commit se pueden retirar los documentos reemplazados.
    $conn->commit();

    foreach (array_unique($archivosAntiguos) as $rutaAntigua) {
        if (
            $rutaAntigua !== "" &&
            is_file($rutaAntigua) &&
            !in_array($rutaAntigua, $archivosNuevos, true)
        ) {
            @unlink($rutaAntigua);
        }
    }

    unset(
        $_SESSION["rendicion_form_anterior"],
        $_SESSION["rendicion_errores_gastos"],
    );

    $_SESSION["rendicion_exito"] =
        "La rendición fue enviada correctamente y quedó en revisión.";

    header("Location: rendicion.php");

    exit();
} catch (Throwable $e) {
    // Deshacer SQL y retirar los archivos nuevos evita dejar cargas de un envío fallido.
    $conn->rollback();

    foreach ($archivosNuevos as $rutaNueva) {
        if (is_file($rutaNueva)) {
            @unlink($rutaNueva);
        }
    }

    error_log(
        "Error rendición acceso_rut_id " .
            $accesoRutId .
            ": " .
            $e->getMessage(),
    );

    errorRendicion(
        "Ocurrió un error al guardar la rendición. Tus datos escritos se conservaron, pero debes volver a seleccionar los archivos PDF.",
    );
}
