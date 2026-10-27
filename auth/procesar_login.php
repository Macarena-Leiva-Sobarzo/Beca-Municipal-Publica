<?php
require_once __DIR__ . "/../config/aviso.php";

/** Autentica la cuenta y establece su rol. La sesión determina las rutas disponibles; cada controlador vuelve a comprobar los permisos. */

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");

    exit();
}

$modoAdministrativo = ($_POST["modo"] ?? "") === "administrador";

function volverLoginConError(bool $modoAdministrativo): never
{
    header("Location: login.php?" . ($modoAdministrativo ? "modo=administrador&" : "") . "error=1");
    exit();
}

$rut = trim($_POST["rut"] ?? "");

$password = $_POST["password"] ?? "";

if (empty($rut) || empty($password)) {
    volverLoginConError($modoAdministrativo);
}

$sql = "
    SELECT
        id,
        correo,
        password,
        rol,
        activo
    FROM usuarios
    WHERE REPLACE(REPLACE(REPLACE(UPPER(rut), '.', ''), '-', ''), ' ', '') = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

$rutLogin = strtoupper(str_replace([".", "-", " "], "", $rut));
$stmt->bind_param("s", $rutLogin);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
    volverLoginConError($modoAdministrativo);
}

$usuario = $resultado->fetch_assoc();

// Durante una convocatoria exclusiva de rendición, las cuentas de estudiantes
// acceden mediante su RUT incluido en el padrón; las cuentas administrativas
// mantienen su acceso normal con contraseña.
if (
    ($usuario["rol"] ?? "") === "usuario" &&
    procesoSoloRendicionActivo($conn) !== null
) {
    header("Location: login.php?rut=" . rawurlencode($rut));
    exit();
}

if ((int) $usuario["activo"] !== 1) {
    mostrarAvisoBecas("Esta cuenta se encuentra desactivada.");
}

if (!password_verify($password, (string) ($usuario["password"] ?? ""))) {
    volverLoginConError($modoAdministrativo);
}

session_regenerate_id(true);

$_SESSION["usuario_id"] = $usuario["id"];

$_SESSION["correo"] = $usuario["correo"];

$_SESSION["rol"] = $usuario["rol"];

if (in_array($usuario["rol"], ["administrador", "superior"], true)) {
    header("Location: ../" . $usuario["rol"] . "/inicio.php");
} else {
    header("Location: ../usuario/inicio.php");
}

exit();
