<?php
require_once __DIR__ . "/entorno.php";
/** Permite consultar los documentos de cualquiera de los dos portales que comparten la base. */
function rutaDocumentoCompartido(string $ruta, ?string $raiz = null): ?string
{
    $raiz ??= dirname(__DIR__);
    $ruta = str_replace("\\", "/", $ruta);
    if (!str_starts_with($ruta, "documentos/") || str_contains($ruta, "..")) {
        return null;
    }
    $otra = configuracionBecas(
        "BECAS_OTRO_PROYECTO_DIR",
        dirname($raiz) .
            "/" .
            (basename($raiz) === "FormularioBecas"
                ? "PostulacionBecaMunicipal"
                : "FormularioBecas"),
    );
    $resultado = null;
    foreach ([$raiz, $otra] as $proyecto) {
        $base = realpath($proyecto . "/documentos");
        $archivo = realpath($proyecto . "/" . $ruta);
        if (
            !$base ||
            !$archivo ||
            !is_file($archivo) ||
            is_link($proyecto . "/" . $ruta) ||
            !str_starts_with(strtolower($archivo), strtolower($base . DIRECTORY_SEPARATOR))
        ) {
            continue;
        }
        // Si ambos portales tienen la misma ruta con contenido distinto, no entregar un documento ambiguo.
        if ($resultado && hash_file("sha256", $resultado) !== hash_file("sha256", $archivo)) {
            return null;
        }
        $resultado = $archivo;
    }
    return $resultado;
}
