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

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    !in_array($_SESSION["rol"], ["administrador", "superior"], true)
) {
    header("Location: ../auth/login.php");

    exit();
}

$busqueda = trim($_GET["buscar"] ?? "");

$estado = trim($_GET["estado"] ?? "");

$estadosPermitidos = ["", "en_revision", "beneficiado", "no_beneficiado"];

if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = "";
}

$porPagina = 25;

$paginaActual = filter_input(INPUT_GET, "pagina", FILTER_VALIDATE_INT);

if (!$paginaActual || $paginaActual < 1) {
    $paginaActual = 1;
}

require_once __DIR__ . "/../config/proceso_activo.php";
$procesoActivoId = idProcesoActivoBeca($conn);

$where = " WHERE p.proceso_id = ? ";
$tipos = "i";
$parametros = [$procesoActivoId];

if ($busqueda !== "") {
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

    $buscarLike = "%" . $busqueda . "%";

    $tipos .= "sss";

    $parametros[] = $buscarLike;

    $parametros[] = $buscarLike;

    $parametros[] = $buscarLike;
}

if ($estado !== "") {
    $where .= "
        AND p.estado = ?
    ";

    $tipos .= "s";

    $parametros[] = $estado;
}

$sqlTotal = "
    SELECT COUNT(*) AS total

    FROM postulaciones p

    INNER JOIN datos_personales dp
        ON dp.postulacion_id = p.id

    $where
";

$stmtTotal = $conn->prepare($sqlTotal);

if (!$stmtTotal) {
    mostrarAvisoBecas("No fue posible contar las postulaciones.");
}

if (!empty($parametros)) {
    $stmtTotal->bind_param($tipos, ...$parametros);
}

$stmtTotal->execute();

$resultadoTotal = $stmtTotal->get_result();

$filaTotal = $resultadoTotal->fetch_assoc();

$totalPostulaciones = (int) ($filaTotal["total"] ?? 0);

$stmtTotal->close();

$totalPaginas = max(1, (int) ceil($totalPostulaciones / $porPagina));

if ($paginaActual > $totalPaginas) {
    $paginaActual = $totalPaginas;
}

$offset = ($paginaActual - 1) * $porPagina;

$estadisticasPostulaciones = [
    "total" => 0,
    "revision" => 0,
    "beneficiados" => 0,
    "rechazados" => 0,
];

$sqlEstadisticas = "
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE
                WHEN estado = 'en_revision'
                THEN 1
                ELSE 0
            END
        ) AS revision,

        SUM(
            CASE
                WHEN estado = 'beneficiado'
                THEN 1
                ELSE 0
            END
        ) AS beneficiados,

        SUM(
            CASE
                WHEN estado = 'no_beneficiado'
                THEN 1
                ELSE 0
            END
        ) AS rechazados

    FROM postulaciones
    WHERE proceso_id = $procesoActivoId
";

$resultadoEstadisticas = $conn->query($sqlEstadisticas);

if ($resultadoEstadisticas) {
    $filaEstadisticas = $resultadoEstadisticas->fetch_assoc();

    $estadisticasPostulaciones["total"] = (int) ($filaEstadisticas["total"] ?? 0);

    $estadisticasPostulaciones["revision"] = (int) ($filaEstadisticas["revision"] ?? 0);

    $estadisticasPostulaciones["beneficiados"] = (int) ($filaEstadisticas["beneficiados"] ?? 0);

    $estadisticasPostulaciones["rechazados"] = (int) ($filaEstadisticas["rechazados"] ?? 0);
}

$sql = "
    SELECT

        p.id,
        p.estado,
        p.fecha_postulacion,

        dp.nombres,
        dp.apellido_paterno,
        dp.apellido_materno,
        dp.rut,
        dp.correo_electronico,

        documentos_estado.carnet_estado,
        documentos_estado.alumno_regular_estado,
        documentos_estado.certificado_notas_estado,
        documentos_estado.registro_social_estado,
        documentos_estado.acreditacion_gratuidad_estado

    FROM postulaciones p

    INNER JOIN datos_personales dp
        ON dp.postulacion_id = p.id

    LEFT JOIN
    (
        SELECT

            postulacion_id,

            MAX(
                CASE
                    WHEN tipo_documento = 'carnet'
                    THEN estado_validacion
                END
            ) AS carnet_estado,

            MAX(
                CASE
                    WHEN tipo_documento = 'alumno_regular'
                    THEN estado_validacion
                END
            ) AS alumno_regular_estado,

            MAX(
                CASE
                    WHEN tipo_documento = 'certificado_notas'
                    THEN estado_validacion
                END
            ) AS certificado_notas_estado,
            MAX(CASE WHEN tipo_documento = 'registro_social' THEN estado_validacion END) AS registro_social_estado,
            MAX(CASE WHEN tipo_documento = 'acreditacion_gratuidad' THEN estado_validacion END) AS acreditacion_gratuidad_estado

        FROM documentos

        GROUP BY postulacion_id

    ) documentos_estado

        ON documentos_estado.postulacion_id = p.id

    $where

    ORDER BY

        CASE

            WHEN p.estado = 'en_revision'
                THEN 1

            WHEN p.estado = 'no_beneficiado'
                THEN 2

            WHEN p.estado = 'beneficiado'
                THEN 3

            ELSE 4

        END,

        p.fecha_postulacion DESC,

        p.id DESC

    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    mostrarAvisoBecas("No fue posible consultar las postulaciones.");
}

$tiposConsulta = $tipos . "ii";

$parametrosConsulta = $parametros;

$parametrosConsulta[] = $porPagina;

$parametrosConsulta[] = $offset;

$stmt->bind_param($tiposConsulta, ...$parametrosConsulta);

$stmt->execute();

$resultado = $stmt->get_result();

$mensajeExito = $_SESSION["admin_exito"] ?? null;

$mensajeError = $_SESSION["admin_error"] ?? null;

unset($_SESSION["admin_exito"], $_SESSION["admin_error"]);

function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
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

function mostrarEstadoDocumento(?string $estado): string
{
    return match ($estado) {
        "validado" => '<span class="estado-doc-listado validado">✓ Aceptado</span>',

        "rechazado" => '<span class="estado-doc-listado rechazado">× Rechazado</span>',

        "pendiente" => '<span class="estado-doc-listado pendiente">Pendiente</span>',

        default => '<span class="estado-doc-listado sin-documento">Sin documento</span>',
    };
}

function textoEstadoPostulacion(string $estado): string
{
    return match ($estado) {
        "pendiente" => "Pendiente",

        "en_revision" => "En revisión",

        "beneficiado" => "Beneficiado",

        "no_beneficiado" => "No beneficiado",

        default => ucfirst(str_replace("_", " ", $estado)),
    };
}

function claseEstadoPostulacion(string $estado): string
{
    return match ($estado) {
        "pendiente", "en_revision" => "pendiente",

        "beneficiado" => "aprobada",

        "no_beneficiado" => "rechazada",

        default => "pendiente",
    };
}

function urlPagina(int $pagina, string $busqueda, string $estado): string
{
    $query = [
        "pagina" => $pagina,
    ];

    if ($busqueda !== "") {
        $query["buscar"] = $busqueda;
    }

    if ($estado !== "") {
        $query["estado"] = $estado;
    }

    return "postulaciones.php?" . http_build_query($query);
}

$rutaCss = __DIR__ . "/../css/estilos.css";

$versionCss = is_file($rutaCss) ? (string) filemtime($rutaCss) : (string) time();

require_once __DIR__ . "/../vistas/superior/postulaciones.php";
