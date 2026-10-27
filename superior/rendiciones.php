<?php
require_once __DIR__ . "/../config/aviso.php";

// Controlador propio del superior: esta ruta no ejecuta controladores de administrador.
require_once __DIR__ . "/../config/sesion.php";
if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso reservado al superior.");
}

require_once __DIR__ . "/../config/sesion.php";

require_once "../config/conexion.php";

date_default_timezone_set("America/Santiago");

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");

    exit();
}

if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["administrador", "superior"], true)) {
    header("Location: ../index.php");

    exit();
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function dinero(mixed $valor): string
{
    return "$" . number_format((float) $valor, 0, ",", ".");
}

function fecha(?string $valor): string
{
    if (empty($valor)) {
        return "-";
    }

    $timestamp = strtotime($valor);

    if ($timestamp === false) {
        return "-";
    }

    return date("d/m/Y", $timestamp);
}

function fechaHora(?string $valor): string
{
    if (empty($valor)) {
        return "-";
    }

    $timestamp = strtotime($valor);

    if ($timestamp === false) {
        return "-";
    }

    return date("d/m/Y H:i", $timestamp);
}

function categoriaGasto(?string $categoria): string
{
    return match ($categoria) {
        "arancel" => "Arancel",

        "matricula" => "Matrícula",

        "materiales" => "Materiales de estudio",

        "vestuario" => "Vestuario",

        "alimentacion" => "Alimentación",

        default => ucfirst(str_replace("_", " ", (string) $categoria)),
    };
}

function estadoBoleta(?string $estado): string
{
    return match ($estado) {
        "validado" => "Aceptada",

        "rechazado" => "Rechazada",

        default => "Pendiente",
    };
}

function claseEstadoBoleta(?string $estado): string
{
    return match ($estado) {
        "validado" => "estado-validado",

        "rechazado" => "estado-rechazado",

        default => "estado-pendiente",
    };
}

function textoEstadoRendicion(?string $estado): string
{
    return match ($estado) {
        "enviada" => "En revisión",

        "en_revision" => "En revisión",

        "aprobada" => "Aprobada",

        "rechazada" => "Rechazada",

        default => ucfirst(str_replace("_", " ", (string) $estado)),
    };
}

$buscar = trim($_GET["buscar"] ?? "");

$estadoFiltro = trim($_GET["estado"] ?? "");

$porPagina = 25;

$paginaActual = filter_input(INPUT_GET, "pagina", FILTER_VALIDATE_INT);

if (!$paginaActual || $paginaActual < 1) {
    $paginaActual = 1;
}

require_once __DIR__ . "/../config/proceso_activo.php";
$procesoActivoId = idProcesoActivoBeca($conn);
$procesoRendiciones = $conn
    ->query("SELECT id,nombre,tipo_proceso FROM procesos_beca WHERE id=$procesoActivoId")
    ->fetch_assoc() ?: [];
$esSoloRendicion = ($procesoRendiciones["tipo_proceso"] ?? "") === "solo_rendicion";
$_SESSION["csrf_padron_rendicion"] ??= bin2hex(random_bytes(32));
$totalPadronRendicion = 0;
if ($esSoloRendicion) {
    $totalPadronRendicion = (int) ($conn
        ->query("SELECT COUNT(*) total FROM obligados_rendicion WHERE proceso_id=$procesoActivoId")
        ->fetch_assoc()["total"] ?? 0);
}

$where = "
    WHERE r.proceso_id = ? AND r.estado IN (
        'enviada',
        'en_revision',
        'aprobada',
        'rechazada'
    )
";

$tipos = "i";

$parametros = [$procesoActivoId];

if ($buscar !== "") {
    $where .= "
        AND
        (
            CONCAT(
                dp.nombres,
                ' ',
                dp.apellido_paterno,
                ' ',
                dp.apellido_materno
            ) LIKE ?

            OR dp.rut LIKE ?

            OR dp.correo_electronico LIKE ?
        )
    ";

    $buscarLike = "%" . $buscar . "%";

    $tipos .= "sss";

    $parametros[] = $buscarLike;

    $parametros[] = $buscarLike;

    $parametros[] = $buscarLike;
}

if (in_array($estadoFiltro, ["en_revision", "aprobada", "rechazada"], true)) {
    if ($estadoFiltro === "en_revision") {
        $where .= "
            AND r.estado IN (
                'enviada',
                'en_revision'
            )
        ";
    } else {
        $where .= "
            AND r.estado = ?
        ";

        $tipos .= "s";

        $parametros[] = $estadoFiltro;
    }
}

$sqlTotal = "
    SELECT

        COUNT(*) AS total

    FROM rendiciones r

    LEFT JOIN postulaciones p ON p.id = r.postulacion_id

    INNER JOIN personas_rendicion dp ON dp.rendicion_id = r.id

    $where
";

$stmtTotal = $conn->prepare($sqlTotal);

if (!$stmtTotal) {
    mostrarAvisoBecas("No fue posible contar las rendiciones.");
}

if ($tipos !== "" && !empty($parametros)) {
    $stmtTotal->bind_param($tipos, ...$parametros);
}

$stmtTotal->execute();

$filaTotal = $stmtTotal->get_result()->fetch_assoc();

$totalRendicionesFiltradas = (int) ($filaTotal["total"] ?? 0);

$stmtTotal->close();

$totalPaginas = max(1, (int) ceil($totalRendicionesFiltradas / $porPagina));

if ($paginaActual > $totalPaginas) {
    $paginaActual = $totalPaginas;
}

$offset = ($paginaActual - 1) * $porPagina;

$sql = "
    SELECT

        r.id,
        r.estado,
        r.anio_cursa,
        r.fecha_envio,
        r.fecha_revision,

        p.id AS postulacion_id,
        COALESCE(p.folio, CONCAT('REND-', r.id)) AS folio,
        COALESCE(p.monto_asignado, r.monto_anual, r.monto_primer_semestre + r.monto_segundo_semestre) AS monto_asignado,
        p.monto_primer_semestre,
        p.monto_segundo_semestre,

        dp.nombres,
        dp.apellido_paterno,
        dp.apellido_materno,
        dp.rut,
        dp.correo_electronico,

        (
            SELECT COUNT(*)

            FROM rendicion_gastos rg

            WHERE rg.rendicion_id = r.id

        ) AS total_boletas,

        (
            SELECT COUNT(*)

            FROM rendicion_gastos rg

            WHERE
                rg.rendicion_id = r.id

                AND rg.estado_validacion = 'validado'

        ) AS boletas_validadas,

        (
            SELECT COUNT(*)

            FROM rendicion_gastos rg

            WHERE
                rg.rendicion_id = r.id

                AND rg.estado_validacion = 'rechazado'

        ) AS boletas_rechazadas

    FROM rendiciones r

    LEFT JOIN postulaciones p ON p.id = r.postulacion_id

    INNER JOIN personas_rendicion dp ON dp.rendicion_id = r.id

    $where

    ORDER BY

        CASE

            WHEN r.estado IN (
                'enviada',
                'en_revision'
            )
                THEN 1

            WHEN r.estado = 'rechazada'
                THEN 2

            WHEN r.estado = 'aprobada'
                THEN 3

            ELSE 4

        END,

        r.fecha_envio DESC,

        r.id DESC

    LIMIT ? OFFSET ?
";

$tiposConsulta = $tipos . "ii";

$parametrosConsulta = $parametros;

$parametrosConsulta[] = $porPagina;

$parametrosConsulta[] = $offset;

$stmt = $conn->prepare($sql);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar las rendiciones.");
}

$stmt->bind_param($tiposConsulta, ...$parametrosConsulta);

$stmt->execute();

$resultado = $stmt->get_result();

$rendiciones = [];

while ($fila = $resultado->fetch_assoc()) {
    $rendiciones[] = $fila;
}

$stmt->close();

function urlPaginaRendiciones(int $pagina, string $buscar, string $estadoFiltro): string
{
    $query = [
        "pagina" => $pagina,
    ];

    if ($buscar !== "") {
        $query["buscar"] = $buscar;
    }

    if ($estadoFiltro !== "") {
        $query["estado"] = $estadoFiltro;
    }

    return "rendiciones.php?" . http_build_query($query);
}

$totalRendiciones = $totalRendicionesFiltradas;

$totalGeneralesBoletas = 0;

$totalGeneralesAceptadas = 0;

$totalGeneralesRechazadas = 0;

foreach ($rendiciones as $rendicionResumen) {
    $totalGeneralesBoletas += (int) $rendicionResumen["total_boletas"];

    $totalGeneralesAceptadas += (int) $rendicionResumen["boletas_validadas"];

    $totalGeneralesRechazadas += (int) $rendicionResumen["boletas_rechazadas"];
}

$estadisticasRendiciones = [
    "total" => 0,

    "revision" => 0,

    "aprobadas" => 0,

    "rechazadas" => 0,
];

$sqlEstadisticasRendiciones = "
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE

                WHEN estado IN (
                    'enviada',
                    'en_revision'
                )

                THEN 1

                ELSE 0

            END
        ) AS revision,

        SUM(
            CASE

                WHEN estado = 'aprobada'

                THEN 1

                ELSE 0

            END
        ) AS aprobadas,

        SUM(
            CASE

                WHEN estado = 'rechazada'

                THEN 1

                ELSE 0

            END
        ) AS rechazadas

    FROM rendiciones

    WHERE proceso_id = $procesoActivoId AND estado IN (
        'enviada',
        'en_revision',
        'aprobada',
        'rechazada'
    )
";

$resultadoEstadisticasRendiciones = $conn->query($sqlEstadisticasRendiciones);

if ($resultadoEstadisticasRendiciones) {
    $filaEstadisticas = $resultadoEstadisticasRendiciones->fetch_assoc();

    $estadisticasRendiciones["total"] = (int) ($filaEstadisticas["total"] ?? 0);

    $estadisticasRendiciones["revision"] = (int) ($filaEstadisticas["revision"] ?? 0);

    $estadisticasRendiciones["aprobadas"] = (int) ($filaEstadisticas["aprobadas"] ?? 0);

    $estadisticasRendiciones["rechazadas"] = (int) ($filaEstadisticas["rechazadas"] ?? 0);
}

require_once __DIR__ . "/../vistas/superior/rendiciones.php";
