<?php
if (!isset($mensaje, $inicioAviso, $cssAviso)) {
    require_once __DIR__ . "/../config/aviso.php";
    mostrarAvisoAccesoBecas("Acceso no autorizado.", 403);
}
$escaparAviso = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información importante | Becas Estudiantiles</title>
    <link rel="stylesheet" href="<?= $escaparAviso($cssAviso) ?>">
</head>
<body class="pagina-aviso">
    <main class="aviso-contenedor">
        <section class="aviso-tarjeta" aria-labelledby="aviso-titulo">
            <h1 id="aviso-titulo">Información importante</h1>
            <p><?= $escaparAviso($mensaje) ?></p>
            <button
                type="button"
                class="aviso-aceptar"
                id="aceptarAviso"
                data-destino="<?= $escaparAviso($destinoAviso ?? $inicioAviso) ?>"
                data-accion="<?= $escaparAviso($accionAviso ?? "volver") ?>">
                Aceptar
            </button>
        </section>
    </main>
    <footer class="aviso-footer"><p>© <?= date("Y") ?> Becas Estudiantiles</p></footer>
    <script>
        document.getElementById("aceptarAviso").addEventListener("click", function () {
            if (this.dataset.accion === "volver" && window.parent !== window) {
                window.parent.postMessage(
                    { tipo: "becas-cerrar-visor-documento" },
                    window.location.origin
                );
                return;
            }

            window.location.assign(this.dataset.destino);
        });
    </script>
</body>
</html>
