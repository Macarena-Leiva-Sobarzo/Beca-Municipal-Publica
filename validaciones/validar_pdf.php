<?php

/** Validación común de cargas PDF: extensión, tamaño, formato y contenido. La revisión documental administrativa sigue siendo independiente de esta comprobación técnica. */

require_once dirname(__DIR__) . "/vendor/autoload.php";

/** Deshace los filtros de filas PNG usados por imágenes PDF de 8 bits. */
function decodificarFilasPDF(string $contenido, int $ancho, int $alto, int $componentes): ?string
{
    $filaBytes = $ancho * $componentes;
    if ($filaBytes <= 0 || $alto <= 0 || strlen($contenido) !== ($filaBytes + 1) * $alto) {
        return null;
    }
    $anterior = array_fill(0, $filaBytes, 0);
    $salida = "";
    $posicion = 0;
    for ($y = 0; $y < $alto; $y++) {
        $filtro = ord($contenido[$posicion++]);
        if ($filtro > 4) {
            return null;
        }
        $fila = [];
        for ($x = 0; $x < $filaBytes; $x++) {
            $izquierda = $x >= $componentes ? $fila[$x - $componentes] : 0;
            $arriba = $anterior[$x];
            $diagonal = $x >= $componentes ? $anterior[$x - $componentes] : 0;
            $prediccion = 0;
            if ($filtro === 1) {
                $prediccion = $izquierda;
            }
            if ($filtro === 2) {
                $prediccion = $arriba;
            }
            if ($filtro === 3) {
                $prediccion = intdiv($izquierda + $arriba, 2);
            }
            if ($filtro === 4) {
                $p = $izquierda + $arriba - $diagonal;
                $a = abs($p - $izquierda);
                $b = abs($p - $arriba);
                $c = abs($p - $diagonal);
                $prediccion = $a <= $b && $a <= $c ? $izquierda : ($b <= $c ? $arriba : $diagonal);
            }
            $fila[$x] = (ord($contenido[$posicion++]) + $prediccion) & 255;
            $salida .= chr($fila[$x]);
        }
        $anterior = $fila;
    }
    return $salida;
}

/** Comprueba contenido sin OCR ni programas externos. */
function pdfTieneContenido(Smalot\PdfParser\Document $pdf): bool
{
    foreach ($pdf->getPages() as $pagina) {
        if (preg_match("/[^\s\p{Z}\p{C}]/u", $pagina->getText())) {
            return true;
        }
    }
    // Las imágenes pueden estar en los recursos heredados de la página.
    foreach ($pdf->getObjectsByType("XObject", "Image") as $imagen) {
        $contenido = (string) $imagen->getContent();
        if ($contenido === "") {
            continue;
        }
        $detalle = $imagen->getDetails();
        $pixeles = (int) ($detalle["Width"] ?? 0) * (int) ($detalle["Height"] ?? 0);
        if ($pixeles > 12000000) {
            throw new RuntimeException(
                "El PDF contiene una imagen demasiado grande para verificarla. Reduce su resolución y vuelve a adjuntarlo.",
            );
        }
        $bitmap = function_exists("imagecreatefromstring")
            ? @imagecreatefromstring($contenido)
            : false;
        if ($bitmap !== false) {
            try {
                $ancho = imagesx($bitmap);
                $alto = imagesy($bitmap);
                for ($y = 0; $y < $alto; $y++) {
                    for ($x = 0; $x < $ancho; $x++) {
                        $color = imagecolorsforindex($bitmap, imagecolorat($bitmap, $x, $y));
                        if (
                            $color["alpha"] < 127 &&
                            min($color["red"], $color["green"], $color["blue"]) < 245
                        ) {
                            return true;
                        }
                    }
                }
            } finally {
                imagedestroy($bitmap);
            }
            continue;
        }
        // Los flujos de píxeles RGB/grises descomprimidos no tienen cabecera de imagen.
        $componentes = ($detalle["ColorSpace"] ?? "") === "DeviceRGB" ? 3 : 1;
        $predictor = (int) ($detalle["DecodeParms"]["Predictor"] ?? 1);
        if ($predictor >= 10 && $predictor <= 15 && ($detalle["BitsPerComponent"] ?? 0) == 8) {
            $contenido =
                decodificarFilasPDF(
                    $contenido,
                    (int) ($detalle["Width"] ?? 0),
                    (int) ($detalle["Height"] ?? 0),
                    $componentes,
                ) ?? "";
        }
        $longitud = (int) ($detalle["Width"] ?? 0) * (int) ($detalle["Height"] ?? 0) * $componentes;
        if (
            ($detalle["BitsPerComponent"] ?? 0) == 8 &&
            in_array($detalle["ColorSpace"] ?? "", ["DeviceRGB", "DeviceGray"], true) &&
            $longitud === strlen($contenido)
        ) {
            if (preg_match('/[\x00-\xF4]/', $contenido)) {
                return true;
            }
            continue;
        }
        // No confundir una imagen en otro formato con un documento vacío.
        return true;
    }
    return false;
}

/** Valida el archivo, sus páginas y la presencia de contenido. */
function validarArchivoPDF(string $ruta, string $nombreOriginal, int $maximo): array
{
    if (!is_file($ruta) || !is_readable($ruta)) {
        throw new RuntimeException("El archivo no existe o no puede ser leído.");
    }
    $tamano = filesize($ruta);
    if ($tamano === false || $tamano <= 0) {
        throw new RuntimeException("El archivo está vacío.");
    }
    if ($tamano > $maximo) {
        throw new RuntimeException(
            "El archivo supera el máximo permitido de " . $maximo / 1024 / 1024 . " MB.",
        );
    }
    if (strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION)) !== "pdf") {
        throw new RuntimeException("Solo se permiten archivos con extensión PDF.");
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
    if ($mime !== "application/pdf" || file_get_contents($ruta, false, null, 0, 5) !== "%PDF-") {
        throw new RuntimeException("El archivo no corresponde a un PDF válido.");
    }
    try {
        $pdf = (new Smalot\PdfParser\Parser())->parseFile($ruta);
        if (count($pdf->getPages()) === 0) {
            throw new RuntimeException("PDF sin páginas.");
        }
    } catch (Throwable $e) {
        throw new RuntimeException(
            "No se pudo abrir el PDF. Puede estar dañado, protegido o no contener páginas.",
        );
    }
    if (!pdfTieneContenido($pdf)) {
        throw new RuntimeException(
            "El PDF está vacío o solo contiene páginas en blanco. Adjunta un documento con contenido.",
        );
    }
    return ["mime" => $mime, "extension" => "pdf", "tamano" => $tamano];
}
