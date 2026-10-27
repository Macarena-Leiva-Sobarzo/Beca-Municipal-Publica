<?php
require_once __DIR__ . "/horario.php";

/** Muestra un aviso de contenido y conserva el contexto desde el que se abrió. */
function mostrarAvisoBecas(string $mensaje, ?int $codigo = null): never
{
    mostrarAvisoBecasInterno($mensaje, $codigo, false);
}

/** Muestra un aviso de acceso y termina la sesión al confirmar el mensaje. */
function mostrarAvisoAccesoBecas(string $mensaje, ?int $codigo = null): never
{
    mostrarAvisoBecasInterno($mensaje, $codigo, true);
}

/** @internal Renderiza los avisos sin mezclar el retorno del visor con el control de sesión. */
function mostrarAvisoBecasInterno(string $mensaje, ?int $codigo, bool $cerrarSesion): never
{
    $estado = $codigo ?? (int) http_response_code();
    if ($estado < 400 || $estado > 599) {
        $estado = 400;
    }
    http_response_code($estado);
    if (PHP_SAPI === "cli") {
        fwrite(STDERR, $mensaje . PHP_EOL);
        exit(1);
    }
    $tipo = strtolower((string) ($_SERVER["HTTP_ACCEPT"] ?? ""));
    if (
        str_contains($tipo, "application/json") ||
        strtolower((string) ($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "")) === "xmlhttprequest" ||
        isset($_POST["usuarios_ajax"]) ||
        in_array(
            (string) ($_POST["accion"] ?? ""),
            ["verificar_boleta", "verificar_comprobante_devolucion"],
            true,
        )
    ) {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(
            ["ok" => false, "error" => $mensaje, "mensaje" => $mensaje],
            JSON_UNESCAPED_UNICODE,
        );
        exit();
    }

    $raiz = str_replace("\\", "/", dirname(__DIR__));
    $archivo = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_FILENAME"] ?? ""));
    $ruta = (string) ($_SERVER["SCRIPT_NAME"] ?? "/index.php");
    $relativa = str_starts_with($archivo, $raiz . "/")
        ? substr($archivo, strlen($raiz) + 1)
        : basename($ruta);
    $base = str_ends_with($ruta, $relativa)
        ? substr($ruta, 0, -strlen($relativa))
        : rtrim(dirname($ruta), "/") . "/";
    $base = "/" . trim($base, "/");
    $inicioAviso = rtrim($base, "/") . "/index.php";
    $cssAviso = rtrim($base, "/") . "/css/avisos.css?v=20261022";
    $accionAviso = $cerrarSesion ? "cerrar_sesion" : "volver";
    $destinoAviso = $inicioAviso;

    if (!$cerrarSesion) {
        // En visores de documentos se conserva el detalle desde el que se abrió el archivo.
        $baseAplicacion = rtrim((string) dirname($base), "/");
        $candidatosVolver = [
            (string) ($_GET["volver"] ?? ""),
            (string) ($_SERVER["HTTP_REFERER"] ?? ""),
        ];
        foreach ($candidatosVolver as $referencia) {
            if ($referencia === "") {
                continue;
            }
            $partesReferencia = parse_url($referencia);
            if (!is_array($partesReferencia)) {
                continue;
            }
            $hostActual = strtolower((string) preg_replace(
                "/:\\d+$/",
                "",
                (string) ($_SERVER["HTTP_HOST"] ?? ""),
            ));
            $hostReferencia = strtolower((string) ($partesReferencia["host"] ?? ""));
            $rutaReferencia = (string) ($partesReferencia["path"] ?? "");
            $perteneceAplicacion = $baseAplicacion === "" || $baseAplicacion === "/"
                ? str_starts_with($rutaReferencia, "/")
                : $rutaReferencia === $baseAplicacion || str_starts_with(
                    $rutaReferencia,
                    $baseAplicacion . "/",
                );
            if (
                ($hostReferencia === "" || $hostReferencia === $hostActual) &&
                $rutaReferencia !== "" &&
                $perteneceAplicacion
            ) {
                $destinoAviso = $rutaReferencia;
                if (isset($partesReferencia["query"]) && $partesReferencia["query"] !== "") {
                    $destinoAviso .= "?" . $partesReferencia["query"];
                }
                break;
            }
        }
    } else {
        $destinoAviso = rtrim($base, "/") . "/auth/cerrar_sesion.php";
    }

    header("Content-Type: text/html; charset=UTF-8");
    header("Cache-Control: no-store");
    require __DIR__ . "/../vistas/aviso.php";
    exit();
}

