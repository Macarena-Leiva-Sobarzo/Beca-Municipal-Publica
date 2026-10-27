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

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.php");

    exit();
}

if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["administrador", "superior"], true)) {
    header("Location: ../index.php");

    exit();
}

require_once __DIR__ . "/../config/proceso_activo.php";
$procesoActivoId = idProcesoActivoBeca($conn);

$busqueda = trim($_GET["buscar"] ?? "");

$estadisticas = [
    "total" => 0,
    "revision" => 0,
    "beneficiados" => 0,
    "rechazados" => 0,
];

$sqlEstadisticas = "
    SELECT

        COUNT(*) AS total,

        SUM(
            estado = 'en_revision'
        ) AS revision,

        SUM(
            estado = 'beneficiado'
        ) AS beneficiados,

        SUM(
            estado = 'no_beneficiado'
        ) AS rechazados

    FROM postulaciones
    WHERE proceso_id = $procesoActivoId
";

$resultadoEstadisticas = $conn->query($sqlEstadisticas);

if ($resultadoEstadisticas) {
    $fila = $resultadoEstadisticas->fetch_assoc();

    $estadisticas["total"] = (int) ($fila["total"] ?? 0);

    $estadisticas["revision"] = (int) ($fila["revision"] ?? 0);

    $estadisticas["beneficiados"] = (int) ($fila["beneficiados"] ?? 0);

    $estadisticas["rechazados"] = (int) ($fila["rechazados"] ?? 0);
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
    $filaRendiciones = $resultadoEstadisticasRendiciones->fetch_assoc();

    $estadisticasRendiciones["total"] = (int) ($filaRendiciones["total"] ?? 0);

    $estadisticasRendiciones["revision"] = (int) ($filaRendiciones["revision"] ?? 0);

    $estadisticasRendiciones["aprobadas"] = (int) ($filaRendiciones["aprobadas"] ?? 0);

    $estadisticasRendiciones["rechazadas"] = (int) ($filaRendiciones["rechazadas"] ?? 0);
}

$porPaginaInicio = 25;
$condicionInicio = "p.proceso_id = ?";
$parametrosInicio = [$procesoActivoId];
$tiposInicio = "i";
if ($busqueda !== "") {
    $condicionInicio .=
        " AND (CONCAT_WS(' ', dp.nombres, dp.apellido_paterno, dp.apellido_materno) LIKE ? OR dp.rut LIKE ?)";
    $parametrosInicio[] = "%" . $busqueda . "%";
    $parametrosInicio[] = "%" . $busqueda . "%";
    $tiposInicio .= "ss";
}
$origenInicio =
    " FROM postulaciones p INNER JOIN datos_personales dp ON dp.postulacion_id=p.id INNER JOIN datos_educacionales de ON de.postulacion_id=p.id WHERE " .
    $condicionInicio;
$contarInicio = $conn->prepare("SELECT COUNT(*) total" . $origenInicio);
$contarInicio->bind_param($tiposInicio, ...$parametrosInicio);
$contarInicio->execute();
$totalInicio = (int) $contarInicio->get_result()->fetch_assoc()["total"];
$paginasInicio = max(1, (int) ceil($totalInicio / $porPaginaInicio));
$paginaInicio = min($paginasInicio, max(1, (int) ($_GET["pagina"] ?? 1)));
$offsetInicio = ($paginaInicio - 1) * $porPaginaInicio;
$stmt = $conn->prepare(
    "SELECT p.id,p.estado,p.fecha_postulacion,dp.nombres,dp.apellido_paterno,dp.apellido_materno,dp.rut,dp.correo_electronico,de.carrera,de.nombre_establecimiento" .
        $origenInicio .
        " ORDER BY p.fecha_postulacion DESC,p.id DESC LIMIT ? OFFSET ?",
);
$parametrosInicio[] = $porPaginaInicio;
$parametrosInicio[] = $offsetInicio;
$tiposInicio .= "ii";
$stmt->bind_param($tiposInicio, ...$parametrosInicio);
$stmt->execute();
$postulaciones = $stmt->get_result();
$urlPaginaInicio = static fn(int $pagina) => "inicio.php?" .
    http_build_query(["pagina" => $pagina, "buscar" => $busqueda]);

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? "", ENT_QUOTES, "UTF-8");
}

function textoEstado(string $estado): string
{
    return match ($estado) {
        "pendiente" => "Pendiente",

        "en_revision" => "En revisión",

        "beneficiado" => "Beneficiado",

        "no_beneficiado" => "No beneficiado",

        default => $estado,
    };
}

require_once __DIR__ . "/../vistas/superior/inicio.php";
