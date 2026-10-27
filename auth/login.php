<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";

if (isset($_SESSION["usuario_id"])) {
    $portal = in_array($_SESSION["rol"] ?? "", ["administrador", "superior"], true)
        ? $_SESSION["rol"]
        : "usuario";
    header("Location: ../" . $portal . "/inicio.php");

    exit();
}

$hayErrorLogin = isset($_GET["error"]);
$rut_anterior = $hayErrorLogin ? "" : ($_GET["rut"] ?? "");
$modoAdministrativo = ($_GET["modo"] ?? "") === "administrador";

$soloRendicionAcceso = procesoSoloRendicionActivo($conn) !== null && !$modoAdministrativo;

if ($soloRendicionAcceso) {
    require_once __DIR__ . "/../vistas/auth/login_solo_rendicion.php";
    exit();
}

require_once __DIR__ . "/../vistas/auth/login.php";
