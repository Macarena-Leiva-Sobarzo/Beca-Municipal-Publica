<?php
require_once __DIR__ . "/aviso.php";
set_exception_handler(static function (Throwable $error): void {
    error_log("Error no controlado: " . $error->getMessage());
    mostrarAvisoBecas(
        "No fue posible completar la solicitud. Inténtalo nuevamente más tarde.",
        500,
    );
});
require_once __DIR__ . "/horario.php";
require_once __DIR__ . "/entorno.php";
// El registro queda fuera del acceso web gracias a logs/.htaccess.
if (is_dir(__DIR__ . "/../logs") && is_writable(__DIR__ . "/../logs")) {
    ini_set("error_log", __DIR__ . "/../logs/php_errors.log");
}
if (esProduccionBecas()) {
    // Conservar errores para soporte sin mostrar rutas, consultas o datos al visitante.
    ini_set("display_errors", "0");
    ini_set("log_errors", "1");
}
// Evita reutilizar sesiones del proyecto de rendición al cambiar de base.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name("BECAS_COMPLETO");
    $https = !empty($_SERVER["HTTPS"]) && strtolower((string) $_SERVER["HTTPS"]) !== "off";
    session_set_cookie_params(["httponly" => true, "secure" => $https, "samesite" => "Lax"]);
    session_start();
}
