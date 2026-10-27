<?php
require_once __DIR__ . "/../config/aviso.php";

/** Consultas compartidas: la pantalla pagina; la exportación usa todos los resultados del filtro. */
function accesoHistorial(mysqli $db): void
{
    if (
        !isset($_SESSION["usuario_id"]) ||
        !in_array($_SESSION["rol"] ?? "", ["administrador", "superior"], true)
    ) {
        http_response_code(403);
        mostrarAvisoAccesoBecas("Acceso no autorizado.");
    }
    $s = $db->prepare(
        "SELECT id FROM usuarios WHERE id=? AND activo=1 AND rol IN ('administrador','superior')",
    );
    $s->bind_param("i", $_SESSION["usuario_id"]);
    $s->execute();
    $valido = $s->get_result()->num_rows > 0;
    $s->close();
    if (!$valido) {
        http_response_code(403);
        mostrarAvisoAccesoBecas("La cuenta no tiene permisos vigentes.");
    }
}
function origenHistorial(string $tipo): string
{
    if ($tipo === "postulaciones") {
        return "SELECT p.id, YEAR(p.fecha_postulacion) anio, CONCAT_WS(' ',dp.nombres,dp.apellido_paterno,dp.apellido_materno) COLLATE utf8mb4_unicode_ci nombre, dp.rut COLLATE utf8mb4_unicode_ci rut, dp.correo_electronico COLLATE utf8mb4_unicode_ci correo, dp.telefono COLLATE utf8mb4_unicode_ci telefono, p.estado COLLATE utf8mb4_unicode_ci estado, NULL fecha_envio, p.fecha_postulacion fecha, (p.estado='beneficiado') beneficiario FROM postulaciones p LEFT JOIN datos_personales dp ON dp.postulacion_id=p.id";
    }
    // El historial registra únicamente rendiciones que ya fueron enviadas.
    return "SELECT r.id, YEAR(p.fecha_postulacion) anio, CONCAT_WS(' ',dp.nombres,dp.apellido_paterno,dp.apellido_materno) COLLATE utf8mb4_unicode_ci nombre, dp.rut COLLATE utf8mb4_unicode_ci rut, dp.correo_electronico COLLATE utf8mb4_unicode_ci correo, dp.telefono COLLATE utf8mb4_unicode_ci telefono, r.estado COLLATE utf8mb4_unicode_ci estado, r.fecha_envio, p.fecha_postulacion fecha, (p.estado='beneficiado') beneficiario FROM postulaciones p LEFT JOIN datos_personales dp ON dp.postulacion_id=p.id INNER JOIN rendiciones r ON r.postulacion_id=p.id AND r.fecha_envio IS NOT NULL UNION ALL SELECT r.id, YEAR(COALESCE(pb.fecha_inicio_rendicion,r.fecha_inicio,r.fecha_envio)) anio,CONCAT_WS(' ',d.nombres,d.apellido_paterno,d.apellido_materno) COLLATE utf8mb4_unicode_ci nombre,d.rut COLLATE utf8mb4_unicode_ci rut,d.correo_electronico COLLATE utf8mb4_unicode_ci correo,d.telefono COLLATE utf8mb4_unicode_ci telefono,r.estado COLLATE utf8mb4_unicode_ci estado,r.fecha_envio,COALESCE(r.fecha_envio,r.fecha_inicio,pb.fecha_inicio_rendicion) fecha, 1 beneficiario FROM rendiciones r JOIN personas_rendicion d ON d.rendicion_id=r.id JOIN procesos_beca pb ON pb.id=r.proceso_id WHERE r.postulacion_id IS NULL AND r.acceso_rut_id IS NOT NULL AND r.fecha_envio IS NOT NULL";
}
function consultarHistorial(mysqli $db, string $tipo, array $filtro, ?int $pagina = 1): array
{
    $origen = origenHistorial($tipo);
    $where = " WHERE 1=1";
    $params = [];
    $tipos = "";
    if ($filtro["anio"] !== null) {
        $where .= " AND anio=?";
        $params[] = $filtro["anio"];
        $tipos .= "i";
    }
    if ($filtro["buscar"] !== "") {
        $where .=
            " AND (nombre LIKE ? OR UPPER(REPLACE(REPLACE(REPLACE(rut,'.',''),'-',''),' ','')) LIKE ?)";
        $params[] = "%" . $filtro["buscar"] . "%";
        $params[] = "%" . strtoupper(str_replace([".", "-", " "], "", $filtro["buscar"])) . "%";
        $tipos .= "ss";
    }
    $ejecutar = static function ($sql) use ($db, $params, $tipos) {
        $s = $db->prepare($sql);
        if ($params) {
            $s->bind_param($tipos, ...$params);
        }
        $s->execute();
        $r = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        $s->close();
        return $r;
    };
    $total = (int) $ejecutar("SELECT COUNT(*) total FROM (" . $origen . ") h" . $where)[0]["total"];
    $paginas = max(1, (int) ceil($total / 25));
    $actual = $pagina === null ? null : max(1, min($paginas, $pagina));
    $limite = $actual === null ? "" : " LIMIT 25 OFFSET " . ($actual - 1) * 25;
    $resumen = $ejecutar(
        "SELECT COUNT(*) total, COALESCE(SUM(fecha_envio IS NOT NULL),0) rendiciones, COALESCE(SUM(beneficiario),0) beneficiarios FROM (" .
            $origen .
            ") h" .
            $where,
    )[0];
    $totalGeneral = (int) $db
        ->query("SELECT COUNT(*) total FROM (" . $origen . ") h")
        ->fetch_assoc()["total"];
    return [
        "filas" => $ejecutar(
            "SELECT * FROM (" . $origen . ") h" . $where . " ORDER BY fecha DESC,id DESC" . $limite,
        ),
        "total" => $total,
        "total_general" => $totalGeneral,
        "rendiciones_enviadas" => (int) $resumen["rendiciones"],
        "beneficiarios" => (int) $resumen["beneficiarios"],
        "pagina" => $actual,
        "paginas" => $paginas,
    ];
}
function filtrosHistorial(array $entrada): array
{
    $valor = $entrada["anio"] ?? "";
    $anio = filter_var($valor, FILTER_VALIDATE_INT);
    if ($valor !== "" && ($anio === false || $anio < 1900 || $anio > 2200)) {
        http_response_code(400);
        mostrarAvisoBecas("Año no válido.");
    }
    return [
        "anio" => $valor === "" ? null : $anio,
        "buscar" => mb_substr(trim((string) ($entrada["buscar"] ?? "")), 0, 150),
    ];
}
function textoHistorial(array $fila, string $tipo): string
{
    if ($tipo === "rendiciones") {
        return "Enviada";
    }
    return match ($fila["estado"]) {
        "beneficiado" => "Beneficiado",
        "no_beneficiado" => "No beneficiado",
        default => "Pendiente de resultado",
    };
}
function escHistorial($v): string
{
    return htmlspecialchars((string) ($v ?? ""), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}
