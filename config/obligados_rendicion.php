<?php

/**
 * Compatibilidad para instalaciones anteriores.
 * El padrón de cada proceso de solo rendición ahora lo carga exclusivamente el superior.
 */
function registrarObligadosRendicion2026(mysqli $conn): void
{
    // No insertar automáticamente: evita habilitar RUT no incorporados al padrón.
}
