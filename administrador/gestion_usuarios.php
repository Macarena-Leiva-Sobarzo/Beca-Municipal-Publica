<?php
require_once __DIR__ . "/../config/aviso.php";
require_once __DIR__ . "/../config/rut.php";
require_once __DIR__ . "/../config/solo_rendicion.php";
// Gestión de cuentas compartida por Configuración y la ruta de usuarios.
if (($_SESSION["rol"] ?? "") !== "superior" || empty($_SESSION["usuario_id"])) {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso reservado al superior.");
}
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE id=? AND rol='superior' AND activo=1");
$stmt->bind_param("i", $_SESSION["usuario_id"]);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    http_response_code(403);
    mostrarAvisoAccesoBecas("La cuenta no tiene permisos vigentes.");
}
$stmt->close();
$_SESSION["csrf_usuarios"] ??= bin2hex(random_bytes(32));
$procesoSoloRendicionUsuarios = procesoSoloRendicionActivo($conn) !== null;
function escUsuario($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, "UTF-8");
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["usuarios_accion"])) {
    $exitoUsuarios = false;
    try {
        if (
            !is_string($_POST["csrf"] ?? null) ||
            !hash_equals($_SESSION["csrf_usuarios"], $_POST["csrf"])
        ) {
            throw new RuntimeException("Recarga la página e inténtalo nuevamente.");
        }
        $accion = $_POST["usuarios_accion"];
        $id = (int) ($_POST["id"] ?? 0);
        if (!in_array($accion, ["crear", "editar", "estado", "eliminar"], true)) {
            throw new RuntimeException("Operación no válida.");
        }
        if ($accion !== "crear") {
            $stmt = $conn->prepare("SELECT id,rol FROM usuarios WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $actual = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$actual) {
                throw new RuntimeException("La cuenta ya no existe.");
            }
        }
        if (
            in_array($accion, ["estado", "eliminar"], true) &&
            $id === (int) $_SESSION["usuario_id"]
        ) {
            throw new RuntimeException("No puedes desactivar ni eliminar tu propia cuenta.");
        }
        if ($accion === "estado") {
            $activo = ($_POST["activo"] ?? "") === "1" ? 1 : 0;
            $stmt = $conn->prepare("UPDATE usuarios SET activo=? WHERE id=?");
            $stmt->bind_param("ii", $activo, $id);
            $stmt->execute();
            $cambios = $stmt->affected_rows;
            $stmt->close();
            $aviso = $cambios
                ? ($activo
                    ? "Cuenta activada correctamente."
                    : "Cuenta desactivada correctamente.")
                : "La cuenta ya tenía ese estado; no hubo cambios.";
        } elseif ($accion === "eliminar") {
            require_once __DIR__ . "/eliminar_cuenta.php";
            $pendientes = eliminarCuentaCompleta($conn, $id, dirname(__DIR__));
            $aviso = $pendientes
                ? "Se eliminaron la cuenta y sus registros, pero no se pudieron borrar algunos archivos. Solicita la revisión de permisos al administrador del servidor."
                : "Cuenta eliminada junto con sus postulaciones, rendiciones, datos y documentos.";
        } else {
            $correo = trim((string) ($_POST["correo"] ?? ""));
            $rut = normalizarRutBecas((string) ($_POST["rut"] ?? ""));
            $rol = (string) ($_POST["rol"] ?? "");
            $clave = (string) ($_POST["password"] ?? "");
            $usuarioSinClavePermitido =
                $rol === "usuario" && $procesoSoloRendicionUsuarios;
            if (!in_array($rol, ["usuario", "administrador", "superior"], true)) {
                throw new RuntimeException("Selecciona un rol válido.");
            }
            if ($id === (int) $_SESSION["usuario_id"] && $rol !== "superior") {
                throw new RuntimeException("No puedes quitarte el rol superior.");
            }
            if ($correo === "" && $rut === "") {
                throw new RuntimeException("Ingresa un correo o un RUT para iniciar sesión.");
            }
            if ($usuarioSinClavePermitido && $rut === "") {
                throw new RuntimeException("Ingresa el RUT del usuario para habilitar su acceso por RUT.");
            }
            if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("El correo no es válido.");
            }
            if ($rut !== "") {
                if (!preg_match('/^[0-9]{7,8}[0-9K]$/', $rut)) {
                    throw new RuntimeException("El RUT no es válido.");
                }
                $cuerpo = substr($rut, 0, -1);
                $suma = 0;
                $factor = 2;
                foreach (str_split(strrev($cuerpo)) as $digito) {
                    $suma += (int) $digito * $factor;
                    $factor = $factor === 7 ? 2 : $factor + 1;
                }
                $resto = 11 - ($suma % 11);
                $dv = $resto === 11 ? "0" : ($resto === 10 ? "K" : (string) $resto);
                if (substr($rut, -1) !== $dv) {
                    throw new RuntimeException("El dígito verificador del RUT no es válido.");
                }
                $stmt = $conn->prepare(
                    "SELECT id FROM usuarios WHERE REPLACE(REPLACE(UPPER(rut),'.',''),'-','')=? AND id<>?",
                );
                $stmt->bind_param("si", $rut, $id);
                $stmt->execute();
                $duplicado = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($duplicado) {
                    throw new RuntimeException("El RUT ya está registrado.");
                }
                $rut = preg_replace("/\B(?=(\d{3})+(?!\d))/", ".", $cuerpo) . "-" . $dv;
            }
            if ($accion === "crear" && !$usuarioSinClavePermitido && $clave === "") {
                throw new RuntimeException("La contraseña es obligatoria para esta cuenta.");
            }
            if ($clave !== "" && strlen($clave) < 8) {
                throw new RuntimeException("La contraseña debe tener al menos ocho caracteres.");
            }
            $correo = $correo === "" ? null : $correo;
            $rut = $rut === "" ? null : $rut;
            if ($accion === "crear") {
                $hash = $clave === "" ? null : password_hash($clave, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "INSERT INTO usuarios(correo,rut,rol,password) VALUES(?,?,?,?)",
                );
                $stmt->bind_param("ssss", $correo, $rut, $rol, $hash);
            } elseif ($clave !== "") {
                $hash = password_hash($clave, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "UPDATE usuarios SET correo=?,rut=?,rol=?,password=? WHERE id=?",
                );
                $stmt->bind_param("ssssi", $correo, $rut, $rol, $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET correo=?,rut=?,rol=? WHERE id=?");
                $stmt->bind_param("sssi", $correo, $rut, $rol, $id);
            }
            $stmt->execute();
            $cambios = $stmt->affected_rows;
            $nuevoUsuarioId = $accion === "crear" ? (int) $conn->insert_id : 0;
            $stmt->close();
            if ($accion === "crear" && $usuarioSinClavePermitido && $nuevoUsuarioId > 0) {
                $procesoSolo = procesoSoloRendicionActivo($conn);
                if ($procesoSolo) {
                    $padron = $conn->prepare(
                        "INSERT IGNORE INTO obligados_rendicion (proceso_id,usuario_id) VALUES (?,?)",
                    );
                    $procesoSoloId = (int) $procesoSolo["id"];
                    $padron->bind_param("ii", $procesoSoloId, $nuevoUsuarioId);
                    $padron->execute();
                    $padron->close();
                }
            }
            $aviso =
                $accion === "crear"
                    ? "Usuario creado correctamente."
                    : ($cambios
                        ? "Usuario actualizado correctamente."
                        : "No hubo cambios: los datos ingresados son iguales a los actuales.");
            if ($id === (int) $_SESSION["usuario_id"]) {
                $_SESSION["correo"] = $correo;
            }
        }
        $exitoUsuarios = true;
    } catch (mysqli_sql_exception $e) {
        $aviso =
            (int) $e->getCode() === 1062
                ? "El correo o el RUT ya está registrado."
                : "No se pudo guardar la operación. Inténtalo nuevamente.";
    } catch (RuntimeException $e) {
        $aviso = $e->getMessage();
    }
    if (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "XMLHttpRequest") {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["ok" => $exitoUsuarios, "mensaje" => $aviso], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $_SESSION["aviso_usuarios"] = $aviso;
    header("Location: configuracion.php#lista-usuarios");
    exit();
}
$avisoUsuarios = $_SESSION["aviso_usuarios"] ?? "";
unset($_SESSION["aviso_usuarios"]);
$editarUsuario = null;
$editarId = (int) ($_GET["editar_usuario"] ?? 0);
if ($editarId) {
    $stmt = $conn->prepare("SELECT id,correo,rut,rol,activo FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $editarId);
    $stmt->execute();
    $editarUsuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$buscarUsuario = trim((string) ($_GET["buscar_usuario"] ?? ""));
$rolFiltro = (string) ($_GET["rol_usuario"] ?? "");
$estadoFiltro = (string) ($_GET["estado_usuario"] ?? "");
if (!in_array($rolFiltro, ["", "usuario", "administrador", "superior"], true)) {
    $rolFiltro = "";
}
if (!in_array($estadoFiltro, ["", "0", "1"], true)) {
    $estadoFiltro = "";
}
$patron = "%" . $buscarUsuario . "%";
$rutPatron = "%" . strtoupper(str_replace([".", "-", " "], "", $buscarUsuario)) . "%";
// Al abrir el listado, se priorizan las cuentas de gestión. Una búsqueda o un rol
// seleccionado permite consultar también las cuentas con rol usuario.
$soloCuentasGestion = $buscarUsuario === "" && $rolFiltro === "";
$condicionUsuarios =
    " WHERE (COALESCE(correo,'') LIKE ? OR REPLACE(REPLACE(UPPER(COALESCE(rut,'')),'.',''),'-','') LIKE ?) AND (?='' OR rol=?) AND (?='' OR activo=?)";
if ($soloCuentasGestion) {
    $condicionUsuarios .= " AND rol IN ('administrador','superior')";
}
$stmt = $conn->prepare(
    "SELECT id,correo,rut,rol,activo FROM usuarios" . $condicionUsuarios . " ORDER BY id DESC",
);
$stmt->bind_param(
    "ssssss",
    $patron,
    $rutPatron,
    $rolFiltro,
    $rolFiltro,
    $estadoFiltro,
    $estadoFiltro,
);
$stmt->execute();
$listaUsuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
