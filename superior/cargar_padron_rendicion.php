<?php

declare(strict_types=1);

require_once __DIR__ . "/../config/aviso.php";
require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../config/solo_rendicion.php";

if (empty($_SESSION["usuario_id"]) || ($_SESSION["rol"] ?? "") !== "superior") {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso reservado al superior.");
}

function volverPadron(string $mensaje, bool $correcto = false): never
{
    header("Location: rendiciones.php?" . ($correcto ? "ok=" : "error=") . rawurlencode($mensaje));
    exit();
}

function rutsDesdeTextoPadron(string $texto): array
{
    preg_match_all("/(?<![0-9])[0-9]{1,2}(?:\\.[0-9]{3}){2}-?[0-9Kk]|(?<![0-9])[0-9]{7,8}-?[0-9Kk](?![0-9A-Za-z])/u", $texto, $coincidencias);
    $ruts = [];
    foreach ($coincidencias[0] ?? [] as $rut) {
        $normalizado = rutSoloRendicionNormalizado($rut);
        if (rutSoloRendicionValido($normalizado)) {
            $ruts[$normalizado] = rutSoloRendicionFormateado($normalizado);
        }
    }
    return array_values($ruts);
}

function textoXlsxPadron(string $archivo): string
{
    if (!class_exists("ZipArchive")) {
        throw new RuntimeException("El servidor no tiene habilitada la extensión ZIP para leer archivos Excel.");
    }
    $zip = new ZipArchive();
    if ($zip->open($archivo) !== true) {
        throw new RuntimeException("No fue posible abrir el archivo Excel.");
    }
    try {
        $compartidos = [];
        $xmlCompartidos = $zip->getFromName("xl/sharedStrings.xml");
        if ($xmlCompartidos !== false) {
            $documento = new DOMDocument();
            if (@$documento->loadXML($xmlCompartidos, LIBXML_NONET)) {
                foreach ($documento->getElementsByTagName("si") as $item) {
                    $compartidos[] = $item->textContent;
                }
            }
        }
        $texto = "";
        for ($indice = 0; ; $indice++) {
            $xml = $zip->getFromName("xl/worksheets/sheet" . ($indice + 1) . ".xml");
            if ($xml === false) {
                break;
            }
            $documento = new DOMDocument();
            if (!@$documento->loadXML($xml, LIBXML_NONET)) {
                continue;
            }
            foreach ($documento->getElementsByTagName("c") as $celda) {
                if (!$celda instanceof DOMElement) {
                    continue;
                }
                $valor = "";
                $tipo = $celda->getAttribute("t");
                $nodoValor = $celda->getElementsByTagName("v")->item(0);
                if ($tipo === "s" && $nodoValor) {
                    $valor = $compartidos[(int) $nodoValor->textContent] ?? "";
                } elseif ($tipo === "inlineStr") {
                    $valor = $celda->textContent;
                } elseif ($nodoValor) {
                    $valor = $nodoValor->textContent;
                }
                $texto .= $valor . "\n";
            }
        }
        return $texto;
    } finally {
        $zip->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" ||
    !is_string($_POST["csrf"] ?? null) ||
    !hash_equals($_SESSION["csrf_padron_rendicion"] ?? "", $_POST["csrf"])) {
    http_response_code(403);
    mostrarAvisoBecas("Solicitud no válida.");
}

$procesoId = (int) ($_POST["proceso_id"] ?? 0);
$proceso = $conn->query("SELECT id,tipo_proceso,activo FROM procesos_beca WHERE id=$procesoId")->fetch_assoc();
if (!$proceso || !$proceso["activo"] || $proceso["tipo_proceso"] !== "solo_rendicion") {
    volverPadron("Selecciona un proceso activo de solo rendición.");
}

$archivo = $_FILES["padron"] ?? [];
if (($archivo["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo["tmp_name"] ?? "")) {
    volverPadron("Selecciona un archivo válido.");
}
if ((int) ($archivo["size"] ?? 0) <= 0 || (int) $archivo["size"] > 10 * 1024 * 1024) {
    volverPadron("El archivo debe pesar entre 1 byte y 10 MB.");
}

$extension = strtolower(pathinfo((string) $archivo["name"], PATHINFO_EXTENSION));
try {
    if ($extension === "csv") {
        $texto = (string) file_get_contents($archivo["tmp_name"]);
    } elseif ($extension === "xlsx") {
        $texto = textoXlsxPadron($archivo["tmp_name"]);
    } elseif ($extension === "pdf") {
        require_once __DIR__ . "/../vendor/autoload.php";
        $texto = (new Smalot\PdfParser\Parser())->parseFile($archivo["tmp_name"])->getText();
    } else {
        throw new RuntimeException("El padrón debe ser un archivo Excel (.xlsx o .csv) o PDF.");
    }
    $ruts = rutsDesdeTextoPadron($texto);
    if (!$ruts) {
        throw new RuntimeException("No se encontraron RUT chilenos válidos en el archivo.");
    }
    if (count($ruts) > 10000) {
        throw new RuntimeException("El padrón no puede superar 10.000 RUT.");
    }

    $conn->begin_transaction();
    $transaccionAbierta = true;

    $buscarUsuario = $conn->prepare("SELECT id,rol FROM usuarios WHERE REPLACE(REPLACE(REPLACE(UPPER(rut),'.',''),'-',''),' ','')=? LIMIT 1");
    $crearUsuario = $conn->prepare("INSERT INTO usuarios (rut,rol,activo) VALUES (?,'usuario',1)");
    // La clave primaria proceso_id + usuario_id impide duplicados. INSERT IGNORE
    // permite mantener los RUT ya cargados en importaciones anteriores.
    $agregar = $conn->prepare("INSERT IGNORE INTO obligados_rendicion (proceso_id,usuario_id) VALUES (?,?)");
    $incorporados = 0;
    $yaHabilitados = 0;
    $omitidos = 0;
    foreach ($ruts as $rut) {
        $normalizado = rutSoloRendicionNormalizado($rut);
        $buscarUsuario->bind_param("s", $normalizado);
        $buscarUsuario->execute();
        $usuario = $buscarUsuario->get_result()->fetch_assoc();
        if ($usuario && $usuario["rol"] !== "usuario") {
            $omitidos++;
            continue;
        }
        if ($usuario) {
            $usuarioId = (int) $usuario["id"];
        } else {
            $crearUsuario->bind_param("s", $rut);
            $crearUsuario->execute();
            $usuarioId = (int) $conn->insert_id;
        }
        $agregar->bind_param("ii", $procesoId, $usuarioId);
        $agregar->execute();
        if ($agregar->affected_rows === 1) {
            $incorporados++;
        } else {
            $yaHabilitados++;
        }
    }
    $buscarUsuario->close();
    $crearUsuario->close();
    $agregar->close();
    if (!$incorporados && !$yaHabilitados) {
        throw new RuntimeException("Ningún RUT pudo incorporarse al padrón.");
    }
    $conn->commit();
    $transaccionAbierta = false;
    volverPadron(
        "El archivo fue importado correctamente: $incorporados RUT agregados al padrón" .
            ($yaHabilitados ? ", $yaHabilitados ya estaban habilitados" : "") .
            ($omitidos ? ", $omitidos omitidos por pertenecer a cuentas administrativas" : "") .
            ".",
        true,
    );
} catch (Throwable $error) {
    if (!empty($transaccionAbierta)) {
        $conn->rollback();
    }
    error_log("Importación padrón rendición: " . $error->getMessage());
    volverPadron($error instanceof RuntimeException ? $error->getMessage() : "No fue posible importar el padrón.");
}
