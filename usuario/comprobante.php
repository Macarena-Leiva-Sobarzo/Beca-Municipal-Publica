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

$sql = "
    SELECT
        postulaciones.id,
        postulaciones.estado
    FROM postulaciones
    INNER JOIN procesos_beca
        ON procesos_beca.id = postulaciones.proceso_id
    WHERE usuario_id = ?
        AND procesos_beca.activo = 1
    ORDER BY id DESC
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar la postulación.");
}

$stmt->bind_param("i", $usuario_id);

$stmt->execute();

$resultado = $stmt->get_result();

$postulacion = $resultado->fetch_assoc();

$stmt->close();

if (!$postulacion) {
    header("Location: postulacion.php");

    exit();
}

if ($postulacion["estado"] === "pendiente") {
    header("Location: postulacion.php");

    exit();
}

require_once __DIR__ . "/../vistas/usuario/comprobante.php";
