<?php

declare(strict_types=1);

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Disponible únicamente desde la tarea programada del servidor.");
}
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/notificar_resultado_postulacion.php";
ini_set("error_log", __DIR__ . "/../logs/correos_automaticos.log");

try {
    $desde = require __DIR__ . "/../config/correos_automaticos.php";
    $ahora = (new DateTimeImmutable("now", new DateTimeZone("America/Santiago")))->format(
        "Y-m-d H:i:s",
    );
    $stmt = $conn->prepare("SELECT p.id FROM postulaciones p
        INNER JOIN procesos_beca pb ON pb.id = p.proceso_id
        LEFT JOIN correos_resultado_postulacion c ON c.postulacion_id = p.id
        WHERE p.estado IN ('beneficiado', 'no_beneficiado') AND pb.fecha_resultados <= ?
        AND (p.fecha_resultado >= ? OR pb.fecha_resultados >= ?)
        AND (c.estado IS NULL OR c.estado = 'pendiente') ORDER BY p.id");
    $stmt->bind_param("sss", $ahora, $desde, $desde);
    $stmt->execute();
    $pendientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $errores = 0;
    foreach ($pendientes as $p) {
        $resultado = notificarResultadoPostulacion($conn, (int) $p["id"]);
        echo "Postulación " . (int) $p["id"] . ": " . $resultado . PHP_EOL;
        error_log("Postulación " . (int) $p["id"] . ": " . $resultado);
        if ($resultado === "error") {
            $errores++;
        }
    }
    exit($errores ? 1 : 0);
} catch (Throwable $e) {
    error_log("Error en envío de postulaciones: " . $e->getMessage());
    exit(1);
}
