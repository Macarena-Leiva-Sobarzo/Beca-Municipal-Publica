<?php

require_once __DIR__ . "/../config/sesion.php";

if (isset($_SESSION["usuario_id"])) {
    header("Location: ../usuario/inicio.php");

    exit();
}

$rut = "";

if (isset($_GET["rut"])) {
    $rut = trim($_GET["rut"]);
}

require_once __DIR__ . "/../vistas/auth/registro.php";
