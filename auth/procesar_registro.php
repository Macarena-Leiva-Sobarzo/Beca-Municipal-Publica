<?php
require_once __DIR__ . "/../config/rut.php";
require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: registro.php");
    exit();
}
$rut = normalizarRutBecas((string) ($_POST["rut"] ?? ""));
$password = (string) ($_POST["password"] ?? "");
$confirmacion = (string) ($_POST["password_confirmacion"] ?? "");
function errorRegistroRut(string $mensaje): never
{
    header(
        "Location: registro.php?error=" .
            urlencode($mensaje) .
            "&rut=" .
            urlencode((string) ($_POST["rut"] ?? "")),
    );
    exit();
}
if (!preg_match('/^[0-9]{7,8}[0-9K]$/', $rut)) {
    errorRegistroRut("Ingresa un RUT válido con su dígito verificador.");
}
$cuerpo = substr($rut, 0, -1);
$suma = 0;
$factor = 2;
for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
    $suma += (int) $cuerpo[$i] * $factor;
    $factor = $factor === 7 ? 2 : $factor + 1;
}
$resto = 11 - ($suma % 11);
$dv = $resto === 11 ? "0" : ($resto === 10 ? "K" : (string) $resto);
if (substr($rut, -1) !== $dv) {
    errorRegistroRut("El RUT no es válido. Revisa el dígito verificador.");
}
if (strlen($password) < 8) {
    errorRegistroRut("La contraseña debe tener al menos 8 caracteres.");
}
if ($password !== $confirmacion) {
    errorRegistroRut("Las contraseñas no coinciden.");
}
$stmt = $conn->prepare(
    "SELECT id FROM usuarios WHERE REPLACE(REPLACE(REPLACE(UPPER(rut),'.',''),'-',''),' ','')=? LIMIT 1",
);
$stmt->bind_param("s", $rut);
$stmt->execute();
$existe = $stmt->get_result()->num_rows > 0;
$stmt->close();
if ($existe) {
    errorRegistroRut("Este RUT ya está registrado. Inicia sesión con tu cuenta.");
}
$rut = preg_replace("/\B(?=(\d{3})+(?!\d))/", ".", $cuerpo) . "-" . $dv;
$hash = password_hash($password, PASSWORD_DEFAULT);
try {
    $stmt = $conn->prepare("INSERT INTO usuarios(rut,password,rol) VALUES (?,?,'usuario')");
    $stmt->bind_param("ss", $rut, $hash);
    $stmt->execute();
    $stmt->close();
} catch (mysqli_sql_exception $error) {
    errorRegistroRut(
        $error->getCode() === 1062
            ? "Este RUT ya está registrado."
            : "No fue posible crear la cuenta.",
    );
}
header("Location: login.php?registro=exitoso");
exit();
