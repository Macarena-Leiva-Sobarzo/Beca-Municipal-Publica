<?php

/**
 * Presentación de comprobante rendicion.
 * Los datos se preparan en el controlador; se escapan al incorporarlos al HTML.
 * @var array $rendicion
 * @var array $gastos
 */

function eComprobante(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

$nombreCompleto = trim(
    (string) ($rendicion["nombres"] ?? "") .
        " " .
        (string) ($rendicion["apellido_paterno"] ?? "") .
        " " .
        (string) ($rendicion["apellido_materno"] ?? ""),
);
?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Comprobante de rendición
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=mobile-20261020&modulos-proceso-20261004">

</head>

<body class="estilo-rendicion">

    <header class="header">

        <div class="logo">
            Becas Estudiantiles
        </div>

        <?php require __DIR__ . "/menu.php"; ?>

    </header>

    <main class="portal-usuario">


        <div class="portal-encabezado">

            <h1>
                Comprobante de rendición
            </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>

            <p>
                <?= eComprobante($nombreCompleto) ?>
                ·
                <span class="rut-completo"><?= eComprobante($rendicion["rut"] ?? "") ?></span>
            </p>

        </div>

        <section class="portal-card">

            <div
                style="
                display:flex;
                gap:12px;
                flex-wrap:wrap;
                margin-bottom:20px;
            ">

                <a
                    href="generar_comprobante_rendicion.php?modo=descargar"
                    class="btn-formulario-principal">
                    Descargar PDF
                </a>

                <a
                    href="rendicion.php"
                    class="btn-formulario-secundario">
                    Volver
                </a>

            </div>

            <iframe
                src="generar_comprobante_rendicion.php?modo=ver"
                title="Comprobante de rendición"
                style="
                width:100%;
                min-height:800px;
                border:1px solid #dbe2ea;
                border-radius:12px;
            "></iframe>

        </section>

    </main>

    <footer class="footer">

        <p>
            © <?= date("Y") ?>
            Becas Estudiantiles
        </p>

    </footer>

</body>

</html>
