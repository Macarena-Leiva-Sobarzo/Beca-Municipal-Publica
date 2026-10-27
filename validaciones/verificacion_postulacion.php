<?php
// La verificación pertenece a la sesión, al contenido del PDF y a los datos comparados.
function huellaDocumentoPostulacion(string $campo, string $ruta, array $datos): string
{
    $campos = [
        "rut",
        "nombres",
        "apellido_paterno",
        "apellido_materno",
        "carrera",
        "nombre_establecimiento",
        "promedio_ultimo_semestre",
        "tiene_gratuidad",
    ];
    $contexto = [(string) ($_SESSION["usuario_id"] ?? ""), $campo, "pdf-contenido-v1"];
    foreach ($campos as $clave) {
        $contexto[] = trim((string) ($datos[$clave] ?? ""));
    }
    $hash = hash_file("sha256", $ruta);
    if ($hash === false) {
        throw new RuntimeException("No fue posible leer el documento.");
    }
    $contexto[] = $hash;
    return hash("sha256", json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

function guardarVerificacionPostulacion(
    string $campo,
    string $ruta,
    array $datos,
    array $resultado,
): void {
    $_SESSION["verificacion_postulacion"][$campo] = [
        "huella" => huellaDocumentoPostulacion($campo, $ruta, $datos),
        "fecha" => time(),
        "resultado" => $resultado,
    ];
}

function recuperarVerificacionPostulacion(string $campo, string $ruta, array $datos): array
{
    $guardado = $_SESSION["verificacion_postulacion"][$campo] ?? [];
    if (
        ($guardado["fecha"] ?? 0) >= time() - 3600 &&
        hash_equals($guardado["huella"] ?? "", huellaDocumentoPostulacion($campo, $ruta, $datos)) &&
        ($guardado["resultado"]["estado"] ?? "") === "correcto"
    ) {
        return $guardado["resultado"];
    }
    return [
        "estado" => "pendiente",
        "mensaje" => "Debes verificar nuevamente este documento antes de enviar la postulación.",
    ];
}
