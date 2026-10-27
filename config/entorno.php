<?php

declare(strict_types=1);

/** El hosting conserva su configuración privada y XAMPP usa la configuración de desarrollo. */
function configuracionBecas(string $clave, string $predeterminado = ""): string
{
    static $configuracion = null;
    if ($configuracion === null) {
        $archivo = __DIR__ . "/config.local.php";
        $configuracion = is_file($archivo) ? require $archivo : [];
        if (!is_array($configuracion)) {
            throw new RuntimeException("La configuración local debe devolver un arreglo.");
        }
        // No confiar en HTTP_HOST: cualquier visitante puede enviar ese encabezado.
        // El hosting Linux usa siempre producción. En Windows, solo acceso local o CLI.
        $entornoForzado = getenv("BECAS_ENV");
        $clienteLocal =
            PHP_SAPI === "cli" ||
            in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1", "::ffff:127.0.0.1"], true);
        $usarDesarrollo =
            $entornoForzado === "development" ||
            ($entornoForzado === false && PHP_OS_FAMILY === "Windows" && $clienteLocal);
        if ($usarDesarrollo) {
            $desarrollo = require __DIR__ . "/config.desarrollo.php";
            if (!is_array($desarrollo)) {
                throw new RuntimeException(
                    "La configuración de desarrollo debe devolver un arreglo.",
                );
            }
            $configuracion = array_replace($configuracion, $desarrollo);
        }
    }
    $valor = getenv($clave);
    return $valor !== false ? $valor : (string) ($configuracion[$clave] ?? $predeterminado);
}

function esProduccionBecas(): bool
{
    return configuracionBecas("BECAS_ENV", "production") === "production";
}
