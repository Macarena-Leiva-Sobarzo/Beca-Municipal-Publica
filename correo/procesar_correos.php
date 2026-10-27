<?php

declare(strict_types=1);
if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit();
}
// Ejecutar cada cinco minutos desde cron (hosting) o el Programador de tareas (Windows).
$error = 0;
foreach (["enviar_postulaciones_pendientes.php", "enviar_resultados_pendientes.php"] as $archivo) {
    passthru(escapeshellarg(PHP_BINARY) . " " . escapeshellarg(__DIR__ . "/" . $archivo), $codigo);
    if ($codigo !== 0) {
        $error = 1;
    }
}
exit($error);
