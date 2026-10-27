<?php

/** Centraliza los totales usados por el informe financiero descargable. */
function obtenerResumenFinanciero(mysqli $conn): array
{
    require_once __DIR__ . "/../config/proceso_activo.php";
    $procesoFinancieroId = idProcesoActivoBeca($conn);
    $categorias = [
        "arancel" => 0,

        "matricula" => 0,

        "materiales" => 0,

        "vestuario" => 0,

        "alimentacion" => 0,

        "otro" => 0,
    ];

    $sql = "

        SELECT

            categoria,

            SUM(monto) AS total


        FROM rendicion_gastos rg

        INNER JOIN rendiciones r ON r.id = rg.rendicion_id

        LEFT JOIN postulaciones p ON p.id = r.postulacion_id

        WHERE COALESCE(r.proceso_id, p.proceso_id) = $procesoFinancieroId


        GROUP BY categoria


        ORDER BY categoria ASC

    ";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        throw new Exception($conn->error);
    }

    while ($fila = $resultado->fetch_assoc()) {
        $categoria = $fila["categoria"];

        if (isset($categorias[$categoria])) {
            $categorias[$categoria] = (float) ($fila["total"] ?? 0);
        }
    }

    $totalRendido = array_sum($categorias);

    $resultadoDevuelto = $conn->query(
        "
            SELECT

                SUM(monto_devuelto)
                AS total_devuelto


            FROM rendiciones r

            LEFT JOIN postulaciones p ON p.id = r.postulacion_id

            WHERE COALESCE(r.proceso_id, p.proceso_id) = $procesoFinancieroId
                AND r.monto_devuelto IS NOT NULL

            ",
    );

    if (!$resultadoDevuelto) {
        throw new Exception($conn->error);
    }

    $filaDevuelto = $resultadoDevuelto->fetch_assoc();

    $totalDevuelto = (float) ($filaDevuelto["total_devuelto"] ?? 0);

    return [
        "categorias" => $categorias,

        "total_rendido" => $totalRendido,

        "total_devuelto" => $totalDevuelto,
    ];
}

function generarResumenFinanciero(array $datos): string
{
    $nombres = [
        "arancel" => "Arancel",

        "matricula" => "Matrícula",

        "materiales" => "Materiales",

        "vestuario" => "Vestuario",

        "alimentacion" => "Alimentación",

        "otro" => "Otro",
    ];

    $html = "

    <h2>
        Resumen financiero
    </h2>

    <table>

    <tr>

        <th>
            Categoría
        </th>

        <th>
            Total rendido
        </th>

    </tr>

    ";

    foreach ($datos["categorias"] as $categoria => $monto) {
        $html .=
            "

        <tr>

            <td>

                " .
            eArchivo($nombres[$categoria] ?? $categoria) .
            "

            </td>


            <td>

                $

                " .
            number_format($monto, 0, ",", ".") .
            "

            </td>

        </tr>

        ";
    }

    $html .=
        "

    <tr>

        <th>
            Total rendido
        </th>

        <th>

            $

            " .
        number_format($datos["total_rendido"], 0, ",", ".") .
        "

        </th>

    </tr>

    </table>

    <br>

    <h2>
        Dinero devuelto
    </h2>

    <table>

    <tr>

        <th>
            Descripción
        </th>


        <th>
            Total
        </th>


    </tr>

    <tr>

        <td>

            Devoluciones realizadas

        </td>

        <td>

            $

            " .
        number_format($datos["total_devuelto"], 0, ",", ".") .
        "

        </td>


    </tr>

    </table>

    ";

    return $html;
}
