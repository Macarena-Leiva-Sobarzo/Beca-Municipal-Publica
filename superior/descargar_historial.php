<?php
require_once __DIR__ . "/../config/aviso.php";

// Controlador propio del superior: esta ruta no ejecuta controladores de administrador.
require_once __DIR__ . "/../config/sesion.php";
if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso reservado al superior.");
}

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/datos_historial.php";
accesoHistorial($conn);
if (
    $_SERVER["REQUEST_METHOD"] !== "POST" ||
    !is_string($_POST["token"] ?? null) ||
    empty($_SESSION["token_historial"]) ||
    !hash_equals($_SESSION["token_historial"], $_POST["token"])
) {
    http_response_code(403);
    mostrarAvisoBecas("Solicitud no válida.");
}
$formato = $_POST["formato"] ?? "";
if (!in_array($formato, ["pdf", "excel"], true)) {
    http_response_code(400);
    mostrarAvisoBecas("Formato no válido.");
}
$filtro = filtrosHistorial($_POST);
// Excel interpreta algunos textos como fórmulas; neutralizarlos antes de generar la celda.
$celda = static function ($v) use ($formato) {
    $v = (string) ($v ?? "");
    if ($formato === "excel" && preg_match("/^[\s]*[=+@-]/u", $v)) {
        $v = "'" . $v;
    }
    return escHistorial($v);
};
$consultaDescargador = $conn->prepare(
    "SELECT nombres, apellido_paterno, apellido_materno, correo, rol FROM usuarios WHERE id=? LIMIT 1",
);
$consultaDescargador->bind_param("i", $_SESSION["usuario_id"]);
$consultaDescargador->execute();
$descargador = $consultaDescargador->get_result()->fetch_assoc() ?: [];
$consultaDescargador->close();
$nombreDescargador = trim(implode(" ", array_filter([
    $descargador["nombres"] ?? "",
    $descargador["apellido_paterno"] ?? "",
    $descargador["apellido_materno"] ?? "",
])));
$nombreDescargador = $nombreDescargador !== "" ? $nombreDescargador : ($descargador["correo"] ?? "Usuario sin nombre");
$rolDescargador = $descargador["rol"] ?? ($_SESSION["rol"] ?? "Sin rol");
$fechaGeneracion = date("d-m-Y H:i:s");
$claseEstado = static function (?string $estado): string {
    return match (strtolower((string) $estado)) {
        "beneficiado", "aprobada" => "estado-aprobado",
        "no_beneficiado", "rechazada" => "estado-rechazado",
        default => "estado-en-revision",
    };
};
$estiloEstado = static function (string $clase): string {
    return match ($clase) {
        "estado-aprobado" => "display:inline-block;padding:4px 8px;border-radius:999px;font-weight:700;color:#166534;background:#dcfce7",
        "estado-rechazado" => "display:inline-block;padding:4px 8px;border-radius:999px;font-weight:700;color:#b91c1c;background:#fee2e2",
        default => "display:inline-block;padding:4px 8px;border-radius:999px;font-weight:700;color:#9a3412;background:#ffedd5",
    };
};
$html =
    '<html lang="es"><head><meta charset="UTF-8"><style>@page{margin:28px}body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#1f2937}h1{margin:0;color:#1d4ed8;font-size:22px}.subtitulo{margin:5px 0 18px;color:#64748b}.metadatos{margin:0 0 22px;padding:14px 16px;border:1px solid #cbd5e1;background:#f8fafc}.metadatos p{margin:0 0 8px}.metadatos p:last-child{margin-bottom:0}h2{margin:20px 0 9px;color:#1f2937;font-size:15px}table{width:100%;border-collapse:collapse;table-layout:fixed}th,td{border:1px solid #cbd5e1;padding:6px;word-wrap:break-word}th{background:#dbeafe;color:#1e3a8a}thead{display:table-header-group}tr{page-break-inside:avoid}.estado{display:inline-block;padding:4px 8px;border-radius:999px;font-weight:700}.estado-aprobado{color:#166534;background:#dcfce7}.estado-rechazado{color:#b91c1c;background:#fee2e2}.estado-en-revision{color:#9a3412;background:#ffedd5}</style></head><body><h1>Becas Estudiantiles</h1><p class="subtitulo">Historial de postulaciones y rendiciones</p><div class="metadatos"><p><strong>Fecha y hora de generación:</strong><br>' .
    $celda($fechaGeneracion) .
    '</p><p><strong>Descargado por:</strong> ' .
    $celda($nombreDescargador) .
    '<br><strong>Rol:</strong> ' .
    $celda($rolDescargador) .
    '</p></div><p>Año de la beca: ' .
    $celda($filtro["anio"] ?? "Todos") .
    " · Búsqueda: " .
    $celda($filtro["buscar"] ?: "Sin filtro") .
    "</p>";
foreach (["postulaciones", "rendiciones"] as $tipo) {
    $lista = consultarHistorial($conn, $tipo, $filtro, null);
    $html .=
        "<h2>" .
        ucfirst($tipo) .
        " (" .
        $lista["total"] .
        ")</h2><table><thead><tr><th>Año</th><th>Nombre</th><th>RUT</th><th>Correo</th><th>Teléfono</th><th>" .
        ($tipo === "postulaciones" ? "Resultado de postulación" : "Rendición enviada") .
        "</th>" .
        ($tipo === "rendiciones" ? "<th>Estado de revisión</th>" : "") .
        "</tr></thead><tbody>";
    foreach ($lista["filas"] as $fila) {
        $html .= "<tr>";
        foreach (["anio", "nombre", "rut", "correo", "telefono"] as $campo) {
            $html .= '<td style="mso-number-format:\@">' . $celda($fila[$campo]) . "</td>";
        }
        $claseResultado = $tipo === "postulaciones" ? $claseEstado($fila["estado"] ?? null) : "estado-en-revision";
        $html .= '<td><span class="estado ' . $claseResultado . '" style="' . $estiloEstado($claseResultado) . '">' . $celda(textoHistorial($fila, $tipo)) . "</span></td>";
        if ($tipo === "rendiciones") {
            $claseRevision = $claseEstado($fila["estado"] ?? null);
            $html .= '<td><span class="estado ' . $claseRevision . '" style="' . $estiloEstado($claseRevision) . '">' . $celda(ucfirst(str_replace("_", " ", $fila["estado"]))) . "</span></td>";
        }
        $html .= "</tr>";
    }
    if (!$lista["filas"]) {
        $html .=
            '<tr><td colspan="' .
            ($tipo === "rendiciones" ? 7 : 6) .
            '">Sin registros para estos filtros.</td></tr>';
    }
    $html .= "</tbody></table>";
}
$html .= "</body></html>";
$nombre = "historial_becas_" . ($filtro["anio"] ?? "todos") . "_" . date("Y-m-d");
header("Cache-Control: no-store");
if ($formato === "excel") {
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header('Content-Disposition: attachment; filename="' . $nombre . '.xls"');
    echo "\xEF\xBB\xBF" . $html;
    exit();
}
require_once __DIR__ . "/../vendor/autoload.php";
$options = new Dompdf\Options();
$options->set("isRemoteEnabled", false);
$pdf = new Dompdf\Dompdf($options);
$pdf->loadHtml($html, "UTF-8");
$pdf->setPaper("A4", "landscape");
$pdf->render();
$pdf->stream($nombre . ".pdf", ["Attachment" => true]);
