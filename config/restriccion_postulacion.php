<?php
require_once __DIR__ . "/reglas_beca.php";
require_once __DIR__ . "/obligados_rendicion.php";

/** La sanción dura solo el año inmediatamente posterior al beneficio, una vez vencida la rendición. */
function incumplimientoRendicion(
    array $antecedente,
    int $anioDestino,
    ?DateTimeImmutable $ahora = null,
): bool {
    $ahora ??= new DateTimeImmutable("now", new DateTimeZone("America/Santiago"));
    $beneficio = fechaBeca($antecedente["fecha_postulacion"] ?? null);
    $cierre = fechaBeca($antecedente["fecha_cierre_rendicion"] ?? null);
    if (
        ($antecedente["estado"] ?? "") !== "beneficiado" ||
        !$beneficio ||
        !$cierre ||
        (int) $beneficio->format("Y") + 1 !== $anioDestino ||
        $ahora <= $cierre
    ) {
        return false;
    }
    $envio = fechaBeca($antecedente["fecha_envio"] ?? null);
    // Una rendición enviada cuenta aunque esté en revisión o haya sido rechazada.
    return !$envio || $envio > $cierre;
}

/** Consulta por cuenta y RUT: cambiar de correo o crear otra cuenta no evita la restricción. */
function restriccionPostulacion(
    mysqli $conn,
    int $usuarioId,
    array $proceso,
    string $rut = "",
    ?DateTimeImmutable $ahora = null,
): ?string {
    $inicio = fechaBeca($proceso["fecha_inicio"] ?? null);
    if (!$inicio) {
        return null;
    }
    $anio = (int) $inicio->format("Y");
    $normalizar = static fn($valor) => strtoupper(preg_replace("/[^0-9kK]/", "", (string) $valor));
    $ruts = [];
    if ($rut !== "") {
        $ruts[] = $normalizar($rut);
    }
    $stmt = $conn->prepare(
        "SELECT rut FROM usuarios WHERE id=? UNION SELECT dp.rut FROM datos_personales dp JOIN postulaciones p ON p.id=dp.postulacion_id WHERE p.usuario_id=?",
    );
    $stmt->bind_param("ii", $usuarioId, $usuarioId);
    $stmt->execute();
    foreach ($stmt->get_result() as $fila) {
        if ($fila["rut"]) {
            $ruts[] = $normalizar($fila["rut"]);
        }
    }
    $stmt->close();
    $ruts = array_values(array_unique(array_filter($ruts)));
    $rutSql = $ruts
        ? " OR UPPER(REPLACE(REPLACE(REPLACE(dp.rut,'.',''),'-',''),' ','')) IN (" .
            implode(",", array_fill(0, count($ruts), "?")) .
            ")"
        : "";
    $sql =
        "SELECT p.estado,p.fecha_postulacion,pb.fecha_cierre_rendicion,r.fecha_envio FROM postulaciones p JOIN procesos_beca pb ON pb.id=p.proceso_id LEFT JOIN datos_personales dp ON dp.postulacion_id=p.id LEFT JOIN rendiciones r ON r.postulacion_id=p.id WHERE p.estado='beneficiado' AND YEAR(p.fecha_postulacion)=? AND (p.usuario_id=?" .
        $rutSql .
        ")";
    $anterior = $anio - 1;
    $stmt = $conn->prepare($sql);
    $params = [$anterior, $usuarioId, ...$ruts];
    $stmt->bind_param("ii" . str_repeat("s", count($ruts)), ...$params);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    // Los beneficiarios del sistema de rendición conservan la misma regla anual.
    $rutImportado = $ruts
        ? " OR UPPER(REPLACE(REPLACE(REPLACE(a.rut,'.',''),'-',''),' ','')) IN (" .
            implode(",", array_fill(0, count($ruts), "?")) .
            ")"
        : "";
    $sqlImportado =
        "SELECT 'beneficiado' estado,COALESCE(pb.fecha_inicio_rendicion,r.fecha_inicio,r.fecha_envio) fecha_postulacion,pb.fecha_cierre_rendicion,r.fecha_envio FROM rendiciones r JOIN accesos_rut a ON a.id=r.acceso_rut_id JOIN procesos_beca pb ON pb.id=r.proceso_id WHERE r.postulacion_id IS NULL AND YEAR(COALESCE(pb.fecha_inicio_rendicion,r.fecha_inicio,r.fecha_envio))=? AND (a.id=?" .
        $rutImportado .
        ")";
    $stmt = $conn->prepare($sqlImportado);
    $stmt->bind_param("ii" . str_repeat("s", count($ruts)), ...$params);
    $stmt->execute();
    $filas = array_merge($filas, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    // Padrón obligatorio: también detecta a quienes nunca iniciaron una rendición.
    registrarObligadosRendicion2026($conn);
    $rutObligado = $ruts
        ? " OR UPPER(REPLACE(REPLACE(REPLACE(u.rut,'.',''),'-',''),' ','')) IN (" .
            implode(",", array_fill(0, count($ruts), "?")) .
            ")"
        : "";
    $sqlObligados =
        "SELECT 'beneficiado' estado,pb.fecha_inicio_rendicion fecha_postulacion,
        pb.fecha_cierre_rendicion,MIN(r.fecha_envio) fecha_envio
        FROM obligados_rendicion o JOIN usuarios u ON u.id=o.usuario_id
        JOIN procesos_beca pb ON pb.id=o.proceso_id
        LEFT JOIN rendiciones r ON r.proceso_id=o.proceso_id AND r.acceso_rut_id=o.usuario_id
        WHERE YEAR(pb.fecha_inicio_rendicion)=? AND (u.id=?" .
        $rutObligado .
        ')
        GROUP BY o.proceso_id,o.usuario_id,pb.fecha_inicio_rendicion,pb.fecha_cierre_rendicion';
    $stmt = $conn->prepare($sqlObligados);
    $stmt->bind_param("ii" . str_repeat("s", count($ruts)), ...$params);
    $stmt->execute();
    $filas = array_merge($filas, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    foreach ($filas as $fila) {
        if (incumplimientoRendicion($fila, $anio, $ahora)) {
            return "No puedes postular en $anio porque recibiste la beca en $anterior y no enviaste la rendición dentro del plazo. Podrás volver a postular en " .
                ($anio + 1) .
                ", si cumples los requisitos y el plazo está abierto.";
        }
    }
    return null;
}
