<?php
/**
 * Variables recibidas desde:
 * auth/login.php
 *
 * @var string $rut_anterior
 * @var bool $modoAdministrativo
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
        Iniciar sesión
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
                Iniciar sesión
            </h1>


            <p>
                Ingresa a tu cuenta para continuar con tu postulación
            </p>


            <?php if (isset($_GET["registro"]) && $_GET["registro"] === "exitoso"): ?>

                <div class="mensaje exito">

                    Cuenta creada correctamente
                    Ahora puedes iniciar sesión

                </div>

            <?php endif; ?>


            <?php if (isset($_GET["error"])): ?>

                <div class="mensaje error">

                    El RUT o la contraseña son incorrectos

                </div>

            <?php endif; ?>


            <form
                action="procesar_login.php"
                method="POST">

                <?php if ($modoAdministrativo): ?>
                    <input type="hidden" name="modo" value="administrador">
                <?php endif; ?>


                <div class="campo">

                    <label for="rut">
                        RUT
                    </label>

                    <input
                        type="text"
                        id="rut"
                        name="rut"
                        placeholder="12.345.678-5" maxlength="12"
                        required
                        autocomplete="username"
                        value="<?= htmlspecialchars($rut_anterior, ENT_QUOTES, "UTF-8") ?>"
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
                        autocomplete="current-password">

                </div>


                <button
                    type="submit"
                    class="btn-formulario">
                    Iniciar sesión
                </button>


            </form>


            <div class="auth-link">

                ¿No tienes una cuenta?

                <a href="registro.php">
                    Crear cuenta
                </a>

            </div>


            <a
                href="../index.php"
                class="volver">
                Volver al inicio
            </a>


        </div>


    </div>


    <script>
        document.addEventListener(
            "DOMContentLoaded",
            function() {

                const campoCorreo =
                    document.getElementById(
                        "rut"
                    );

                if (campoCorreo) {

                    <?php if (isset($_GET["error"])): ?>
                    campoCorreo.value = "";
                    <?php endif; ?>

                    campoCorreo.focus();

                    campoCorreo.select();

                }

            }
        );
    </script>

</body>

</html>
