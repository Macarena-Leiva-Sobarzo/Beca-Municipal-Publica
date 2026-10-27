<?php

declare(strict_types=1);

/** Utilidades para procesos donde el acceso del estudiante se realiza solo con RUT. */
function rutSoloRendicionNormalizado(string $rut): string
{
    return strtoupper((string) preg_replace("/[^0-9kK]/", "", $rut));
}

function rutSoloRendicionValido(string $rut): bool
{
    $rut = rutSoloRendicionNormalizado($rut);
    if (!preg_match("/^\\d{7,8}[0-9K]$/", $rut)) {
        return false;
    }
    $cuerpo = substr($rut, 0, -1);
    $dv = substr($rut, -1);
    $suma = 0;
    $factor = 2;
    for ($indice = strlen($cuerpo) - 1; $indice >= 0; $indice--) {
        $suma += (int) $cuerpo[$indice] * $factor;
        $factor = $factor === 7 ? 2 : $factor + 1;
    }
    $resultado = 11 - ($suma % 11);
    $esperado = $resultado === 11 ? "0" : ($resultado === 10 ? "K" : (string) $resultado);
    return hash_equals($esperado, $dv);
}

function rutSoloRendicionFormateado(string $rut): string
{
    $rut = rutSoloRendicionNormalizado($rut);
    return number_format((int) substr($rut, 0, -1), 0, "", ".") . "-" . substr($rut, -1);
}

function procesoSoloRendicionActivo(mysqli $conn): ?array
{
    $resultado = $conn->query(
        "SELECT * FROM procesos_beca WHERE activo=1 ORDER BY id DESC LIMIT 1",
    );
    $proceso = $resultado ? $resultado->fetch_assoc() : null;
    return ($proceso["tipo_proceso"] ?? "") === "solo_rendicion" ? $proceso : null;
}

function usuarioHabilitadoSoloRendicion(mysqli $conn, int $procesoId, int $usuarioId): bool
{
    $consulta = $conn->prepare(
        "SELECT 1 FROM obligados_rendicion o
         JOIN usuarios u ON u.id=o.usuario_id
         WHERE o.proceso_id=? AND o.usuario_id=? AND u.rol='usuario' AND u.activo=1",
    );
    $consulta->bind_param("ii", $procesoId, $usuarioId);
    $consulta->execute();
    $habilitado = (bool) $consulta->get_result()->fetch_row();
    $consulta->close();
    return $habilitado;
}
