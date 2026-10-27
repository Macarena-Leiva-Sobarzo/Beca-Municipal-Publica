<?php

/**
 * Variables recibidas desde:
 * usuario/formulario_postulacion.php
 *
 * @var int $usuario_id
 * @var array $old
 * @var array $erroresDocumentos
 * @var mysqli_result $bancos
 * @var mysqli_result $tipos_cuenta
 * @var string $correo_usuario
 * @var string $correo_formulario
 * @var string|null $errorPostulacion
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
        Formulario de postulación
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


    <main class="formulario-postulacion">



        <div class="formulario-encabezado">



            <h1>
                Formulario de postulación
            </h1>
            <?php require __DIR__ . "/../proceso_modulo.php"; ?>

        </div>


        <?php if ($errorPostulacion): ?>

            <div class="alerta-formulario error">

                <?= htmlspecialchars($errorPostulacion, ENT_QUOTES, "UTF-8") ?>

            </div>

        <?php endif; ?>



        <div class="aviso-documentos">
            <div class="aviso-documentos-icono" aria-hidden="true">⚠</div>
            <p>Completa todos los campos y adjunta los cinco
                documentos obligatorios en formato PDF antes
                de enviar tu postulación.
                <br><br>
                Los campos marcados con <span class="obligatorio">*</span> son obligatorios.
            </p>
        </div>
        <div class="progreso">

            <div class="progreso-info">

                <span>
                    Progreso de la postulación
                </span>

                <span id="porcentajeProgreso">
                    0%
                </span>

            </div>


            <div class="progreso-barra">

                <div
                    class="progreso-completado"
                    id="progreso"></div>

            </div>

        </div>


        <form
            action="procesar_postulacion.php"
            method="POST"
            enctype="multipart/form-data"
            id="formPostulacion"
            novalidate>


            <section class="form-seccion">


                <div class="form-seccion-encabezado">

                    <div class="form-seccion-numero">
                        1
                    </div>


                    <div>

                        <h2>
                            Identificación del postulante
                        </h2>

                        <p>
                            Ingresa correctamente tus datos personales
                        </p>

                    </div>

                </div>



                <div class="form-grid">


                    <div class="campo-form">

                        <label for="nombres">

                            Nombres

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="nombres"
                            name="nombres"
                            maxlength="150"
                            autocomplete="given-name"
                            value="<?= oldValue($old, "nombres") ?>"
                            required>

                    </div>


                    <div class="campo-form">

                        <label for="apellido_paterno">

                            Apellido paterno

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="apellido_paterno"
                            name="apellido_paterno"
                            maxlength="100"
                            autocomplete="family-name"
                            value="<?= oldValue($old, "apellido_paterno") ?>"
                            required>

                    </div>


                    <div class="campo-form">

                        <label for="apellido_materno">

                            Apellido materno

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="apellido_materno"
                            name="apellido_materno"
                            maxlength="100"
                            value="<?= oldValue($old, "apellido_materno") ?>"
                            required>

                    </div>


                    <div class="campo-form">

                        <label>
                            RUT
                            <span class="obligatorio">*</span>
                        </label>

                        <div class="rut-dividido">

                            <div class="rut-numero">

                                <input
                                    type="text"
                                    id="rut_numero"
                                    name="rut_numero"
                                    inputmode="numeric"
                                    maxlength="10"
                                    placeholder="12.345.678"
                                    autocomplete="off"
                                    value="<?= oldValue($old, "rut_numero") ?>"
                                    required>

                            </div>


                            <span class="rut-guion">
                                -
                            </span>


                            <div class="rut-dv">

                                <input
                                    type="text"
                                    id="rut_dv"
                                    name="rut_dv"
                                    inputmode="text"
                                    autocapitalize="characters"
                                    pattern="[0-9Kk]"
                                    title="Ingresa un dígito del 0 al 9 o la letra K"
                                    maxlength="1"
                                    placeholder="K"
                                    autocomplete="off"
                                    value="<?= oldValue($old, "rut_dv") ?>"
                                    required>

                            </div>

                            <input
                                type="hidden"
                                id="rut"
                                name="rut"
                                value="<?= oldValue($old, "rut") ?>">


                            <span
                                id="rutEstado"
                                class="rut-estado"></span>

                        </div>


                        <small
                            class="campo-ayuda"
                            id="rutMensaje">
                            Ingresa el número del RUT y su dígito verificador
                        </small>

                    </div>


                    <div class="campo-form">

                        <label for="fecha_nacimiento">

                            Fecha de nacimiento

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="date"
                            id="fecha_nacimiento"
                            name="fecha_nacimiento"
                            value="<?= oldValue($old, "fecha_nacimiento") ?>"
                            required>

                    </div>


                    <div class="campo-form">

                        <label for="telefono">

                            Teléfono de contacto

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="tel"
                            id="telefono"
                            name="telefono"
                            inputmode="numeric"
                            maxlength="9"
                            minlength="9"
                            pattern="[0-9]{9}"
                            placeholder="912345678"
                            autocomplete="tel"
                            value="<?= oldValue($old, "telefono") ?>"
                            required>

                        <small class="campo-ayuda">
                            Ingresa exactamente 9 dígitos. Ejemplo: 912345678
                        </small>

                    </div>


                    <div
                        class="campo-form form-grid-completo">

                        <label for="direccion">

                            Dirección completa

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="direccion"
                            name="direccion"
                            maxlength="300"
                            autocomplete="street-address"
                            value="<?= oldValue($old, "direccion") ?>"
                            required>

                    </div>


                    <div
                        class="campo-form form-grid-completo">

                        <label for="correo_electronico">

                            Correo electrónico

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="email"
                            id="correo_electronico"
                            name="correo_electronico"
                            maxlength="150"
                            value="<?= htmlspecialchars($correo_formulario, ENT_QUOTES, "UTF-8") ?>"
                            autocomplete="email"
                            required>


                        <small class="campo-ayuda">

                            Se ha completado utilizando el
                            correo de tu cuenta

                        </small>

                    </div>


                </div>


            </section>


            <section class="form-seccion">


                <div class="form-seccion-encabezado">

                    <div class="form-seccion-numero">
                        2
                    </div>


                    <div>

                        <h2>
                            Antecedentes educacionales
                        </h2>

                        <p>
                            Información relacionada con tus estudios
                        </p>

                    </div>

                </div>



                <div class="form-grid">


                    <div class="campo-form">

                        <label for="carrera">

                            Carrera

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="carrera"
                            name="carrera"
                            maxlength="200"
                            value="<?= oldValue($old, "carrera") ?>"
                            required>

                    </div>


                    <div class="campo-form">

                        <label for="establecimiento">

                            Nombre del establecimiento

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="establecimiento"
                            name="nombre_establecimiento"
                            maxlength="250"
                            value="<?= oldValue($old, "nombre_establecimiento") ?>"
                            required>

                    </div>


                    <div class="campo-form">

                        <label for="region">

                            Región del establecimiento

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="region"
                            name="region"
                            maxlength="150"
                            value="<?= oldValue($old, "region") ?>"
                            required>

                    </div>


                    <div class="campo-form">

                        <label for="comuna">

                            Comuna del establecimiento

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="comuna"
                            name="comuna"
                            maxlength="150"
                            value="<?= oldValue($old, "comuna") ?>"
                            required>

                    </div>


                    <div
                        class="campo-form form-grid-completo">

                        <label>

                            ¿Cuenta con gratuidad?

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <div class="opciones-radio">


                            <label class="radio-opcion">

                                <input
                                    type="radio"
                                    name="tiene_gratuidad"
                                    value="si"

                                    <?php if (($old["tiene_gratuidad"] ?? "") === "si"): ?>

                                    checked

                                    <?php endif; ?>

                                    required>

                                Sí

                            </label>



                            <label class="radio-opcion">

                                <input
                                    type="radio"
                                    name="tiene_gratuidad"
                                    value="no"

                                    <?php if (($old["tiene_gratuidad"] ?? "") === "no"): ?>

                                    checked

                                    <?php endif; ?>

                                    required>

                                No

                            </label>


                        </div>

                    </div>


                    <div class="documento-item form-grid-completo">
                        <div class="aviso-documentos">
                            <div class="aviso-documentos-icono" aria-hidden="true">⚠</div>
                            <p>El documento que acredita tu respuesta sobre gratuidad debe contener el RUT del postulante.</p>
                        </div>
                        <div class="documento-encabezado"><label for="acreditacion_gratuidad" class="documento-nombre">
                                Documento que acredita tu respuesta sobre gratuidad
                                <span class="obligatorio">*</span></label>
                            <span class="documento-obligatorio">OBLIGATORIO</span>
                        </div>
                        <p class="campo-ayuda">PDF obligatorio con contenido. Máximo 5 MB.</p>
                        <input type="file" class="documento-input" name="acreditacion_gratuidad"
                            id="acreditacion_gratuidad" accept=".pdf,application/pdf" required>
                        <div id="errorGratuidad" class="documento-error <?= isset(
                                                                            $erroresDocumentos["acreditacion_gratuidad"],
                                                                        )
                                                                            ? "visible"
                                                                            : "" ?>"><?= htmlspecialchars(
                                                                                            $erroresDocumentos["acreditacion_gratuidad"] ?? "",
                                                                                            ENT_QUOTES,
                                                                                            "UTF-8",
                                                                                        ) ?></div>
                    </div>
                    <div class="campo-form">

                        <label for="promedio">

                            Promedio último semestre

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="number"
                            id="promedio"
                            name="promedio_ultimo_semestre"
                            min="1"
                            max="7"
                            step="0.01"
                            placeholder="Ejemplo: 5.80"
                            value="<?= oldValue($old, "promedio_ultimo_semestre") ?>"
                            required>


                        <small class="campo-ayuda">

                            Debe encontrarse entre 1.00 y 7.00

                        </small>

                    </div>


                </div>


            </section>


            <section class="form-seccion">


                <div class="form-seccion-encabezado">

                    <div class="form-seccion-numero">
                        3
                    </div>


                    <div>

                        <h2>
                            Datos bancarios
                        </h2>

                        <p>
                            Ingresa los datos de la cuenta donde
                            recibirías el monto de la beca
                        </p>


                    </div>

                </div>



                <div class="aviso-documentos">
                    <div class="aviso-documentos-icono" aria-hidden="true">⚠</div>
                    <p>De preferencia, utiliza tu CuentaRUT. Si no tienes CuentaRUT, elige otra cuenta bancaria.</p>
                </div>
                <div class="form-grid">


                    <div class="campo-form">

                        <label for="banco">

                            Banco

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <select
                            id="banco"
                            name="banco_id"
                            required>

                            <option value="">
                                Selecciona un banco
                            </option>


                            <?php while ($banco = $bancos->fetch_assoc()): ?>


                                <option
                                    value="<?= (int) $banco["id"] ?>"

                                    <?php if (
                                        (string) ($old["banco_id"] ?? "") === (string) $banco["id"]
                                    ): ?>

                                    selected

                                    <?php endif; ?>>

                                    <?= htmlspecialchars($banco["nombre"], ENT_QUOTES, "UTF-8") ?>

                                </option>


                            <?php endwhile; ?>


                            <option
                                value="otro"

                                <?php if (($old["banco_id"] ?? "") === "otro"): ?>

                                selected

                                <?php endif; ?>>
                                Otro
                            </option>


                        </select>


                        <div
                            class="banco-otro"
                            id="bancoOtro">

                            <label
                                for="banco_otro"
                                class="label-banco-otro">
                                Nombre del banco
                            </label>


                            <input
                                type="text"
                                id="banco_otro"
                                name="banco_otro"
                                maxlength="150"
                                placeholder="Escribe el nombre del banco"
                                value="<?= oldValue($old, "banco_otro") ?>">

                        </div>


                    </div>


                    <div class="campo-form">

                        <label for="tipo_cuenta">

                            Tipo de cuenta

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <select
                            id="tipo_cuenta"
                            name="tipo_cuenta_id"
                            required>

                            <option value="">
                                Selecciona un tipo
                            </option>


                            <?php while ($tipo = $tipos_cuenta->fetch_assoc()): ?>


                                <option
                                    value="<?= (int) $tipo["id"] ?>"

                                    <?php if (
                                        (string) ($old["tipo_cuenta_id"] ?? "") ===
                                        (string) $tipo["id"]
                                    ): ?>

                                    selected

                                    <?php endif; ?>>

                                    <?= htmlspecialchars($tipo["nombre"], ENT_QUOTES, "UTF-8") ?>

                                </option>


                            <?php endwhile; ?>


                        </select>

                    </div>


                    <div
                        class="campo-form form-grid-completo">

                        <label for="numero_cuenta">

                            Número de cuenta

                            <span class="obligatorio">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="numero_cuenta"
                            name="numero_cuenta"
                            maxlength="100"
                            value="<?= oldValue($old, "numero_cuenta") ?>"
                            required>

                    </div>


                </div>


            </section>


            <section class="form-seccion">


                <div class="form-seccion-encabezado">

                    <div class="form-seccion-numero">
                        4
                    </div>


                    <div>

                        <h2>
                            Documentos obligatorios
                        </h2>

                        <p>
                            Debes adjuntar los cinco documentos
                            solicitados
                        </p>

                    </div>

                </div>



                <div class="aviso-documentos">

                    <div class="aviso-documentos-icono">
                        ⚠
                    </div>


                    <p>

                        Solo se permiten archivos PDF y cada
                        archivo puede pesar hasta 5 MB. No se aceptan PDFs vacíos o sin contenido detectable.

                        <br><br>

                        Cada PDF se verificará al seleccionarlo. Los datos de los documentos serán revisados por el personal municipal. La comprobación del PDF se realiza automáticamente al seleccionarlo.
                        Debes tener los cinco documentos verificados para enviar la postulación. Si cambias tus datos, los archivos seleccionados se volverán a verificar automáticamente.

                    </p>

                </div>



                <div class="documentos-lista">


                    <div class="documento-item">


                        <div class="documento-encabezado">

                            <span class="documento-nombre">

                                Carnet de identidad <span class="obligatorio">*</span>

                            </span>


                            <span class="documento-obligatorio">

                                OBLIGATORIO

                            </span>

                        </div>


                        <p class="campo-ayuda">

                            El PDF debe contener la fotocopia
                            del carnet por ambos lados

                        </p>


                        <input
                            type="file"
                            class="documento-input"
                            name="carnet"
                            id="carnet"
                            accept=".pdf,application/pdf"
                            required>


                        <div class="documento-info">

                            <span>
                                Formato: PDF
                            </span>

                            <span>
                                Máximo: 5 MB
                            </span>

                        </div>


                        <div
                            id="errorCarnet"
                            class="documento-error
                        <?= isset($erroresDocumentos["carnet"]) ? "visible" : "" ?>">

                            <?php if (isset($erroresDocumentos["carnet"])): ?>

                                <?= htmlspecialchars(
                                    $erroresDocumentos["carnet"],
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>

                            <?php endif; ?>

                        </div>


                    </div>


                    <div class="documento-item">


                        <div class="documento-encabezado">

                            <span class="documento-nombre">

                                Certificado de alumno regular <span class="obligatorio">*</span>

                            </span>


                            <span class="documento-obligatorio">

                                OBLIGATORIO

                            </span>

                        </div>


                        <p class="campo-ayuda">

                            Debe corresponder a un certificado
                            oficial de alumno regular

                        </p>


                        <input
                            type="file"
                            class="documento-input"
                            name="alumno_regular"
                            id="alumno_regular"
                            accept=".pdf,application/pdf"
                            required>


                        <div class="documento-info">

                            <span>
                                Formato: PDF
                            </span>

                            <span>
                                Máximo: 5 MB
                            </span>

                        </div>


                        <div
                            id="errorAlumno"
                            class="documento-error
                        <?= isset($erroresDocumentos["alumno_regular"]) ? "visible" : "" ?>">

                            <?php if (isset($erroresDocumentos["alumno_regular"])): ?>

                                <?= htmlspecialchars(
                                    $erroresDocumentos["alumno_regular"],
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>

                            <?php endif; ?>

                        </div>


                    </div>


                    <div class="documento-item">


                        <div class="documento-encabezado">

                            <span class="documento-nombre">

                                Concentración de notas <span class="obligatorio">*</span>

                            </span>


                            <span class="documento-obligatorio">

                                OBLIGATORIO

                            </span>

                        </div>


                        <p class="campo-ayuda">

                            Debe corresponder al último
                            certificado de notas y contener
                            el promedio del último semestre

                        </p>


                        <input
                            type="file"
                            class="documento-input"
                            name="certificado_notas"
                            id="certificado_notas"
                            accept=".pdf,application/pdf"
                            required>


                        <div class="documento-info">

                            <span>
                                Formato: PDF
                            </span>

                            <span>
                                Máximo: 5 MB
                            </span>

                        </div>


                        <div
                            id="errorNotas"
                            class="documento-error
                        <?= isset($erroresDocumentos["certificado_notas"]) ? "visible" : "" ?>">

                            <?php if (isset($erroresDocumentos["certificado_notas"])): ?>

                                <?= htmlspecialchars(
                                    $erroresDocumentos["certificado_notas"],
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) ?>

                            <?php endif; ?>

                        </div>


                    </div>


                    <div class="documento-item form-grid-completo">
                        <div class="documento-encabezado">
                            <label for="registro_social" class="documento-nombre">Registro Social de Hogares 
                                <span class="obligatorio">*</span></label><span class="documento-obligatorio">OBLIGATORIO</span></div>
                        <p class="campo-ayuda">PDF obligatorio con contenido. Máximo 5 MB.</p>
                        <input type="file" class="documento-input" name="registro_social" 
                        id="registro_social" accept=".pdf,application/pdf" required>
                        <div id="errorRegistroSocial" class="documento-error <?= isset(
                                                                                    $erroresDocumentos["registro_social"],
                                                                                )
                                                                                    ? "visible"
                                                                                    : "" ?>"><?= htmlspecialchars(
                                                                                                    $erroresDocumentos["registro_social"] ?? "",
                                                                                                    ENT_QUOTES,
                                                                                                    "UTF-8",
                                                                                                ) ?></div>
                    </div>
                </div>


            </section>


            <section class="form-seccion">


                <div class="confirmacion-postulacion">


                    <label class="checkbox-confirmacion">


                        <input
                            type="checkbox"
                            id="confirmar_datos"
                            required>


                        <span>

                            Confirmo que los datos ingresados son
                            correctos y que los documentos adjuntados
                            corresponden a los documentos solicitados

                        </span>


                    </label>


                </div>


            </section>


            <div class="formulario-acciones">

                <a
                    href="postulacion.php"
                    class="btn-formulario-secundario">
                    Cancelar
                </a>


                <button
                    type="submit"
                    id="btnEnviarPostulacion"
                    class="btn-formulario-principal">
                    Enviar postulación
                </button>

            </div>


        </form>


    </main>

    <footer class="footer">

        <p>
            © <?= date("Y") ?> Becas Estudiantiles
        </p>

    </footer>


    <script src="../js/script.js"></script>


    <script>
        document.addEventListener(
            "DOMContentLoaded",


            function() {

                const rutNumero =
                    document.getElementById(
                        "rut_numero"
                    );

                const rutDv =
                    document.getElementById(
                        "rut_dv"
                    );

                const rutCompleto =
                    document.getElementById(
                        "rut"
                    );

                const rutEstado =
                    document.getElementById(
                        "rutEstado"
                    );

                const rutMensaje =
                    document.getElementById(
                        "rutMensaje"
                    );


                function limpiarNumeroRut(valor) {

                    return valor.replace(
                        /\D/g,
                        ""
                    );
                }

                function formatearNumeroRut(valor) {

                    let numero =
                        limpiarNumeroRut(
                            valor
                        );


                    if (numero === "") {

                        return "";
                    }


                    return Number(
                        numero
                    ).toLocaleString(
                        "es-CL"
                    );
                }

                function calcularDvRut(numero) {

                    let suma = 0;

                    let multiplicador = 2;


                    for (
                        let i = numero.length - 1; i >= 0; i--
                    ) {

                        suma +=
                            parseInt(
                                numero.charAt(i),
                                10
                            ) *
                            multiplicador;


                        multiplicador++;


                        if (
                            multiplicador > 7
                        ) {

                            multiplicador = 2;
                        }
                    }


                    const resultado =
                        11 - (
                            suma % 11
                        );


                    if (
                        resultado === 11
                    ) {

                        return "0";
                    }


                    if (
                        resultado === 10
                    ) {

                        return "K";
                    }


                    return String(
                        resultado
                    );
                }

                function actualizarRutCompleto() {

                    const numero =
                        limpiarNumeroRut(
                            rutNumero.value
                        );


                    const dv =
                        rutDv.value
                        .trim()
                        .toUpperCase();


                    if (
                        numero === "" ||
                        dv === ""
                    ) {

                        rutCompleto.value =
                            "";

                        return;
                    }


                    rutCompleto.value =
                        formatearNumeroRut(
                            numero
                        ) +
                        "-" +
                        dv;
                }

                function validarRutFormulario() {

                    const numero =
                        limpiarNumeroRut(
                            rutNumero.value
                        );


                    const dvIngresado =
                        rutDv.value
                        .trim()
                        .toUpperCase();


                    actualizarRutCompleto();


                    if (
                        numero.length < 7 ||
                        numero.length > 8
                    ) {

                        rutEstado.textContent =
                            "✕";

                        rutEstado.className =
                            "rut-estado invalido";


                        rutMensaje.textContent =
                            "El número del RUT no es válido.";


                        return false;
                    }


                    if (
                        !/^[0-9K]$/.test(
                            dvIngresado
                        )
                    ) {

                        rutEstado.textContent =
                            "✕";

                        rutEstado.className =
                            "rut-estado invalido";


                        rutMensaje.textContent =
                            "El dígito verificador no es válido.";


                        return false;
                    }


                    const dvCorrecto =
                        calcularDvRut(
                            numero
                        );


                    if (
                        dvCorrecto !==
                        dvIngresado
                    ) {

                        rutEstado.textContent =
                            "✕";

                        rutEstado.className =
                            "rut-estado invalido";


                        rutMensaje.textContent =
                            "El RUT ingresado no es válido.";


                        return false;
                    }


                    rutEstado.textContent =
                        "✓";

                    rutEstado.className =
                        "rut-estado valido";


                    rutMensaje.textContent =
                        "RUT válido.";


                    return true;
                }

                rutNumero.addEventListener(
                    "input",
                    function() {

                        const numero =
                            limpiarNumeroRut(
                                this.value
                            )
                            .slice(
                                0,
                                8
                            );


                        this.value =
                            formatearNumeroRut(
                                numero
                            );


                        actualizarRutCompleto();


                        if (
                            rutDv.value !== ""
                        ) {

                            validarRutFormulario();
                        }


                        actualizarProgreso();

                    }
                );

                rutDv.addEventListener(
                    "input",
                    function() {

                        let valor =
                            this.value
                            .toUpperCase()
                            .replace(
                                /[^0-9K]/g,
                                ""
                            );


                        this.value =
                            valor.slice(
                                0,
                                1
                            );


                        actualizarRutCompleto();


                        if (
                            rutNumero.value !== "" &&
                            rutDv.value !== ""
                        ) {

                            validarRutFormulario();
                        }


                        actualizarProgreso();

                    }
                );

                rutNumero.addEventListener(
                    "blur",
                    function() {

                        if (
                            rutNumero.value !== "" &&
                            rutDv.value !== ""
                        ) {

                            validarRutFormulario();
                        }

                    }
                );


                rutDv.addEventListener(
                    "blur",
                    function() {

                        if (
                            rutNumero.value !== "" &&
                            rutDv.value !== ""
                        ) {

                            validarRutFormulario();
                        }

                    }
                );

                const telefono =
                    document.getElementById(
                        "telefono"
                    );


                telefono.addEventListener(
                    "input",
                    function() {

                        this.value =
                            this.value
                            .replace(
                                /\D/g,
                                ""
                            )
                            .slice(
                                0,
                                9
                            );


                        actualizarProgreso();

                    }
                );

                const MAX_FILE_SIZE =
                    5 * 1024 * 1024;


                const formulario =
                    document.getElementById(
                        "formPostulacion"
                    );


                const banco =
                    document.getElementById(
                        "banco"
                    );


                const bancoOtroContenedor =
                    document.getElementById(
                        "bancoOtro"
                    );


                const bancoOtro =
                    document.getElementById(
                        "banco_otro"
                    );


                const botonEnviar =
                    document.getElementById(
                        "btnEnviarPostulacion"
                    );


                function actualizarBancoOtro() {


                    if (
                        banco.value === "otro"
                    ) {

                        bancoOtroContenedor
                            .classList
                            .add(
                                "visible"
                            );


                        bancoOtro.required =
                            true;

                    } else {

                        bancoOtroContenedor
                            .classList
                            .remove(
                                "visible"
                            );


                        bancoOtro.required =
                            false;

                    }

                }

                banco.addEventListener(
                    "change",
                    function() {


                        actualizarBancoOtro();


                        if (
                            banco.value !==
                            "otro"
                        ) {

                            bancoOtro.value =
                                "";
                        }


                        actualizarProgreso();

                    }
                );

                actualizarBancoOtro();

                const documentos = [{
                        input: document.getElementById("registro_social"),
                        error: document.getElementById("errorRegistroSocial"),
                        nombre: "Registro Social de Hogares"
                    },
                    {
                        input: document.getElementById("acreditacion_gratuidad"),
                        error: document.getElementById("errorGratuidad"),
                        nombre: "Acreditación de gratuidad"
                    },

                    {

                        input: document.getElementById(
                            "carnet"
                        ),

                        error: document.getElementById(
                            "errorCarnet"
                        ),

                        nombre: "Carnet de identidad"

                    },

                    {

                        input: document.getElementById(
                            "alumno_regular"
                        ),

                        error: document.getElementById(
                            "errorAlumno"
                        ),

                        nombre: "Certificado de alumno regular"

                    },

                    {

                        input: document.getElementById(
                            "certificado_notas"
                        ),

                        error: document.getElementById(
                            "errorNotas"
                        ),

                        nombre: "Certificado de notas"

                    }

                ];


                function mostrarErrorDocumento(
                    documento,
                    mensaje
                ) {


                    documento.error.textContent =
                        mensaje;


                    documento.error
                        .classList
                        .add(
                            "visible"
                        );

                }


                function limpiarErrorDocumento(
                    documento
                ) {


                    documento.error.textContent =
                        "";


                    documento.error
                        .classList
                        .remove(
                            "visible"
                        );

                }


                function validarDocumento(
                    documento
                ) {


                    const archivo =
                        documento.input.files[0];


                    limpiarErrorDocumento(
                        documento
                    );


                    if (!archivo) {

                        return false;
                    }

                    if (
                        archivo.size <= 0
                    ) {

                        mostrarErrorDocumento(
                            documento,
                            documento.nombre +
                            " está vacío."
                        );


                        documento.input.value =
                            "";


                        return false;
                    }


                    if (
                        archivo.size >
                        MAX_FILE_SIZE
                    ) {

                        mostrarErrorDocumento(
                            documento,
                            documento.nombre +
                            " supera el máximo permitido de 5 MB."
                        );


                        documento.input.value =
                            "";


                        return false;
                    }


                    const extension =
                        archivo.name
                        .split(".")
                        .pop()
                        .toLowerCase();


                    if (
                        extension !== "pdf"
                    ) {

                        mostrarErrorDocumento(
                            documento,
                            documento.nombre +
                            " debe estar en formato PDF."
                        );


                        documento.input.value =
                            "";


                        return false;
                    }


                    if (
                        archivo.type &&
                        archivo.type !==
                        "application/pdf"
                    ) {

                        mostrarErrorDocumento(
                            documento,
                            documento.nombre +
                            " no parece ser un archivo PDF válido."
                        );


                        documento.input.value =
                            "";


                        return false;
                    }


                    return true;

                }

                function actualizarProgreso() {


                    const camposTexto =
                        formulario.querySelectorAll(
                            'input[required]:not([type="radio"]):not([type="checkbox"]):not([type="file"]), select[required]'
                        );


                    const gruposRadio =
                        new Set();


                    formulario
                        .querySelectorAll(
                            'input[type="radio"][required]'
                        )
                        .forEach(
                            function(radio) {

                                gruposRadio.add(
                                    radio.name
                                );

                            }
                        );


                    const archivos =
                        formulario.querySelectorAll(
                            'input[type="file"][required]'
                        );


                    const checks =
                        formulario.querySelectorAll(
                            'input[type="checkbox"][required]'
                        );


                    let total =
                        camposTexto.length +
                        gruposRadio.size +
                        archivos.length +
                        checks.length;


                    let completados = 0;

                    camposTexto.forEach(
                        function(campo) {


                            if (
                                campo.value.trim() !==
                                ""
                            ) {

                                completados++;
                            }

                        }
                    );

                    gruposRadio.forEach(
                        function(nombre) {


                            if (
                                formulario.querySelector(
                                    'input[name="' +
                                    nombre +
                                    '"]:checked'
                                )
                            ) {

                                completados++;
                            }

                        }
                    );

                    archivos.forEach(
                        function(campo) {


                            if (
                                campo.files &&
                                campo.files.length > 0
                            ) {

                                completados++;
                            }

                        }
                    );


                    checks.forEach(
                        function(campo) {


                            if (campo.checked) {

                                completados++;
                            }

                        }
                    );


                    let porcentaje = 0;


                    if (total > 0) {

                        porcentaje =
                            Math.round(
                                (
                                    completados /
                                    total
                                ) *
                                100
                            );
                    }


                    porcentaje =
                        Math.max(
                            0,
                            Math.min(
                                porcentaje,
                                100
                            )
                        );


                    document
                        .getElementById(
                            "progreso"
                        )
                        .style
                        .width =
                        porcentaje +
                        "%";


                    document
                        .getElementById(
                            "porcentajeProgreso"
                        )
                        .textContent =
                        porcentaje +
                        "%";

                }


                formulario.addEventListener(
                    "input",
                    actualizarProgreso
                );


                formulario.addEventListener(
                    "change",
                    actualizarProgreso
                );


                actualizarProgreso();

                function mostrarResultadoDocumento(
                    documento,
                    resultado
                ) {


                    documento.error.textContent =
                        "";


                    documento.error.classList.remove(
                        "visible",
                        "documento-correcto",
                        "documento-advertencia"
                    );


                    if (
                        resultado.estado ===
                        "correcto"
                    ) {

                        documento.error.textContent =
                            "✓ " +
                            'PDF recibido con contenido. Los datos del documento serán revisados por el personal municipal.';


                        documento.error.classList.add(
                            "visible",
                            "documento-correcto"
                        );


                        return;
                    }


                    if (
                        resultado.estado ===
                        "no_verificable"
                    ) {

                        documento.error.textContent =
                            "⚠ " +
                            resultado.mensaje;


                        documento.error.classList.add(
                            "visible",
                            "documento-advertencia"
                        );


                        return;
                    }

                    documento.error.textContent =
                        "✕ " +
                        resultado.mensaje;


                    documento.error.classList.add(
                        "visible"
                    );

                }


                const camposVerificacion = ['rut', 'nombres', 'apellido_paterno', 'apellido_materno', 'carrera', 'nombre_establecimiento', 'promedio_ultimo_semestre', 'tiene_gratuidad'];
                const contextoActual = () => JSON.stringify(camposVerificacion.map(nombre => formulario.elements.namedItem(nombre)?.value.trim() || ''));

                function actualizarEnvio() {
                    botonEnviar.disabled = !documentos.every(d => d.verificado);
                    botonEnviar.textContent = documentos.some(d => d.verificando) ? 'Verificando documentos...' : 'Enviar postulación';
                }

                function invalidar(documento) {
                    documento.version++;
                    documento.verificado = false;
                    documento.verificando = false;
                    documento.boton.disabled = false;
                    documento.boton.textContent = 'Verificar documento';
                    mostrarResultadoDocumento(documento, {
                        estado: 'pendiente',
                        mensaje: 'Documento pendiente de verificación.'
                    });
                    actualizarEnvio();
                }
                async function verificarDocumentoIndividual(documento) {
                    invalidar(documento);
                    if (!validarDocumento(documento)) return;
                    const datos = new FormData(formulario);
                    for (const otro of documentos)
                        if (otro !== documento) datos.delete(otro.input.name);
                    datos.set('documento', documento.input.name);
                    const version = documento.version;
                    const contexto = contextoActual();
                    documento.verificando = true;
                    documento.boton.disabled = true;
                    documento.boton.textContent = 'Verificando...';
                    mostrarResultadoDocumento(documento, {
                        estado: 'no_verificable',
                        mensaje: 'Verificando el contenido del PDF. Espera el resultado.'
                    });
                    actualizarEnvio();
                    try {
                        const respuesta = await fetch('verificar_documentos.php', {
                            method: 'POST',
                            body: datos
                        });
                        const cuerpo = await respuesta.text();
                        let resultado;
                        try {
                            resultado = JSON.parse(cuerpo);
                        } catch (_) {
                            throw new Error('El servidor no pudo completar la verificación (HTTP ' + respuesta.status + '). Intenta nuevamente; si persiste, informa al administrador.');
                        }
                        if (version !== documento.version || contexto !== contextoActual()) return;
                        if (!respuesta.ok || !resultado.ok) throw new Error(resultado.mensaje || 'No fue posible verificar el documento.');
                        const revision = resultado.documentos[documento.input.name];
                        documento.verificado = revision.estado === 'correcto';
                        mostrarResultadoDocumento(documento, revision);
                        if (documento.verificado) {
                            clearTimeout(documento.vencimiento);
                            documento.vencimiento = setTimeout(() => invalidar(documento), 3500 * 1000);
                        }
                    } catch (error) {
                        if (version === documento.version) mostrarResultadoDocumento(documento, {
                            estado: 'error',
                            mensaje: error.message || 'Error de conexión. Vuelve a verificar el documento.'
                        });
                    } finally {
                        if (version === documento.version) {
                            documento.verificando = false;
                            documento.boton.disabled = false;
                            documento.boton.textContent = documento.verificado ? 'Verificar nuevamente' : 'Reintentar verificación';
                            actualizarEnvio();
                        }
                    }
                }
                documentos.forEach(documento => {
                    documento.version = 0;
                    documento.verificado = false;
                    documento.error.setAttribute('role', 'status');
                    documento.error.setAttribute('aria-live', 'polite');
                    documento.boton = document.createElement('button');
                    documento.boton.type = 'button';
                    documento.boton.className = 'btn-verificar-documento';
                    documento.boton.textContent = 'Verificar documento';
                    documento.boton.style.marginTop = '10px';
                    documento.error.insertAdjacentElement('afterend', documento.boton);
                    documento.boton.addEventListener('click', () => verificarDocumentoIndividual(documento));
                    documento.input.addEventListener('change', () => verificarDocumentoIndividual(documento));
                });
                let ultimoContexto = contextoActual();
                let revalidacionDocumentos;

                function revisarCambios() {
                    const actual = contextoActual();
                    if (actual !== ultimoContexto) {
                        ultimoContexto = actual;
                        documentos.forEach(invalidar);
                        clearTimeout(revalidacionDocumentos);
                        revalidacionDocumentos = setTimeout(() => {
                            documentos.filter(d => d.input.files.length > 0).forEach(verificarDocumentoIndividual);
                        }, 600);
                    }
                }
                formulario.addEventListener('input', revisarCambios);
                formulario.addEventListener('change', revisarCambios);
                actualizarEnvio();
                formulario.addEventListener('submit', function(event) {
                    revisarCambios();
                    if (!validarRutFormulario() || !documentos.every(d => d.verificado)) {
                        event.preventDefault();
                        alert('Verifica correctamente los cinco documentos antes de enviar la postulación.');
                        return;
                    }
                    if (!/^[0-9]{9}$/.test(telefono.value)) {
                        event.preventDefault();
                        alert('El teléfono debe contener exactamente 9 dígitos.');
                        telefono.focus();
                        return;
                    }
                    if (banco.value === 'otro' && bancoOtro.value.trim() === '') {
                        event.preventDefault();
                        bancoOtro.focus();
                        return;
                    }
                    botonEnviar.disabled = true;
                    botonEnviar.textContent = 'Enviando postulación...';
                });

            }
        );
    </script>


</body>

</html>