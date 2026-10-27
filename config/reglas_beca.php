<?php

/** Reglas compartidas de fechas en horario de Chile. Una rendición requiere postulación beneficiada, resultado publicado y plazo de rendición abierto. */

declare(strict_types=1);

function fechaBeca(?string $valor): ?DateTimeImmutable
{
    if (!$valor) {
        return null;
    }
    $fecha = DateTimeImmutable::createFromFormat(
        "!Y-m-d H:i:s",
        $valor,
        new DateTimeZone("America/Santiago"),
    );
    return $fecha && $fecha->format("Y-m-d H:i:s") === $valor ? $fecha : null;
}

function plazoBecaAbierto(
    array $proceso,
    string $inicio,
    string $cierre,
    ?DateTimeImmutable $ahora = null,
): bool {
    // Comprobar siempre en servidor: ocultar el botón no impide un POST manual.
    // Ambos extremos del plazo son inclusivos; el año corresponde al inicio de esta etapa.
    $ahora ??= new DateTimeImmutable("now", new DateTimeZone("America/Santiago"));
    $desde = fechaBeca($proceso[$inicio] ?? null);
    $hasta = fechaBeca($proceso[$cierre] ?? null);
    return mensajeAnioProcesoBeca($proceso, $inicio, $ahora) === null &&
        $desde !== null &&
        $hasta !== null &&
        $desde <= $hasta &&
        $ahora >= $desde &&
        $ahora <= $hasta;
}

/** El año se obtiene del período correspondiente, no del nombre editable del proceso. */
function mensajeAnioProcesoBeca(
    array $proceso,
    string $inicio,
    ?DateTimeImmutable $ahora = null,
): ?string {
    $ahora ??= new DateTimeImmutable("now", new DateTimeZone("America/Santiago"));
    $fecha = fechaBeca($proceso[$inicio] ?? null);
    if (!$fecha) {
        return null;
    }
    $anio = (int) $fecha->format("Y");
    $actual = (int) $ahora->setTimezone(new DateTimeZone("America/Santiago"))->format("Y");
    if ($anio === $actual) {
        return null;
    }
    $etapa = $inicio === "fecha_inicio_rendicion" ? "rendición" : "postulación";
    return $anio < $actual
        ? "Fuera de plazo: el período de $etapa corresponde al año $anio y el año actual es $actual. No puedes iniciar ni enviar una $etapa de ese período."
        : "El año del período de $etapa ($anio) no coincide con el año actual ($actual). La $etapa aún no está disponible.";
}

function resultadoBecaPublicado(array $postulacion, ?DateTimeImmutable $ahora = null): bool
{
    $ahora ??= new DateTimeImmutable("now", new DateTimeZone("America/Santiago"));
    $fecha = fechaBeca($postulacion["fecha_resultados"] ?? null);
    return $fecha !== null && $ahora >= $fecha;
}

function puedeRendirBeca(array $postulacion, ?DateTimeImmutable $ahora = null): bool
{
    return ($postulacion["estado"] ?? "") === "beneficiado" &&
        resultadoBecaPublicado($postulacion, $ahora) &&
        plazoBecaAbierto($postulacion, "fecha_inicio_rendicion", "fecha_cierre_rendicion", $ahora);
}
