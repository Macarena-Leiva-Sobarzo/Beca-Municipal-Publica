<?php

/** Aviso preparado por el contexto de rendición, sin formulario ni documentos.
 * @var string $tituloAvisoRendicion
 * @var string $mensajeAvisoRendicion
 */
$escaparAviso = static fn(mixed $valor) => htmlspecialchars(
    (string) $valor,
    ENT_QUOTES,
    "UTF-8",
); ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendición | Becas Estudiantiles</title>
    <link rel="stylesheet" href="../css/estilos.css?v=mobile-20261020&modulos-proceso-20261004">
</head>

<body>
    <header class="header">
        <div class="logo">Becas Estudiantiles</div>
        <?php require __DIR__ . "/menu.php"; ?>
    </header>
    <main class="resultado">

        <section class="resultado-box" role="status">
            <div class="resultado-icono revision" aria-hidden="true">!</div>
            <h1><?= $escaparAviso($tituloAvisoRendicion) ?></h1>
            <?php require __DIR__ . "/../proceso_modulo.php"; ?>
            <p><?= $escaparAviso($mensajeAvisoRendicion) ?></p>
            <a href="inicio.php" class="btn-portal resultado-volver-portal">Volver al portal</a>
        </section>
    </main>
    <footer class="footer">
        <p>© <?= date("Y") ?> Becas Estudiantiles</p>
    </footer>
</body>

</html>
