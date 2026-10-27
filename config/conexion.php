<?php
require_once __DIR__ . "/aviso.php";
require_once __DIR__ . "/horario.php";
require_once __DIR__ . "/entorno.php";

/**
 * Conexión compartida a MySQL. Las credenciales corresponden al entorno de instalación.
 */

try {
    ini_set("log_errors", "1");
    if (is_dir(__DIR__ . "/../logs") && is_writable(__DIR__ . "/../logs")) {
        ini_set("error_log", __DIR__ . "/../logs/php_errors.log");
    }
    if (esProduccionBecas()) {
        ini_set("display_errors", "0");
    }
    $host = configuracionBecas("BECAS_DB_HOST", "localhost");
    $usuario = configuracionBecas("BECAS_DB_USER");
    $password = configuracionBecas("BECAS_DB_PASSWORD");
    $base_datos = configuracionBecas("BECAS_DB");
    $puerto = (int) configuracionBecas("BECAS_DB_PORT", "3306");
    if ($usuario === "" || $base_datos === "") {
        throw new RuntimeException(
            "Falta configurar BECAS_DB_USER o BECAS_DB en config/config.local.php.",
        );
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli($host, $usuario, $password, $base_datos, $puerto);
    $conn->set_charset("utf8mb4");
    $desfaseChile = (new DateTimeImmutable("now", new DateTimeZone("America/Santiago")))->format(
        "P",
    );
    $conn->query("SET time_zone = '$desfaseChile'");
    require_once __DIR__ . "/obligados_rendicion.php";
    registrarObligadosRendicion2026($conn);
} catch (Throwable $error) {
    error_log("Inicialización de becas: " . $error->getMessage());
    http_response_code(503);
    mostrarAvisoBecas("El servicio no está disponible temporalmente. Inténtalo más tarde.");
}
