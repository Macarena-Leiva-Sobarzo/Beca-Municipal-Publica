<?php
/**
 * Variables recibidas desde:
 * usuario/comprobante.php
 *
 * @var int $usuario_id
 * @var array $postulacion
 */
?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Comprobante de postulación
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=mobile-20261020&modulos-proceso-20261004">

</head>

<body>


    <header class="header">

        <div class="logo">
            Becas Estudiantiles
        </div>


        <?php require __DIR__ . "/menu.php"; ?>

    </header>


    <main class="comprobante-pagina">



        <div class="comprobante-encabezado">


            <div>

                <h1>
                    Comprobante de postulación
                </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>


                <p>
                    Revisa el documento antes de descargarlo
                    o imprimirlo
                </p>

            </div>


            <div class="comprobante-acciones">


                <a
                    href="generar_comprobante.php?modo=descargar"
                    class="btn-comprobante-secundario">

                    Descargar PDF

                </a>


                <a
                    href="generar_comprobante.php?modo=ver"
                    target="_blank"
                    class="btn-comprobante-principal">

                    Abrir para imprimir

                </a>


            </div>


        </div>


        <div class="comprobante-visor">

            <iframe
                src="generar_comprobante.php?modo=ver"
                title="Vista previa del comprobante de postulación"
                class="comprobante-iframe">
            </iframe>

        </div>


        <div class="comprobante-volver">

            <a
                href="postulacion.php"
                class="btn-formulario-secundario">

                Volver

            </a>

        </div>


    </main>


    <footer class="footer">

        <p>
            © <?= date("Y") ?> Becas Estudiantiles
        </p>

    </footer>


</body>

</html>
