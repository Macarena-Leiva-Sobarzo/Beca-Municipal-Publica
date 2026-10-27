<?php
if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit();
}
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/notificar_resultado_rendicion.php";
date_default_timezone_set("America/Santiago");
ini_set("error_log", __DIR__ . "/../logs/correos_automaticos.log");

function guardarLog(string $mensaje): void
{
    file_put_contents(
        __DIR__ . "/../logs/resultados.log",
        date("d-m-Y H:i:s") . " - " . $mensaje . PHP_EOL,
        FILE_APPEND | LOCK_EX,
    );
}

$totalEnviados = 0;
$totalErrores = 0;
try {
    $desde = require __DIR__ . "/../config/correos_automaticos.php";
    $ahora = date("Y-m-d H:i:s");
    $stmt = $conn->prepare("SELECT r.id FROM rendiciones r
        LEFT JOIN postulaciones p ON p.id = r.postulacion_id
        INNER JOIN procesos_beca pb ON pb.id = COALESCE(p.proceso_id, r.proceso_id)
        WHERE r.estado IN ('aprobada', 'rechazada')
            AND pb.fecha_resultados_rendicion <= ? AND r.correo_resultado_enviado = 0
            AND (r.fecha_revision >= ? OR pb.fecha_resultados_rendicion >= ?)
        ORDER BY r.id");
    $stmt->bind_param("sss", $ahora, $desde, $desde);
    $stmt->execute();
    $pendientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($pendientes as $pendiente) {
        $id = (int) $pendiente["id"];
        $resultado = notificarResultadoRendicion($conn, $id);
        if ($resultado === "enviado") {
            $totalEnviados++;
            guardarLog("Correo enviado correctamente | Rendición ID: " . $id);
        } elseif ($resultado === "error") {
            $totalErrores++;
            guardarLog("ERROR enviando correo | Rendición ID: " . $id);
        }
    }
} catch (Throwable $e) {
    $totalErrores++;
    guardarLog("ERROR procesando resultados: " . $e->getMessage());
}
guardarLog("Proceso finalizado. Enviados: $totalEnviados | Errores: $totalErrores");
echo "Proceso finalizado. Correos enviados: $totalEnviados. Errores: $totalErrores." . PHP_EOL;
exit($totalErrores ? 1 : 0);
