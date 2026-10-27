<?php
/**
 * Variables recibidas desde:
 * auth/registro.php
 *
 * @var string $rut
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
        Crear cuenta
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=mobile-20261020&unificada-20260924">

</head>

<body>


    <div class="auth-container">


        <div class="auth-box">


            <div class="etiqueta">
                BECAS ESTUDIANTILES
            </div>


            <h1>
                Crear cuenta
            </h1>


            <p>
                Crea tu cuenta para comenzar tu postulación.
                Solo necesitas ingresar tu RUT y contraseña.
            </p>


            <?php if (isset($_GET["error"])): ?>

                <div class="mensaje error">

                    <?= htmlspecialchars($_GET["error"], ENT_QUOTES, "UTF-8") ?>

                </div>

            <?php endif; ?>


            <form
                action="procesar_registro.php"
                method="POST">

                <div class="campo">

                    <label for="rut">
                        RUT
                    </label>

                    <input
                        type="text"
                        id="rut"
                        name="rut" inputmode="text" autocapitalize="characters"
                        placeholder="12.345.678-5" maxlength="12"
                        value="<?= htmlspecialchars($rut, ENT_QUOTES, "UTF-8") ?>"
                        required
                        autocomplete="username"
                        autofocus>

                </div>


                <div class="campo">

                    <label for="password">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password">

                    <small>
                        Mínimo 8 caracteres
                    </small>

                </div>


                <div class="campo">

                    <label for="password_confirmacion">
                        Confirmar contraseña
                    </label>

                    <input
                        type="password"
                        id="password_confirmacion"
                        name="password_confirmacion"
                        required
                        minlength="8"
                        autocomplete="new-password">

                </div>


                <button
                    type="submit"
                    class="btn-formulario">

                    Crear cuenta

                </button>


            </form>


            <div class="auth-link">

                ¿Ya tienes una cuenta?

                <a href="login.php">
                    Iniciar sesión
                </a>

            </div>


            <a
                href="../index.php"
                class="volver">

                Volver al inicio

            </a>


        </div>


    </div>


<script src="../js/rut_cuenta.js?v=20261006" defer></script>
</body>

</html>
