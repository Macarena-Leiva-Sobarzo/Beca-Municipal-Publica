<?php
/** Normaliza el RUT antes de validarlo o guardarlo: k siempre se convierte a K. */
function normalizarRutBecas(string $rut): string
{
    return strtoupper(preg_replace("/[.\s-]+/u", "", trim($rut)));
}
