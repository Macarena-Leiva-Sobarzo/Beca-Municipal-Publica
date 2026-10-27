<?php
require_once __DIR__ . "/../config/aviso.php";

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");

    exit();
}

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "usuario") {
    header("Location: ../index.php");

    exit();
}

$usuario_id = (int) $_SESSION["usuario_id"];

require_once __DIR__ . "/../config/reglas_beca.php";
$procesoVigente = $conn
    ->query("SELECT * FROM procesos_beca WHERE activo = 1 ORDER BY id DESC LIMIT 1")
    ->fetch_assoc();
if (!$procesoVigente || !plazoBecaAbierto($procesoVigente, "fecha_inicio", "fecha_cierre")) {
    header("Location: postulacion.php");
    exit();
}

require_once __DIR__ . "/../config/restriccion_postulacion.php";
if (restriccionPostulacion($conn, $usuario_id, $procesoVigente)) {
    header("Location: postulacion.php");
    exit();
}

$old = $_SESSION["old_postulacion"] ?? [];

$erroresDocumentos = $_SESSION["errores_documentos"] ?? [];

unset($_SESSION["old_postulacion"], $_SESSION["errores_documentos"]);

function oldValue(array $old, string $campo, string $default = ""): string
{
    return htmlspecialchars($old[$campo] ?? $default, ENT_QUOTES, "UTF-8");
}

$sql = "
    SELECT
        id,
        estado
    FROM postulaciones
    WHERE usuario_id = ? AND proceso_id = ?
    ORDER BY id DESC
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible comprobar la postulación.");
}

$stmt->bind_param("ii", $usuario_id, $procesoVigente["id"]);

$stmt->execute();

$resultado = $stmt->get_result();

$postulacion_existente = $resultado->fetch_assoc();

$stmt->close();

if ($postulacion_existente) {
    header("Location: postulacion.php");

    exit();
}

$sql = "
    SELECT
        id,
        nombre
    FROM bancos
    WHERE activo = 1
    ORDER BY nombre ASC
";

$bancos = $conn->query($sql);

if (!$bancos) {
    mostrarAvisoBecas("No fue posible cargar la lista de bancos.");
}

$sql = "
    SELECT
        id,
        nombre
    FROM tipos_cuenta
    WHERE activo = 1
    ORDER BY nombre ASC
";

$tipos_cuenta = $conn->query($sql);

if (!$tipos_cuenta) {
    mostrarAvisoBecas("No fue posible cargar los tipos de cuenta.");
}

$correo_usuario = $_SESSION["correo"] ?? "";

$correo_formulario = $old["correo_electronico"] ?? $correo_usuario;

$errorPostulacion = $_SESSION["error_postulacion"] ?? null;

unset($_SESSION["error_postulacion"]);

require_once __DIR__ . "/../vistas/usuario/formulario_postulacion.php";
