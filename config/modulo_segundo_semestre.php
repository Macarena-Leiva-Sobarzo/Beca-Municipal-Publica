<?php
require_once __DIR__ . "/aviso.php";
require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/proceso_activo.php";
require_once __DIR__ . "/reglas_beca.php";
require_once __DIR__ . "/documentos_compartidos.php";
require_once __DIR__ . "/solo_rendicion.php";
$rolSegundo = $_SESSION["rol"] ?? "";
$usuarioSegundo = (int) ($_SESSION["usuario_id"] ?? 0);
$carpetaSegundo = basename(dirname($_SERVER["SCRIPT_FILENAME"]));
if (
    !$usuarioSegundo ||
    !in_array($rolSegundo, ["usuario", "administrador", "superior"], true) ||
    $carpetaSegundo !== $rolSegundo
) {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Acceso no autorizado.");
}
$permisoSegundo = $conn->prepare("SELECT id FROM usuarios WHERE id=? AND activo=1 AND rol=?");
$permisoSegundo->bind_param("is", $usuarioSegundo, $rolSegundo);
$permisoSegundo->execute();
if (!$permisoSegundo->get_result()->fetch_assoc()) {
    http_response_code(403);
    mostrarAvisoAccesoBecas("Cuenta no disponible.");
}
$procesoSegundoId = idProcesoActivoBeca($conn);
$procesoSegundo = $conn
    ->query("SELECT * FROM procesos_beca WHERE id=$procesoSegundoId")
    ->fetch_assoc();
$esUsuarioSegundo = $rolSegundo === "usuario";
$fechaSegundo = fechaBeca($procesoSegundo["fecha_inicio_certificado_segundo"] ?? null);
$ahoraSegundo = new DateTimeImmutable("now", new DateTimeZone("America/Santiago"));
$abiertoSegundo = $fechaSegundo && $ahoraSegundo >= $fechaSegundo;
$_SESSION["csrf_segundo"] ??= bin2hex(random_bytes(32));
$mensajeSegundo = (string) ($_SESSION["exito_segundo_semestre"] ?? "");
unset($_SESSION["exito_segundo_semestre"]);
$errorSegundo = "";
$postulacionSegundo = null;
if ($esUsuarioSegundo) {
    $consultaSegundo = $conn->prepare(
        "SELECT p.*, pb.fecha_resultados FROM postulaciones p JOIN procesos_beca pb ON pb.id=p.proceso_id WHERE p.usuario_id=? AND p.proceso_id=? LIMIT 1",
    );
    $consultaSegundo->bind_param("ii", $usuarioSegundo, $procesoSegundoId);
    $consultaSegundo->execute();
    $postulacionSegundo = $consultaSegundo->get_result()->fetch_assoc();
}
$habilitadoSegundo =
    $esUsuarioSegundo &&
    (
        ($postulacionSegundo &&
            $postulacionSegundo["estado"] === "beneficiado" &&
            resultadoBecaPublicado($postulacionSegundo)) ||
        (($procesoSegundo["tipo_proceso"] ?? "") === "solo_rendicion" &&
            usuarioHabilitadoSoloRendicion($conn, $procesoSegundoId, $usuarioSegundo))
    );

if (isset($_GET["documento"])) {
    $documentoSegundoId = (int) $_GET["documento"];
    $consultaSegundo = $conn->prepare(
        "SELECT c.*, COALESCE(c.acceso_rut_id,p.usuario_id) usuario_id FROM certificados_segundo_semestre c LEFT JOIN postulaciones p ON p.id=c.postulacion_id WHERE c.id=? AND COALESCE(c.proceso_id,p.proceso_id)=?",
    );
    $consultaSegundo->bind_param("ii", $documentoSegundoId, $procesoSegundoId);
    $consultaSegundo->execute();
    $archivoSegundo = $consultaSegundo->get_result()->fetch_assoc();
    if (
        !$archivoSegundo ||
        ($esUsuarioSegundo && (int) $archivoSegundo["usuario_id"] !== $usuarioSegundo)
    ) {
        http_response_code(404);
        mostrarAvisoBecas("Documento no disponible.");
    }
    $rutaSegundo = rutaDocumentoCompartido($archivoSegundo["ruta_archivo"]);
    if (!$rutaSegundo) {
        http_response_code(404);
        mostrarAvisoBecas("Documento no disponible.");
    }
    header("Content-Type: application/pdf");
    header(
        "Content-Disposition: " .
            (isset($_GET["descargar"]) ? "attachment" : "inline") .
            '; filename="certificado-segundo-semestre.pdf"',
    );
    header("X-Content-Type-Options: nosniff");
    header("Cache-Control: private, no-store");
    readfile($rutaSegundo);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $archivoNuevoSegundo = null;
    $transaccionSegundo = false;
    try {
        if (
            !is_string($_POST["csrf"] ?? null) ||
            !hash_equals($_SESSION["csrf_segundo"], $_POST["csrf"])
        ) {
            throw new RuntimeException("La sesión del formulario expiró. Recarga la página.");
        }
        if ((int) ($_POST["proceso_id"] ?? 0) !== $procesoSegundoId || !$procesoSegundoId) {
            throw new RuntimeException(
                "El proceso activo cambió. Recarga la página antes de guardar.",
            );
        }
        $accionSegundo = $_POST["accion"] ?? "";
        if ($accionSegundo === "configurar" && $rolSegundo === "superior") {
            $fechaEntrada = str_replace("T", " ", trim((string) ($_POST["fecha_inicio"] ?? "")));
            if (strlen($fechaEntrada) === 16) {
                $fechaEntrada .= ":00";
            }
            if (!$procesoSegundo || !fechaBeca($fechaEntrada)) {
                throw new RuntimeException(
                    "Selecciona un proceso activo y una fecha de apertura válida.",
                );
            }
            $consultaSegundo = $conn->prepare(
                "UPDATE procesos_beca SET fecha_inicio_certificado_segundo=? WHERE id=? AND activo=1",
            );
            $consultaSegundo->bind_param("si", $fechaEntrada, $procesoSegundoId);
            $consultaSegundo->execute();
        } elseif ($accionSegundo === "subir" && $esUsuarioSegundo) {
            if (!$habilitadoSegundo || !$abiertoSegundo) {
                throw new RuntimeException(
                    "La carga del certificado no está habilitada para tu postulación.",
                );
            }
            $pdfSegundo = $_FILES["certificado"] ?? [];
            if (
                ($pdfSegundo["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
                !is_uploaded_file($pdfSegundo["tmp_name"] ?? "")
            ) {
                throw new RuntimeException("Adjunta el certificado en PDF.");
            }
            require_once dirname(__DIR__) . "/validaciones/validar_pdf.php";
            validarArchivoPDF($pdfSegundo["tmp_name"], $pdfSegundo["name"], 5 * 1024 * 1024);
            $postulacionSegundoId = (int) ($postulacionSegundo["id"] ?? 0);
            $conn->begin_transaction();
            $transaccionSegundo = true;
            $bloqueoSegundo = $conn->query(
                "SELECT id FROM postulaciones WHERE id=$postulacionSegundoId FOR UPDATE",
            );
            $criterioCertificadoSegundo = $postulacionSegundoId
                ? "postulacion_id=$postulacionSegundoId"
                : "proceso_id=$procesoSegundoId AND acceso_rut_id=$usuarioSegundo";
            $anteriorSegundo = $conn
                ->query("SELECT * FROM certificados_segundo_semestre WHERE $criterioCertificadoSegundo FOR UPDATE")
                ->fetch_assoc();
            if ($anteriorSegundo && $anteriorSegundo["estado"] !== "rechazado") {
                throw new RuntimeException(
                    "Tu certificado ya está pendiente de revisión o aceptado.",
                );
            }
            $directorioSegundo =
                dirname(__DIR__) . "/documentos/segundo_semestre/" . ($postulacionSegundoId ?: "rut-" . $usuarioSegundo);
            if (
                !is_dir($directorioSegundo) &&
                !mkdir($directorioSegundo, 0755, true) &&
                !is_dir($directorioSegundo)
            ) {
                throw new RuntimeException("No fue posible guardar el certificado.");
            }
            $nombreSegundo = bin2hex(random_bytes(16)) . ".pdf";
            $archivoNuevoSegundo = $directorioSegundo . "/" . $nombreSegundo;
            if (!move_uploaded_file($pdfSegundo["tmp_name"], $archivoNuevoSegundo)) {
                throw new RuntimeException("No fue posible guardar el certificado.");
            }
            $rutaSegundo =
                "documentos/segundo_semestre/" . ($postulacionSegundoId ?: "rut-" . $usuarioSegundo) . "/" . $nombreSegundo;
            $originalSegundo = basename($pdfSegundo["name"]);
            if ($postulacionSegundoId) {
                $consultaSegundo = $conn->prepare(
                    "INSERT INTO certificados_segundo_semestre (postulacion_id,acceso_rut_id,proceso_id,nombre_original,ruta_archivo,fecha_subida)
                    VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE nombre_original=VALUES(nombre_original),ruta_archivo=VALUES(ruta_archivo),
                    estado='pendiente',observacion=NULL,fecha_subida=NOW(),fecha_revision=NULL,revisor_id=NULL",
                );
                $consultaSegundo->bind_param("iiiss", $postulacionSegundoId, $usuarioSegundo, $procesoSegundoId, $originalSegundo, $rutaSegundo);
            } else {
                $consultaSegundo = $conn->prepare(
                    "INSERT INTO certificados_segundo_semestre (postulacion_id,acceso_rut_id,proceso_id,nombre_original,ruta_archivo,fecha_subida)
                    VALUES (NULL,?,?,?, ?,NOW()) ON DUPLICATE KEY UPDATE nombre_original=VALUES(nombre_original),ruta_archivo=VALUES(ruta_archivo),
                    estado='pendiente',observacion=NULL,fecha_subida=NOW(),fecha_revision=NULL,revisor_id=NULL",
                );
                $consultaSegundo->bind_param("iiss", $usuarioSegundo, $procesoSegundoId, $originalSegundo, $rutaSegundo);
            }
            $consultaSegundo->execute();
            $conn->commit();
            $transaccionSegundo = false;
        } elseif ($accionSegundo === "revisar" && !$esUsuarioSegundo) {
            $estadoSegundo = $_POST["estado"] ?? "";
            $observacionSegundo = trim((string) ($_POST["observacion"] ?? ""));
            if (!in_array($estadoSegundo, ["validado", "rechazado"], true)) {
                throw new RuntimeException("Selecciona Aceptado o Rechazado.");
            }
            if ($estadoSegundo === "rechazado" && $observacionSegundo === "") {
                throw new RuntimeException("Indica el motivo del rechazo.");
            }
            if (mb_strlen($observacionSegundo) > 2000) {
                throw new RuntimeException("La observación no puede superar 2000 caracteres.");
            }
            $certificadoSegundoId = (int) ($_POST["id"] ?? 0);
            $consultaSegundo = $conn->prepare(
                "UPDATE certificados_segundo_semestre c LEFT JOIN postulaciones p ON p.id=c.postulacion_id 
                SET c.estado=?,c.observacion=?,c.fecha_revision=NOW(),c.revisor_id=? WHERE c.id=? AND COALESCE(c.proceso_id,p.proceso_id)=?",
            );
            $consultaSegundo->bind_param(
                "ssiii",
                $estadoSegundo,
                $observacionSegundo,
                $usuarioSegundo,
                $certificadoSegundoId,
                $procesoSegundoId,
            );
            $consultaSegundo->execute();
            if (!$consultaSegundo->affected_rows) {
                $comprobarSegundo = $conn->prepare(
                    "SELECT c.id FROM certificados_segundo_semestre c LEFT JOIN postulaciones p ON p.id=c.postulacion_id WHERE c.id=? AND COALESCE(c.proceso_id,p.proceso_id)=?",
                );
                $comprobarSegundo->bind_param("ii", $certificadoSegundoId, $procesoSegundoId);
                $comprobarSegundo->execute();
                if (!$comprobarSegundo->get_result()->fetch_assoc()) {
                    throw new RuntimeException(
                        "El certificado no pertenece al proceso activo. Recarga la página.",
                    );
                }
            }
        } else {
            throw new RuntimeException("Operación no permitida.");
        }
        if ($accionSegundo === "configurar") {
            $_SESSION["admin_exito"] =
                "La fecha de apertura para el certificado de segundo semestre fue guardada correctamente.";
            header("Location: configuracion.php?proceso_id=" . $procesoSegundoId);
        } else {
            $paginaRetornoSegundo = max(1, (int) ($_POST["pagina"] ?? 1));
            if (!$esUsuarioSegundo) {
                $_SESSION["exito_segundo_semestre"] = "Los cambios se guardaron correctamente.";
            }
            header(
                "Location: segundo_semestre.php?" .
                    http_build_query([
                        "pagina" => $paginaRetornoSegundo,
                        "buscar" => trim((string) ($_POST["buscar"] ?? "")),
                    ]),
            );
        }
        exit();
    } catch (Throwable $excepcionSegundo) {
        if ($transaccionSegundo) {
            $conn->rollback();
        }
        if ($archivoNuevoSegundo && is_file($archivoNuevoSegundo)) {
            unlink($archivoNuevoSegundo);
        }
        $errorSegundo =
            $excepcionSegundo instanceof RuntimeException
                ? $excepcionSegundo->getMessage()
                : "No fue posible guardar los cambios. Inténtalo nuevamente.";
        error_log("Segundo semestre: " . $excepcionSegundo->getMessage());
    }
}
$buscarSegundo = trim((string) ($_GET["buscar"] ?? ($_POST["buscar"] ?? "")));
$urlPaginaSegundo = static fn(int $pagina) => "segundo_semestre.php?" .
    http_build_query(["pagina" => $pagina, "buscar" => $buscarSegundo]);
$certificadoSegundo = null;
$listadoSegundo = [];
$pendientesSegundo = [];
if ($postulacionSegundo) {
    $postulacionSegundoId = (int) $postulacionSegundo["id"];
    $certificadoSegundo = $conn
        ->query(
            "SELECT * FROM certificados_segundo_semestre WHERE postulacion_id=$postulacionSegundoId",
        )
        ->fetch_assoc();
} elseif ($esUsuarioSegundo && ($procesoSegundo["tipo_proceso"] ?? "") === "solo_rendicion") {
    $consultaSegundo = $conn->prepare(
        "SELECT * FROM certificados_segundo_semestre WHERE proceso_id=? AND acceso_rut_id=? LIMIT 1",
    );
    $consultaSegundo->bind_param("ii", $procesoSegundoId, $usuarioSegundo);
    $consultaSegundo->execute();
    $certificadoSegundo = $consultaSegundo->get_result()->fetch_assoc();
    $consultaSegundo->close();
} elseif (!$esUsuarioSegundo) {
    $revisorActualSegundo =
        $conn
            ->query(
                "SELECT TRIM(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)) nombre FROM usuarios WHERE id=$usuarioSegundo",
            )
            ->fetch_assoc()["nombre"] ?? "";
    if (($procesoSegundo["tipo_proceso"] ?? "") === "solo_rendicion") {
        $estadisticasSegundo = $conn
            ->query(
                "SELECT COUNT(*) postulantes, COUNT(c.id) recibidos, SUM(c.id IS NULL) pendientes FROM usuarios u LEFT JOIN certificados_segundo_semestre c ON c.acceso_rut_id=u.id AND c.proceso_id=$procesoSegundoId WHERE u.rol='usuario' AND u.activo=1 AND u.rut IS NOT NULL",
            )
            ->fetch_assoc();
    } else {
        $estadisticasSegundo = $conn
            ->query(
                "SELECT
        COUNT(*) postulantes,
        COUNT(c.id) recibidos,
        SUM(c.id IS NULL) pendientes
        FROM postulaciones p
        LEFT JOIN certificados_segundo_semestre c ON c.postulacion_id=p.id AND COALESCE(c.proceso_id,p.proceso_id)=$procesoSegundoId
        WHERE p.proceso_id=$procesoSegundoId AND p.estado='beneficiado'",
            )
            ->fetch_assoc();
    }

    if (($procesoSegundo["tipo_proceso"] ?? "") === "solo_rendicion") {
        $consultaPendientesSegundo = $conn->prepare(
            "SELECT
                u.id AS estudiante_id,
                u.rut,
                TRIM(CONCAT_WS(' ', COALESCE(dru.nombres, u.nombres), COALESCE(dru.apellido_paterno, u.apellido_paterno), 
                COALESCE(dru.apellido_materno, u.apellido_materno))) AS nombre_completo,
                COALESCE(dru.correo_electronico, u.correo, '') AS correo,
                COALESCE(dru.telefono, '') AS telefono
            FROM usuarios u
            LEFT JOIN datos_rendicion_usuario dru ON dru.acceso_rut_id = u.id
            LEFT JOIN certificados_segundo_semestre c ON c.acceso_rut_id = u.id AND c.proceso_id = ?
            WHERE u.rol = 'usuario' AND u.activo = 1 AND u.rut IS NOT NULL AND c.id IS NULL
            ORDER BY nombre_completo, u.id",
        );
    } else {
        $consultaPendientesSegundo = $conn->prepare(
            "SELECT
                u.id AS estudiante_id,
                COALESCE(dp.rut, u.rut, '') AS rut,
                TRIM(CONCAT_WS(' ', COALESCE(dp.nombres, u.nombres), COALESCE(dp.apellido_paterno, u.apellido_paterno), 
                COALESCE(dp.apellido_materno, u.apellido_materno))) AS nombre_completo,
                COALESCE(dp.correo_electronico, u.correo, '') AS correo,
                COALESCE(dp.telefono, '') AS telefono
            FROM postulaciones p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            LEFT JOIN datos_personales dp ON dp.postulacion_id = p.id
            LEFT JOIN certificados_segundo_semestre c ON c.postulacion_id = p.id AND COALESCE(c.proceso_id, p.proceso_id) = ?
            WHERE p.proceso_id = ? AND p.estado = 'beneficiado' AND c.id IS NULL
            ORDER BY nombre_completo, u.id",
        );
    }

    if (!$consultaPendientesSegundo) {
        throw new RuntimeException("No fue posible consultar los certificados pendientes.");
    }

    if (($procesoSegundo["tipo_proceso"] ?? "") === "solo_rendicion") {
        $consultaPendientesSegundo->bind_param("i", $procesoSegundoId);
    } else {
        $consultaPendientesSegundo->bind_param("ii", $procesoSegundoId, $procesoSegundoId);
    }

    $consultaPendientesSegundo->execute();
    $pendientesSegundo = $consultaPendientesSegundo->get_result()->fetch_all(MYSQLI_ASSOC);
    $consultaPendientesSegundo->close();
    $consultaBaseSegundo = "SELECT c.*,p.folio,COALESCE(dp.correo_electronico,dru.correo_electronico,u.correo,'') correo,
        COALESCE(dp.telefono,dru.telefono,'') telefono,
        COALESCE(dp.nombres,dru.nombres,u.nombres,'') nombres,
        COALESCE(dp.apellido_paterno,dru.apellido_paterno,u.apellido_paterno,'') apellido_paterno,
        COALESCE(dp.apellido_materno,dru.apellido_materno,u.apellido_materno,'') apellido_materno,
        COALESCE(dp.rut,u.rut,'') rut,
        COALESCE(de.carrera,dru.carrera,'') carrera,
        COALESCE(de.nombre_establecimiento,dru.nombre_establecimiento,'') institucion, TRIM(CONCAT_WS(' ',revisor.nombres,revisor.apellido_paterno,revisor.apellido_materno)) nombre_revisor
        FROM certificados_segundo_semestre c
        LEFT JOIN postulaciones p ON p.id=c.postulacion_id
        JOIN usuarios u ON u.id=COALESCE(c.acceso_rut_id,p.usuario_id)
        LEFT JOIN datos_rendicion_usuario dru ON dru.acceso_rut_id=u.id LEFT JOIN datos_personales dp ON dp.postulacion_id=p.id
        LEFT JOIN datos_educacionales de ON de.postulacion_id=p.id LEFT JOIN usuarios revisor ON revisor.id=c.revisor_id
        WHERE COALESCE(c.proceso_id,p.proceso_id)=$procesoSegundoId";
    $filtroSegundo = "";
    $terminoSegundo = "%" . $buscarSegundo . "%";
    $rutBuscarSegundo = "%" . preg_replace("/[.\s-]+/u", "", $buscarSegundo) . "%";
    if ($buscarSegundo !== "") {
        $filtroSegundo = " WHERE (
            CONVERT(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno) USING utf8mb4) COLLATE utf8mb4_general_ci LIKE ?
            OR CONVERT(rut USING utf8mb4) COLLATE utf8mb4_general_ci LIKE ?
            OR CONVERT(correo USING utf8mb4) COLLATE utf8mb4_general_ci LIKE ?
            OR REPLACE(REPLACE(REPLACE(CONVERT(rut USING utf8mb4), '.', ''), '-', ''), ' ', '') COLLATE utf8mb4_general_ci LIKE ?
        )";
    }
    $contarSegundo = $conn->prepare(
        "SELECT COUNT(*) total FROM (" . $consultaBaseSegundo . ") certificados" . $filtroSegundo,
    );
    if ($buscarSegundo !== "") {
        $contarSegundo->bind_param(
            "ssss",
            $terminoSegundo,
            $terminoSegundo,
            $terminoSegundo,
            $rutBuscarSegundo,
        );
    }
    $contarSegundo->execute();
    $totalSegundo = (int) $contarSegundo->get_result()->fetch_assoc()["total"];
    $totalGeneralSegundo = (int) $estadisticasSegundo["recibidos"];
    $paginasSegundo = max(1, (int) ceil($totalSegundo / 25));
    $paginaSegundo = min(
        $paginasSegundo,
        max(1, (int) ($_GET["pagina"] ?? ($_POST["pagina"] ?? 1))),
    );
    $offsetSegundo = ($paginaSegundo - 1) * 25;
    $avanceSegundo =
        (int) $estadisticasSegundo["postulantes"] > 0
            ? round(
                (100 * (int) $estadisticasSegundo["recibidos"]) /
                    (int) $estadisticasSegundo["postulantes"],
                1,
            )
            : 0;
    $consultarSegundo = $conn->prepare(
        "SELECT * FROM (" .
            $consultaBaseSegundo .
            ") certificados" .
            $filtroSegundo .
            " ORDER BY (estado='pendiente') DESC,fecha_subida DESC,id DESC LIMIT 25 OFFSET $offsetSegundo",
    );
    if ($buscarSegundo !== "") {
        $consultarSegundo->bind_param(
            "ssss",
            $terminoSegundo,
            $terminoSegundo,
            $terminoSegundo,
            $rutBuscarSegundo,
        );
    }
    $consultarSegundo->execute();
    $listadoSegundo = $consultarSegundo->get_result()->fetch_all(MYSQLI_ASSOC);
}
require dirname(__DIR__) . "/vistas/segundo_semestre.php";
