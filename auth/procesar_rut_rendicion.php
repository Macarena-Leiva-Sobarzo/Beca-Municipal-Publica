<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$proceso = procesoSoloRendicionActivo($conn);
$rutIngresado = trim((string) ($_POST["rut"] ?? ""));
if (!$proceso || !rutSoloRendicionValido($rutIngresado)) {
    header("Location: login.php?error=rut");
    exit();
}

$rut = rutSoloRendicionNormalizado($rutIngresado);
$consulta = $conn->prepare(
    "SELECT u.id,u.rut,u.correo FROM usuarios u
     JOIN obligados_rendicion o ON o.usuario_id=u.id
     WHERE o.proceso_id=? AND u.rol='usuario' AND u.activo=1
       AND REPLACE(REPLACE(REPLACE(UPPER(u.rut),'.',''),'-',''),' ','')=? LIMIT 1",
);
$procesoId = (int) $proceso["id"];
$consulta->bind_param("is", $procesoId, $rut);
$consulta->execute();
$usuario = $consulta->get_result()->fetch_assoc();
$consulta->close();

if (!$usuario) {
    header("Location: login.php?error=no_autorizado");
    exit();
}

session_regenerate_id(true);
$_SESSION["usuario_id"] = (int) $usuario["id"];
$_SESSION["acceso_rut_id"] = (int) $usuario["id"];
$_SESSION["rut"] = $usuario["rut"];
$_SESSION["correo"] = $usuario["correo"];
$_SESSION["rol"] = "usuario";
header("Location: ../usuario/inicio.php");
exit();
