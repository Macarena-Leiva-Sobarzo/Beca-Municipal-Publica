<?php

/**
 * Variables preparadas por el controlador o la vista que incluye este archivo.
 * @var DateTimeImmutable $fechaAperturaRendicion
 */
$escaparEsperaRendicion = static fn(mixed $valor) => htmlspecialchars(
    (string) $valor,
    ENT_QUOTES,
    "UTF-8",
); ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendición aún no disponible | Becas Estudiantiles</title>
    <link rel="stylesheet" href="../css/estilos.css?v=mobile-20261020&modulos-proceso-20261004">
</head>

<body>
    <header class="header">
        <div class="logo">Becas Estudiantiles</div>
        <?php require __DIR__ . "/menu.php"; ?>
    </header>
    <main class="resultado">

        <section class="resultado-box" role="status">
            <div class="resultado-icono revision" aria-hidden="true">⏳</div>
            <h1>Rendición aún no disponible</h1>
            <?php require __DIR__ . "/../proceso_modulo.php"; ?>
            <p>El período de rendición todavía no ha comenzado.</p>
            <div class="monto-beca">
                <span>Inicio de rendición</span>
                <strong style="font-size: 24px;"><?= $escaparEsperaRendicion(
                                                        $fechaAperturaRendicion->format("d/m/Y H:i"),
                                                    ) ?></strong>
            </div>
        </section>
    </main>
    <footer class="footer">
        <p>© <?= date("Y") ?> Becas Estudiantiles</p>
    </footer>
</body>

</html>
