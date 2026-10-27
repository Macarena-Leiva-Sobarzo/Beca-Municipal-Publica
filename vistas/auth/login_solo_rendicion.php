<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a rendición | Becas Estudiantiles</title>
    <link rel="stylesheet" href="../css/estilos.css?v=mobile-20261020&solo-rendicion-20261019">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="etiqueta">BECAS ESTUDIANTILES</div>
            <h1>Acceso a rendición</h1>
            <p>Ingresa tu RUT para realizar la rendición de tu beca.</p>
            <?php if (isset($_GET["error"])): ?>
                <div class="mensaje error">
                    <?= $_GET["error"] === "no_autorizado"
                        ? "Tu RUT no está habilitado para este proceso de rendición."
                        : "Ingresa un RUT chileno válido." ?>
                </div>
            <?php endif; ?>
            <form action="procesar_rut_rendicion.php" method="post">
                <div class="campo">
                    <label for="rut">RUT</label>
                    <input id="rut" name="rut" type="text" required autofocus autocomplete="username"
                        placeholder="12.345.678-5" value="<?= htmlspecialchars((string) ($rut_anterior ?? ""), ENT_QUOTES, "UTF-8") ?>">
                    <small class="campo-ayuda">Puedes ingresarlo con o sin puntos y guion.</small>
                </div>
                <button type="submit" class="btn-formulario">Ingresar a rendición</button>
            </form>
            <div class="auth-link"><a href="login.php?modo=administrador">Acceso administrativo</a></div>
            <a href="../index.php" class="volver">Volver al inicio</a>
        </div>
    </div>
    <?php if (isset($_GET["error"])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const campoRut = document.getElementById("rut");
                if (campoRut) {
                    campoRut.value = "";
                    campoRut.focus();
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
