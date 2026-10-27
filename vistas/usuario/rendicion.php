<?php

/**
 * Presentación de rendicion.
 * Los datos se preparan en el controlador; se escapan al incorporarlos al HTML.
 * @var string|null $mensajeAnioProceso
 * @var DateTime|false|null $fechaResultados
 */

$datosUsuario = isset($datosUsuario) && is_array($datosUsuario) ? $datosUsuario : [];

$rendicion = isset($rendicion) && is_array($rendicion) ? $rendicion : [];

$proceso = isset($proceso) && is_array($proceso) ? $proceso : [];

$gastos = isset($gastos) && is_array($gastos) ? $gastos : [];

$documentosRendicion =
    isset($documentosRendicion) && is_array($documentosRendicion) ? $documentosRendicion : [];

$categorias = isset($categorias) && is_array($categorias) ? $categorias : [];

$mensajeExito = isset($mensajeExito) ? $mensajeExito : null;

$mensajeError = isset($mensajeError) ? $mensajeError : null;

$erroresGastos = isset($erroresGastos) && is_array($erroresGastos) ? $erroresGastos : [];

$rutSesion = isset($rutSesion) ? (string) $rutSesion : "";

$nombreCompleto = isset($nombreCompleto) ? (string) $nombreCompleto : "";

$montoAnual = isset($montoAnual) ? (float) $montoAnual : 0;

$montoTotal = isset($montoTotal) ? (float) $montoTotal : 0;

$totalAnual = isset($totalAnual) ? (float) $totalAnual : 0;

$totalGeneral = isset($totalGeneral) ? (float) $totalGeneral : 0;

$huboErrorFormulario = isset($huboErrorFormulario) ? (bool) $huboErrorFormulario : false;

$tieneDevolucionFormulario = isset($tieneDevolucionFormulario)
    ? (bool) $tieneDevolucionFormulario
    : false;

$aceptaDeclaracionFormulario = isset($aceptaDeclaracionFormulario)
    ? (bool) $aceptaDeclaracionFormulario
    : false;

$confirmacionFormulario = isset($confirmacionFormulario) ? (bool) $confirmacionFormulario : false;

$esRendicionNueva = isset($esRendicionNueva) ? (bool) $esRendicionNueva : false;

$esRendicionAprobada = isset($esRendicionAprobada) ? (bool) $esRendicionAprobada : false;

$esRendicionRechazada = isset($esRendicionRechazada) ? (bool) $esRendicionRechazada : false;

$estaRendicionEnRevision = isset($estaRendicionEnRevision)
    ? (bool) $estaRendicionEnRevision
    : false;

$mostrarComoEnRevision = isset($mostrarComoEnRevision)
    ? (bool) $mostrarComoEnRevision
    : $estaRendicionEnRevision;

$resultadosRendicionDisponibles = isset($resultadosRendicionDisponibles)
    ? (bool) $resultadosRendicionDisponibles
    : false;

$fechaCierreRendicion = isset($fechaCierreRendicion) ? $fechaCierreRendicion : null;

$puedeEditar = isset($puedeEditar) ? (bool) $puedeEditar : false;

$estadoPlazoRendicion = isset($estadoPlazoRendicion)
    ? (string) $estadoPlazoRendicion
    : "sin_configurar";

$tieneInstrucciones = isset($tieneInstrucciones) ? (bool) $tieneInstrucciones : false;

$realizoDevolucion = isset($realizoDevolucion) ? (bool) $realizoDevolucion : false;

function valorDatoRendicion(array $datos, string $campo): string
{
    return (string) ($datos[$campo] ?? "");
}

function renderGastosFormulario(array $gastos, string $grupo, array $categorias): void
{
    if (empty($gastos)) {
        return;
    }

    foreach ($gastos as $indice => $gasto) {

        $fecha = $gasto["fecha_gasto"] ?? "";

        $categoria = $gasto["categoria"] ?? "";

        $categoriaOtro = $gasto["categoria_otro"] ?? "";

        $numero = $gasto["numero_documento"] ?? "";

        $monto = $gasto["monto"] ?? "";
        ?>

        <article
            class="rendicion-gasto-card"
            data-indice="<?= (int) $indice ?>">

            <div class="rendicion-gasto-encabezado">

                <h3>
                    Gasto <?= (int) $indice + 1 ?>
                </h3>

                <button
                    type="button"
                    class="btn-eliminar-gasto">
                    Eliminar
                </button>

            </div>


            <div class="form-grid">


                <div class="campo-form">

                    <label>
                        Fecha del gasto
                        <span class="obligatorio">*</span>
                    </label>

                    <input
                        type="date"
                        name="<?= e($grupo) ?>[<?= (int) $indice ?>][fecha]"
                        value="<?= e($fecha) ?>"
                        required>

                </div>


                <div class="campo-form">

                    <label>
                        Categoría
                        <span class="obligatorio">*</span>
                    </label>

                    <select
                        class="categoria-select"
                        name="<?= e($grupo) ?>[<?= (int) $indice ?>][categoria]"
                        required>

                        <option value="">
                            Selecciona
                        </option>

                        <?php foreach ($categorias as $valor => $texto): ?>

                            <option
                                value="<?= e($valor) ?>"
                                <?= $categoria === $valor ? "selected" : "" ?>>
                                <?= e($texto) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div
                    class="campo-form categoria-otro-container"
                    style="<?= $categoria === "otro" ? "" : "display:none;" ?>">

                    <label>
                        Especificar categoría
                        <span class="obligatorio">*</span>
                    </label>

                    <input
                        type="text"
                        class="categoria-otro-input"
                        name="<?= e($grupo) ?>[<?= (int) $indice ?>][categoria_otro]"
                        maxlength="150"
                        value="<?= e($categoriaOtro) ?>"
                        <?= $categoria === "otro" ? "required" : "" ?>>

                </div>

                <div class="campo-form">

                </div>


                <div class="campo-form">

                    <label>
                        Número de documento
                        <span class="obligatorio">*</span>
                    </label>

                    <input
                        type="text"
                        name="<?= e($grupo) ?>[<?= (int) $indice ?>][numero_documento]"
                        value="<?= e($numero) ?>"
                        maxlength="100"
                        required>

                </div>


                <div class="campo-form">

                    <label>
                        Monto
                        <span class="obligatorio">*</span>
                    </label>

                    <input
                        type="number"
                        name="<?= e($grupo) ?>[<?= (int) $indice ?>][monto]"
                        class="gasto-monto" placeholder="19990"
                        min="1"
                        step="1"
                        value="<?= e($monto) ?>"
                        required>

                </div>





                <div
                    class="
                        campo-form
                        form-grid-completo
                    ">

                    <label>
                        Boleta PDF
                        <span class="obligatorio">*</span>
                    </label>

                    <input
                        type="file"
                        class="gasto-boleta"
                        name="<?= e($grupo) ?>[<?= (int) $indice ?>][boleta]"
                        accept="application/pdf"
                        required>

                    <small class="campo-ayuda">

                        Solo PDF.
                        Máximo 5 MB.

                        <?php if (!empty($gasto["nombre_archivo_original"])): ?>

                            Archivo actual:
                            <?= e($gasto["nombre_archivo_original"]) ?>

                        <?php endif; ?>

                    </small>

                    <input
                        type="hidden"
                        class="gasto-verificacion-token"
                        name="<?= e($grupo) ?>[<?= (int) $indice ?>][verificacion]"
                        value="">

                    <div class="rendicion-verificacion-boleta">

                        <button
                            type="button"
                            class="btn-verificar-boleta">
                            Verificar boleta
                        </button>

                        <span class="estado-boleta pendiente">
                            Pendiente de verificación
                        </span>

                    </div>
                </div>


            </div>

        </article>

<?php
    }
}
?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Rendición | Becas Estudiantiles
    </title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=rendicion-estados-20261020">

</head>



<body class="estilo-rendicion">


    <header class="header">

        <div class="logo">
            Becas Estudiantiles
        </div>


        <?php require __DIR__ . "/menu.php"; ?>

    </header>


    <main class="portal-usuario">



        <div class="portal-encabezado portal-rendicion-header">

            <div>
                <h1>
                    Rendición de beca
                </h1>
        <?php require __DIR__ . "/../proceso_modulo.php"; ?>


                <div class="alerta-importante">

                    <div class="icono-alerta">
                        ⚠
                    </div>

                    <div class="contenido-alerta">

                        <h3>
                            Información importante
                        </h3>

                        <p>
                            Si debes realizar una devolución de dinero, primero debes acercarte a la municipalidad
                            para obtener el comprobante de depósito.
                            <br>
                            Luego podrás subir el comprobante a la plataforma para completar tu rendición.
                        </p>

                    </div>

                </div>

            </div>


            <div class="acciones-instrucciones">


                <a href="ver_instrucciones_rendicion.php" class="btn-formulario-secundario" aria-haspopup="dialog">Ver instrucciones</a>


                <a
                    href="descargar_instrucciones_rendicion.php"
                    class="btn-formulario-principal">
                    Descargar PDF
                </a>


            </div>


        </div>


        <?php if (!empty($mensajeExito)): ?>

            <div class="mensaje exito">

                <?= e($mensajeExito) ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($mensajeError)): ?>

            <div class="mensaje error">

                <?= e($mensajeError) ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($erroresGastos)): ?>

            <div class="mensaje error">

                <strong>
                    Revisa los siguientes gastos:
                </strong>

                <ul>

                    <?php foreach ($erroresGastos as $error): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <?php if (!empty($mensajeAnioProceso)): ?>
            <section class="portal-card" role="alert">
                <h2>Período de rendición no disponible</h2>
                <p><?= e($mensajeAnioProceso) ?></p>
                <a href="inicio.php" class="btn-portal">Volver al portal</a>
            </section>
        <?php elseif ($esRendicionNueva && $estadoPlazoRendicion === "no_iniciado"): ?>

            <section class="portal-card">

                <h2>
                    Rendición aún no disponible
                </h2>

                <p>
                    El periodo de rendición todavía
                    no ha comenzado.
                </p>

            </section>


        <?php elseif ($esRendicionNueva && $estadoPlazoRendicion === "vencido"): ?>

            <section class="portal-card">

                <h2>
                    Periodo finalizado
                </h2>

                <p>
                    El plazo para enviar la rendición
                    ha terminado.
                </p>

            </section>


        <?php elseif ($esRendicionNueva && $estadoPlazoRendicion === "sin_configurar"): ?>

            <section class="portal-card">

                <h2>
                    Periodo no configurado
                </h2>

                <p>
                    El administrador todavía no ha
                    configurado las fechas de rendición.
                </p>

            </section>


        <?php elseif ($puedeEditar): ?>


            <form
                action="procesar_rendicion.php"
                method="POST"
                enctype="multipart/form-data"
                id="formRendicion">

                <input
                    type="hidden"
                    name="rendicion_id"
                    value="<?= (int) ($rendicion["id"] ?? 0) ?>">


                <section class="rendicion-box">

                    <div class="rendicion-titulo-seccion">

                        <div class="form-seccion-numero">
                            I
                        </div>

                        <div>

                            <h2>
                                Identificación del beneficiario
                            </h2>

                            <p>
                                Completa tus datos personales.
                            </p>

                        </div>

                    </div>


                    <div class="form-grid">


                        <div class="campo-form">

                            <label for="rut">
                                RUT
                            </label>

                            <input
                                type="text"
                                id="rut"
                                value="<?= e($rutSesion) ?>"
                                readonly>

                        </div>


                        <div class="campo-form">

                            <label for="nombres">
                                Nombres
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="text"
                                name="nombres" readonly
                                id="nombres"
                                maxlength="150"
                                value="<?= e(valorDatoRendicion($datosUsuario, "nombres")) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="apellido_paterno">
                                Apellido paterno
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="text"
                                name="apellido_paterno" readonly
                                id="apellido_paterno"
                                maxlength="100"
                                value="<?= e(
                                    valorDatoRendicion($datosUsuario, "apellido_paterno"),
                                ) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="apellido_materno">
                                Apellido materno
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="text"
                                name="apellido_materno" readonly
                                id="apellido_materno"
                                maxlength="100"
                                value="<?= e(
                                    valorDatoRendicion($datosUsuario, "apellido_materno"),
                                ) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="fecha_nacimiento">
                                Fecha de nacimiento
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="date"
                                name="fecha_nacimiento" readonly
                                id="fecha_nacimiento"
                                value="<?= e(
                                    valorDatoRendicion($datosUsuario, "fecha_nacimiento"),
                                ) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="telefono">
                                Teléfono
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="text"
                                name="telefono" readonly
                                id="telefono"
                                maxlength="9"
                                minlength="9"
                                pattern="[0-9]{9}"
                                inputmode="numeric"
                                placeholder="912345678"
                                value="<?= e($datosUsuario["telefono"]) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="correo_electronico">
                                Correo electrónico
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="email"
                                name="correo_electronico" readonly
                                id="correo_electronico"
                                maxlength="150"
                                value="<?= e(
                                    valorDatoRendicion($datosUsuario, "correo_electronico"),
                                ) ?>"
                                required>

                        </div>


                        <div
                            class="
                            campo-form
                            form-grid-completo
                        ">

                            <label for="direccion">
                                Dirección
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="text"
                                name="direccion" readonly
                                id="direccion"
                                maxlength="300"
                                value="<?= e(valorDatoRendicion($datosUsuario, "direccion")) ?>"
                                required>

                        </div>


                    </div>

                </section>


                <section class="rendicion-box">

                    <div class="rendicion-titulo-seccion">

                        <div class="form-seccion-numero">
                            II
                        </div>

                        <div>

                            <h2>
                                Antecedentes educacionales
                            </h2>

                            <p>
                                Ingresa la información
                                correspondiente a tus estudios.
                            </p>

                        </div>

                    </div>


                    <div class="form-grid">


                        <div class="campo-form">

                            <label for="carrera">
                                Carrera
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="text"
                                name="carrera" readonly
                                id="carrera"
                                maxlength="200"
                                value="<?= e(valorDatoRendicion($datosUsuario, "carrera")) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="nombre_establecimiento">
                                Establecimiento
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="text"
                                name="nombre_establecimiento" readonly
                                id="nombre_establecimiento"
                                maxlength="250"
                                value="<?= e(
                                    valorDatoRendicion($datosUsuario, "nombre_establecimiento"),
                                ) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="region">
                                Región
                                <span class="obligatorio">*</span>
                                <small>Donde queda tu universidad o instituto</small>
                            </label>

                            <input
                                type="text"
                                name="region" readonly
                                id="region"
                                maxlength="150"
                                value="<?= e(valorDatoRendicion($datosUsuario, "region")) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="comuna">
                                Comuna
                                <span class="obligatorio">*</span>
                                <small>Donde queda tu universidad o instituto</small>
                            </label>

                            <input
                                type="text"
                                name="comuna" readonly
                                id="comuna"
                                maxlength="150"
                                value="<?= e(valorDatoRendicion($datosUsuario, "comuna")) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="tiene_gratuidad">
                                ¿Tiene gratuidad?
                                <span class="obligatorio">*</span>
                            </label>

                            <?php $gratuidad = valorDatoRendicion(
                                $datosUsuario,
                                "tiene_gratuidad",
                            ); ?>

                            <input type="hidden" name="tiene_gratuidad" value="<?= e(
                                $gratuidad,
                            ) ?>">
                            <select
                                name="tiene_gratuidad" disabled
                                id="tiene_gratuidad"
                                required>

                                <option value="">
                                    Selecciona
                                </option>

                                <option
                                    value="si"
                                    <?= $gratuidad === "si" ? "selected" : "" ?>>
                                    Sí
                                </option>

                                <option
                                    value="no"
                                    <?= $gratuidad === "no" ? "selected" : "" ?>>
                                    No
                                </option>

                            </select>

                        </div>


                        <div class="campo-form">

                            <label for="promedio_ultimo_semestre">
                                Promedio último semestre
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="number"
                                name="promedio_ultimo_semestre" readonly
                                id="promedio_ultimo_semestre"
                                min="1"
                                max="7"
                                step="0.01"
                                value="<?= e(
                                    valorDatoRendicion($datosUsuario, "promedio_ultimo_semestre"),
                                ) ?>"
                                required>

                        </div>


                        <div class="campo-form">

                            <label for="anio_cursa">
                                Año que cursa
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="number"
                                name="anio_cursa"
                                id="anio_cursa"
                                min="1"
                                max="10"
                                value="<?= e($rendicion["anio_cursa"] ?? "") ?>"
                                required>

                        </div>


                    </div>

                </section>


                <section class="rendicion-box">

                    <div class="rendicion-titulo-seccion">

                        <div class="form-seccion-numero">
                            III
                        </div>

                        <div>

                            <h2>
                                Rendición anual
                            </h2>

                            <p>
                                Revisa el monto anual de tu beca y registra todos los gastos realizados durante el periodo.
                            </p>

                        </div>

                    </div>

                    <div class="aviso-documentos"><div class="aviso-documentos-icono" aria-hidden="true">⚠</div><p>Ingresa los montos sin puntos ni comas. El número debe contener solo dígitos.</p></div>
<div class="form-grid">

                        <div class="campo-form">

                            <label for="monto_anual">
                                Monto recibido anual
                                <span class="obligatorio">*</span>
                            </label>

                            <input
                                type="number"
                                name="monto_anual" readonly
                                id="monto_anual"
                                min="1"
                                step="1"
                                value="<?= e($rendicion["monto_anual"] ?? ($montoAnual ?? "")) ?>"
                                required>

                        </div>

                    </div>

                    <div
                        class="rendicion-gastos-lista"
                        id="gastos">

                        <?php renderGastosFormulario($gastos, "gastos", $categorias); ?>

                    </div>

                    <button
                        type="button"
                        class="
                        btn-formulario-secundario
                        btn-agregar-gasto
                        "
                        data-destino="anual">
                        + Agregar gasto
                    </button>

                    <div class="rendicion-total-semestre">

                        <span>
                            Total gastos:
                        </span>

                        <strong id="totalAnual">
                            $<?= number_format($totalAnual, 0, ",", ".") ?>
                        </strong>

                    </div>

                </section>


                <section class="rendicion-box">

                    <div class="rendicion-titulo-seccion">

                        <div class="form-seccion-numero">
                            IV
                        </div>

                        <div>

                            <h2>
                                Devolución de saldo
                            </h2>

                            <p>
                                Si el total de tus gastos es menor al monto anual recibido,
                                debes devolver la diferencia.
                            </p>

                        </div>

                    </div>


                    <div
                        id="avisoDevolucion"
                        style="
                            margin-bottom:20px;
                            padding:16px 18px;
                            background:#eff6ff;
                            border:1px solid #bfdbfe;
                            border-radius:12px;
                            color:#1e3a8a;
                        ">

                        <strong id="textoAvisoDevolucion">
                            El sistema calculará automáticamente si corresponde
                            realizar una devolución.
                        </strong>

                    </div>


                    <label class="rendicion-check">

                        <input
                            type="checkbox"
                            name="tiene_devolucion"
                            id="tieneDevolucion"
                            value="1"
                            <?= $tieneDevolucionFormulario ? "checked" : "" ?>>

                        Realicé una devolución de saldo.

                    </label>


                    <div
                        id="camposDevolucion"
                        style="<?= $tieneDevolucionFormulario ? "" : "display:none;" ?>">

                        <div class="form-grid">

                            <div class="campo-form">

                                <label for="monto_devuelto">
                                    Monto a devolver
                                    <span class="obligatorio">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="monto_devuelto"
                                    id="monto_devuelto" placeholder="19990"
                                    min="1"
                                    step="1"
                                    value="<?= e($rendicion["monto_devuelto"] ?? "") ?>">

                                <small class="campo-ayuda">
                                    Corresponde al saldo entre el monto anual recibido y el total de gastos.
                                </small>

                            </div>


                            <div class="campo-form">

                                <label for="fecha_deposito">
                                    Fecha del depósito
                                    <span class="obligatorio">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="fecha_deposito"
                                    id="fecha_deposito"
                                    value="<?= e($rendicion["fecha_deposito_devolucion"] ?? "") ?>">

                            </div>


                            <div class="campo-form">

                                <label for="numero_comprobante_devolucion">
                                    Número de comprobante
                                    <span class="obligatorio">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="numero_comprobante_devolucion"
                                    id="numero_comprobante_devolucion"
                                    maxlength="100"
                                    value="<?= e(
                                        $rendicion["numero_comprobante_devolucion"] ?? "",
                                    ) ?>">

                            </div>


                            <div class="campo-form">

                                <label for="comprobante_devolucion">
                                    Comprobante PDF
                                    <span class="obligatorio">*</span>
                                </label>

                                <input
                                    type="file"
                                    name="comprobante_devolucion"
                                    id="comprobante_devolucion"
                                    accept="application/pdf">

                                <small class="campo-ayuda">
                                    Solo PDF, máximo 5 MB.
                                </small>

                            </div>

                        </div>


                        <input
                            type="hidden"
                            name="token_comprobante_devolucion"
                            id="tokenComprobanteDevolucion"
                            value="">


                        <div
                            class="rendicion-verificacion-boleta"
                            style="margin-top:16px;">

                            <button
                                type="button"
                                class="btn-verificar-boleta"
                                id="btnVerificarComprobanteDevolucion">
                                Verificar comprobante
                            </button>

                            <span
                                class="estado-boleta pendiente"
                                id="estadoComprobanteDevolucion">
                                Pendiente de verificación
                            </span>

                        </div>

                    </div>

                </section>


                <section class="rendicion-box">

                    <div class="rendicion-titulo-seccion">

                        <div class="form-seccion-numero">
                            V
                        </div>

                        <div>

                            <h2>
                                Declaración jurada
                            </h2>

                        </div>

                    </div>


                    <div class="rendicion-declaracion">

                        <p>
                            Yo,
                            <strong id="nombreDeclaracion"><?= e(
                                $nombreCompleto !== "" ? $nombreCompleto : "beneficiario/a",
                            ) ?></strong>,
                            RUT
                            <strong><span class="rut-completo"><?= e($rutSesion) ?></span></strong>,
                            declaro que toda la información ingresada en esta rendición es verdadera
                            y que los documentos adjuntados corresponden a gastos efectivamente realizados.
                        </p>


                        <label class="rendicion-check">

                            <input
                                type="checkbox"
                                name="acepta_declaracion"
                                value="1"
                                <?= $aceptaDeclaracionFormulario ? "checked" : "" ?>
                                required>

                            Declaro que he leído y acepto
                            la declaración anterior.

                        </label>

                    </div>

                </section>


                <section class="rendicion-confirmacion">

                    <label class="rendicion-check">

                        <input
                            type="checkbox"
                            name="confirmacion"
                            value="1"
                            <?= $confirmacionFormulario ? "checked" : "" ?>
                            required>

                        Confirmo que revisé todos los
                        antecedentes antes de enviar
                        definitivamente la rendición.

                    </label>

                </section>


                <div class="formulario-acciones">


                    <a
                        href="../auth/cerrar_sesion.php"
                        class="btn-formulario-secundario">
                        Salir
                    </a>


                    <button
                        type="submit"
                        class="btn-formulario-principal">
                        Enviar rendición
                    </button>

                </div>


            </form>


        <?php elseif (
            $resultadosRendicionDisponibles &&
            ($esRendicionAprobada || $esRendicionRechazada)
        ): ?>

            <section class="portal-card" style="max-width:700px;margin:0 auto;text-align:center;">

                <div style="
                        width:90px;
                        height:90px;
                        border-radius:50%;
                        background:<?= $esRendicionAprobada ? "#dcfce7" : "#fee2e2" ?>;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        margin:0 auto 20px;
                        font-size:50px;
                        font-weight:bold;
                        color:<?= $esRendicionAprobada ? "#16a34a" : "#dc2626" ?>;
                    ">
                    <?= $esRendicionAprobada ? "✓" : "×" ?>
                </div>

                <h2>
                    <?= $esRendicionAprobada ? "Rendición aprobada" : "Rendición rechazada" ?>
                </h2>

                <p>
                    <?= $esRendicionAprobada
                        ? "Tu rendición fue revisada y aprobada correctamente."
                        : "Tu rendición no fue aprobada. Revisa las observaciones realizadas por el administrador." ?>
                </p>

                <?php if (!empty($rendicion["fecha_revision"])): ?>
                    <p>
                        <strong>Fecha de revisión:</strong>
                        <?= e(date("d/m/Y H:i", strtotime($rendicion["fecha_revision"]))) ?>
                    </p>
                <?php endif; ?>

                <?php if (trim((string) ($rendicion["observaciones"] ?? "")) !== ""): ?>
                    <div class="mensaje" style="margin-top:20px;text-align:left;">
                        <strong>Observación general</strong>
                        <p><?= nl2br(e($rendicion["observaciones"])) ?></p>
                    </div>
                <?php endif; ?>

                <div style="margin-top:25px;">
                    <a href="comprobante_rendicion.php"
                        class="btn-formulario-principal"
                        style="text-decoration:none;">
                        Ver comprobante
                    </a>
                </div>

            </section>

            <?php
            $boletas = [];

            foreach ($documentosRendicion as $documento):
                $tituloDocumento = mb_strtolower(
                    trim((string) ($documento["titulo"] ?? "")),
                    "UTF-8",
                );

                $tituloNormalizado = str_replace(
                    ["á", "é", "í", "ó", "ú", "ü", "ñ"],
                    ["a", "e", "i", "o", "u", "u", "n"],
                    $tituloDocumento,
                );

                if (
                    str_contains($tituloNormalizado, "devolucion") ||
                    str_contains($tituloNormalizado, "comprobante de devolucion")
                ) {
                    continue;
                }

                $boletas[] = $documento;
            endforeach;

            $tieneDevolucion =
                !empty($rendicion["devolucion_nombre_original"] ?? "") ||
                !empty($rendicion["devolucion_ruta"] ?? "") ||
                !empty($rendicion["monto_devuelto"] ?? "") ||
                !empty($rendicion["numero_comprobante_devolucion"] ?? "");

            $estadoDevolucion = strtolower(
                trim((string) ($rendicion["devolucion_estado_validacion"] ?? "pendiente")),
            );

            if ($estadoDevolucion === "") {
                $estadoDevolucion = "pendiente";
            }

            $observacionDevolucion = trim(
                (string) ($rendicion["devolucion_observacion_validacion"] ?? ""),
            );

            $nombreDevolucion = trim((string) ($rendicion["devolucion_nombre_original"] ?? ""));

            if ($nombreDevolucion === "") {
                $nombreDevolucion = "Comprobante de devolución";
            }
            ?>

            <section
                class="portal-card estado-documentos-usuario"
                style="
                        max-width:900px;
                        margin:28px auto 0;
                    ">

                <div style="margin-bottom:24px;">

                    <h2 style="margin-bottom:8px;">
                        Estado de tus boletas
                    </h2>

                    <p style="margin:0;color:#64748b;">
                        Revisa el resultado de la validación de las boletas
                        y documentos que adjuntaste en tu rendición.
                    </p>

                </div>

                <?php $seccionesBoletas = [
                    [
                        "titulo" => "Gastos de la rendición",
                        "boletas" => $boletas,
                    ],
                ]; ?>

                <?php foreach ($seccionesBoletas as $seccion): ?>

                    <?php if (!empty($seccion["boletas"])): ?>

                        <div style="margin-top:26px;">

                            <h3
                                style="
                                        margin:0 0 14px;
                                        font-size:20px;
                                        color:#1f2937;
                                    ">
                                <?= e($seccion["titulo"]) ?>
                            </h3>

                            <div
                                style="
                                        display:flex;
                                        flex-direction:column;
                                        gap:12px;
                                    ">

                                <?php foreach ($seccion["boletas"] as $documento): ?>

                                    <?php
                                    $estadoDocumento = strtolower(
                                        trim((string) ($documento["estado"] ?? "pendiente")),
                                    );

                                    if ($estadoDocumento === "") {
                                        $estadoDocumento = "pendiente";
                                    }

                                    $esAceptado = in_array(
                                        $estadoDocumento,
                                        ["aceptado", "aprobado", "validado"],
                                        true,
                                    );

                                    $esRechazado = in_array(
                                        $estadoDocumento,
                                        ["rechazado", "rechazada"],
                                        true,
                                    );

                                    $colorBorde = $esAceptado
                                        ? "#22c55e"
                                        : ($esRechazado
                                            ? "#ef4444"
                                            : "#f59e0b");

                                    $colorFondoIcono = $esAceptado
                                        ? "#dcfce7"
                                        : ($esRechazado
                                            ? "#fee2e2"
                                            : "#ffedd5");

                                    $colorIcono = $esAceptado
                                        ? "#15803d"
                                        : ($esRechazado
                                            ? "#dc2626"
                                            : "#c2410c");

                                    $textoEstado = $esAceptado
                                        ? "Aceptado"
                                        : ($esRechazado
                                            ? "Rechazado"
                                            : "Pendiente");

                                    $iconoEstado = $esAceptado ? "✓" : ($esRechazado ? "×" : "!");

                                    $observacionDocumento = trim(
                                        (string) ($documento["observacion"] ?? ""),
                                    );

                                    $fechaValidacion = trim(
                                        (string) ($documento["fecha_validacion"] ?? ""),
                                    );
                                    ?>

                                    <article class="boleta-resultado-card"
                                        style="
                                                background:#f8fafc;
                                                border:1px solid #dbe3ee;
                                                border-left:4px solid <?= $colorBorde ?>;
                                                border-radius:12px;
                                                padding:18px 20px;
                                                display:flex;
                                                align-items:center;
                                                gap:16px;
                                            ">

                                        <div class="boleta-resultado-icono" style="flex:0 0 48px;
                                                    width:48px;
                                                    height:48px;
                                                    border-radius:50%;
                                                    background:<?= $colorFondoIcono ?>;
                                                    color:<?= $colorIcono ?>;
                                                    display:flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    font-size:25px;
                                                    font-weight:700;
                                                ">
                                            <?= e($iconoEstado) ?>
                                        </div>

                                        <div class="boleta-resultado-info" style="flex:1;min-width:0;">

                                            <div class="boleta-resultado-encabezado" style="display:flex;justify-content:space-between;
                                                        align-items:center;
                                                        gap:15px;
                                                    ">

                                                <strong
                                                    style="
                                                            font-size:17px;
                                                            color:#172033;
                                                        ">
                                                    <?= e($documento["titulo"] ?? "Boleta") ?>
                                                </strong>

                                                <span class="boleta-resultado-estado" style="flex-shrink:0;
                                                            padding:7px 18px;
                                                            border-radius:999px;
                                                            background:<?= $colorFondoIcono ?>;
                                                            color:<?= $colorIcono ?>;
                                                            font-size:13px;
                                                            font-weight:700;
                                                        ">
                                                    <?= e($textoEstado) ?>
                                                </span>

                                            </div>

                                            <?php if (!empty($documento["nombre_archivo"])): ?>
                                                <div
                                                    style="
                                                            margin-top:5px;
                                                            color:#64748b;
                                                            font-size:14px;
                                                        ">
                                                    <?= e($documento["nombre_archivo"]) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($fechaValidacion !== ""): ?>
                                                <?php
                                                $fechaFormateada = $fechaValidacion;

                                                $timestampValidacion = strtotime($fechaValidacion);

                                                if ($timestampValidacion !== false) {
                                                    $fechaFormateada = date(
                                                        "d/m/Y H:i",
                                                        $timestampValidacion,
                                                    );
                                                }
                                                ?>

                                                <div
                                                    style="
                                                            margin-top:5px;
                                                            color:#94a3b8;
                                                            font-size:12px;
                                                        ">
                                                    Revisado el <?= e($fechaFormateada) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($documento["monto"])): ?>
                                                <div
                                                    style="
                                                            margin-top:7px;
                                                            color:#64748b;
                                                            font-size:14px;
                                                        ">
                                                    <strong>Monto:</strong>
                                                    $<?= number_format(
                                                        (float) $documento["monto"],
                                                        0,
                                                        ",",
                                                        ".",
                                                    ) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($observacionDocumento !== ""): ?>
                                                <div
                                                    style="
                                                            margin-top:12px;
                                                            padding:12px 14px;
                                                            background:#eef5ff;
                                                            border:1px solid #c7ddff;
                                                            border-radius:9px;
                                                        ">

                                                    <strong
                                                        style="
                                                                display:block;
                                                                margin-bottom:4px;
                                                                color:#1e3a8a;
                                                                font-size:14px;
                                                            ">
                                                        Observación
                                                    </strong>

                                                    <div
                                                        style="
                                                                color:#475569;
                                                                font-size:14px;
                                                                line-height:1.5;
                                                            ">
                                                        <?= nl2br(e($observacionDocumento)) ?>
                                                    </div>

                                                </div>
                                            <?php endif; ?>

                                        </div>

                                    </article>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

                <?php if ($tieneDevolucion): ?>

                    <div style="margin-top:30px;">

                        <h3
                            style="
                                    margin:0 0 14px;
                                    font-size:20px;
                                    color:#1f2937;
                                ">
                            Devolución de dinero
                        </h3>

                        <?php
                        $esDevolucionAceptada = in_array(
                            $estadoDevolucion,
                            ["aceptado", "aprobado", "validado"],
                            true,
                        );

                        $esDevolucionRechazada = in_array(
                            $estadoDevolucion,
                            ["rechazado", "rechazada"],
                            true,
                        );

                        $colorBordeDevolucion = $esDevolucionAceptada
                            ? "#22c55e"
                            : ($esDevolucionRechazada
                                ? "#ef4444"
                                : "#f59e0b");

                        $colorFondoDevolucion = $esDevolucionAceptada
                            ? "#dcfce7"
                            : ($esDevolucionRechazada
                                ? "#fee2e2"
                                : "#ffedd5");

                        $colorTextoDevolucion = $esDevolucionAceptada
                            ? "#15803d"
                            : ($esDevolucionRechazada
                                ? "#dc2626"
                                : "#c2410c");

                        $textoDevolucion = $esDevolucionAceptada
                            ? "Aceptado"
                            : ($esDevolucionRechazada
                                ? "Rechazado"
                                : "Pendiente");

                        $iconoDevolucion = $esDevolucionAceptada
                            ? "✓"
                            : ($esDevolucionRechazada
                                ? "×"
                                : "!");
                        ?>

                        <article class="boleta-resultado-card"
                            style="
                                    background:#f8fafc;
                                    border:1px solid #dbe3ee;
                                    border-left:4px solid <?= $colorBordeDevolucion ?>;
                                    border-radius:12px;
                                    padding:18px 20px;
                                    display:flex;
                                    align-items:center;
                                    gap:16px;
                                ">

                            <div class="boleta-resultado-icono" style="flex:0 0 48px;
                                        width:48px;
                                        height:48px;
                                        border-radius:50%;
                                        background:<?= $colorFondoDevolucion ?>;
                                        color:<?= $colorTextoDevolucion ?>;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        font-size:25px;
                                        font-weight:700;
                                    ">
                                <?= e($iconoDevolucion) ?>
                            </div>

                            <div class="boleta-resultado-info" style="flex:1;min-width:0;">

                                <div class="boleta-resultado-encabezado" style="display:flex;justify-content:space-between;
                                            align-items:center;
                                            gap:15px;
                                        ">

                                    <strong
                                        style="
                                                font-size:17px;
                                                color:#172033;
                                            ">
                                        Comprobante de devolución
                                    </strong>

                                    <span class="boleta-resultado-estado" style="flex-shrink:0;
                                                padding:7px 18px;
                                                border-radius:999px;
                                                background:<?= $colorFondoDevolucion ?>;
                                                color:<?= $colorTextoDevolucion ?>;
                                                font-size:13px;
                                                font-weight:700;
                                            ">
                                        <?= e($textoDevolucion) ?>
                                    </span>

                                </div>

                                <div
                                    style="
                                            margin-top:5px;
                                            color:#64748b;
                                            font-size:14px;
                                        ">
                                    <?= e($nombreDevolucion) ?>
                                </div>

                                <?php if (!empty($rendicion["monto_devuelto"])): ?>
                                    <div
                                        style="
                                                margin-top:7px;
                                                color:#64748b;
                                                font-size:14px;
                                            ">
                                        <strong>Monto devuelto:</strong>
                                        $<?= number_format(
                                            (float) $rendicion["monto_devuelto"],
                                            0,
                                            ",",
                                            ".",
                                        ) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($rendicion["numero_comprobante_devolucion"])): ?>
                                    <div
                                        style="
                                                margin-top:5px;
                                                color:#64748b;
                                                font-size:14px;
                                            ">
                                        <strong>N.º comprobante:</strong>
                                        <?= e($rendicion["numero_comprobante_devolucion"]) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($observacionDevolucion !== ""): ?>
                                    <div
                                        style="
                                                margin-top:12px;
                                                padding:12px 14px;
                                                background:#eef5ff;
                                                border:1px solid #c7ddff;
                                                border-radius:9px;
                                            ">

                                        <strong
                                            style="
                                                    display:block;
                                                    margin-bottom:4px;
                                                    color:#1e3a8a;
                                                    font-size:14px;
                                                ">
                                            Observación
                                        </strong>

                                        <div
                                            style="
                                                    color:#475569;
                                                    font-size:14px;
                                                    line-height:1.5;
                                                ">
                                            <?= nl2br(e($observacionDevolucion)) ?>
                                        </div>

                                    </div>
                                <?php endif; ?>

                            </div>

                        </article>

                    </div>

                <?php endif; ?>

            </section>

        <?php elseif ($mostrarComoEnRevision && !$resultadosRendicionDisponibles): ?>

            <section
                class="portal-card"
                style="
                text-align:center;
                max-width:900px;
                margin:0 auto;
                min-height:auto;
                ">

                <div
                    style="
                        text-align:center;
                        margin-bottom:25px;
                    ">

                    <div
                        style="
                            width:90px;
                            height:90px;
                            border-radius:50%;
                            background:#e8f1ff;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            margin:0 auto 20px;
                            font-size:45px;
                            ">

                        ⏳

                    </div>


                    <h2>
                        Rendición enviada
                    </h2>

                    <p>
                        Tu rendición fue enviada correctamente.
                    </p>

                </div>


                <?php if (isset($fechaResultados) && $fechaResultados instanceof DateTime): ?>

                    <div
                        style="
                            margin-top:25px;
                            padding:20px;
                            background:#eef5ff;
                            border:1px solid #c7ddff;
                            border-radius:15px;
                            ">

                        <p style="margin:0 0 10px;">
                            Publicación de resultados
                        </p>

                        <strong
                            style="
                                font-size:24px;
                                color:#1d4ed8;
                                ">

                            <?= e($fechaResultados->format("d/m/Y H:i")) ?>

                        </strong>

                    </div>

                <?php endif; ?>

                <div
                    style="
                    display:flex;
                    flex-direction:column;
                    align-items:center;
                    justify-content:center;
                    text-align:center;
                    width:100%;
                    margin-top:35px;
                ">

                    <a
                        href="comprobante_rendicion.php"
                        class="btn-formulario-principal"
                        style="
                    text-decoration:none;
                    ">

                        Ver comprobante

                    </a>

                </div>


            </section>


        <?php else: ?>

            <section
                class="portal-card"
                style="
                    max-width:700px;
                    margin:0 auto;
                    text-align:center;
                ">

                <?php
                $estadoActual = $rendicion["estado"] ?? "no_iniciada";

                $esEnviada = in_array($estadoActual, ["enviada", "en_revision"]);

                $esAprobada = $estadoActual === "aprobada";

                $esRechazada = $estadoActual === "rechazada";
                ?>


                <?php if ($esAprobada): ?>


                    <div
                        style="
                            width:90px;
                            height:90px;
                            border-radius:50%;
                            background:#dcfce7;
                            color:#16a34a;

                            display:flex;
                            align-items:center;
                            justify-content:center;

                            margin:0 auto 20px;

                            font-size:50px;
                            font-weight:bold;
                        ">
                        ✓
                    </div>


                    <h2>
                        Rendición aprobada
                    </h2>


                    <p>
                        Tu rendición fue revisada y aprobada correctamente.
                    </p>


                <?php elseif ($esRechazada): ?>


                    <div
                        style="
                            width:90px;
                            height:90px;
                            border-radius:50%;
                            background:#fee2e2;
                            color:#dc2626;

                            display:flex;
                            align-items:center;
                            justify-content:center;

                            margin:0 auto 20px;

                            font-size:50px;
                            font-weight:bold;
                        ">
                        ×
                    </div>


                    <h2>
                        Rendición rechazada
                    </h2>


                    <p>
                        Tu rendición no fue aprobada.
                        Revisa la observacion realizadas por el administrador.
                    </p>



                <?php else: ?>


                    <div
                        style="
                            width:90px;
                            height:90px;
                            border-radius:50%;
                            background:#e8f1ff;
                            color:#2563eb;

                            display:flex;
                            align-items:center;
                            justify-content:center;

                            margin:0 auto 20px;

                            font-size:45px;
                        ">
                        ⏳
                    </div>


                    <h2>
                        Rendición enviada
                    </h2>


                    <p>
                        Tu rendición fue enviada correctamente, sigue en proceso de revisión
                    </p>

                    <div
                        style="
                        display:flex;
                        flex-direction:column;
                        align-items:center;
                        justify-content:center;
                        text-align:center;
                        width:100%;
                        margin-top:35px;
                    ">

                        <a
                            href="comprobante_rendicion.php"
                            class="btn-formulario-principal"
                            style="
                        text-decoration:none;
                        ">

                            Ver comprobante

                        </a>

                    </div>


                <?php endif; ?>


            </section>

        <?php endif; ?>


    </main>


    <footer class="footer">

        <p>
            © <?= date("Y") ?>
            Becas Estudiantiles
        </p>

    </footer>


    <script>
        document.addEventListener(
            "DOMContentLoaded",
            function() {

                const categorias =
                    <?= json_encode(
                        $categorias,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                    ) ?>;


                const formRendicion =
                    document.getElementById(
                        "formRendicion"
                    );


                const contenedorGastos =
                    document.getElementById(
                        "gastos"
                    );

                if (formRendicion) {

                    formRendicion.addEventListener(
                        "submit",
                        function(event) {

                            const cards =
                                formRendicion.querySelectorAll(
                                    ".rendicion-gasto-card"
                                );

                            let faltante = null;

                            cards.forEach(
                                function(card) {

                                    const archivo =
                                        card.querySelector(
                                            ".gasto-boleta"
                                        );

                                    const token =
                                        card.querySelector(
                                            ".gasto-verificacion-token"
                                        );

                                    if (
                                        archivo &&
                                        archivo.files &&
                                        archivo.files.length > 0 &&
                                        token &&
                                        token.value.trim() === ""
                                    ) {

                                        if (!faltante) {
                                            faltante = card;
                                        }
                                    }
                                }
                            );

                            if (faltante) {

                                event.preventDefault();

                                alert(
                                    "Debes verificar cada boleta antes de enviar la rendición."
                                );

                                faltante.scrollIntoView({
                                    behavior: "smooth",
                                    block: "center"
                                });

                                return;
                            }

                            if (
                                tieneDevolucion &&
                                tieneDevolucion.checked &&
                                tokenDevolucionVerificar &&
                                tokenDevolucionVerificar.value.trim() === ""
                            ) {

                                event.preventDefault();

                                alert(
                                    "Debes verificar el comprobante de devolución antes de enviar la rendición."
                                );

                                if (estadoDevolucionVerificar) {
                                    estadoDevolucionVerificar.scrollIntoView({
                                        behavior: "smooth",
                                        block: "center"
                                    });
                                }
                            }
                        }
                    );
                }


                function escaparHtml(
                    valor
                ) {

                    const div =
                        document.createElement(
                            "div"
                        );

                    div.textContent =
                        String(
                            valor ?? ""
                        );

                    return div.innerHTML;
                }


                function opcionesCategorias() {

                    let html =
                        '<option value="">Selecciona</option>';

                    Object.entries(
                        categorias
                    ).forEach(
                        function([valor, texto]) {

                            html +=
                                '<option value="' +
                                escaparHtml(valor) +
                                '">' +
                                escaparHtml(texto) +
                                '</option>';
                        }
                    );

                    return html;
                }


                function crearCard(
                    prefijo,
                    indice
                ) {

                    const card =
                        document.createElement(
                            "article"
                        );

                    card.className =
                        "rendicion-gasto-card";

                    card.dataset.indice =
                        indice;

                    card.innerHTML = `

                <div class="rendicion-gasto-encabezado">

                    <h3>
                        Gasto ${indice + 1}
                    </h3>

                    <button
                        type="button"
                        class="btn-eliminar-gasto"
                    >
                        Eliminar
                    </button>

                </div>

                <div class="form-grid">

                    <div class="campo-form">

                        <label>
                            Fecha del gasto
                            <span class="obligatorio">*</span>
                        </label>

                        <input
                            type="date"
                            name="${prefijo}[${indice}][fecha]"
                            required
                        >

                    </div>

                    <div class="campo-form">

                        <label>
                            Categoría
                            <span class="obligatorio">*</span>
                        </label>

                        <select
                            class="categoria-select"
                            name="${prefijo}[${indice}][categoria]"
                            required
                        >
                            ${opcionesCategorias()}
                        </select>

                    </div>

                    <div class="campo-form categoria-otro-container" style="display:none;">

                        <label>
                            Especificar categoría
                            <span class="obligatorio">*</span>
                        </label>

                        <input
                            type="text"
                            class="categoria-otro-input"
                            name="${prefijo}[${indice}][categoria_otro]"
                            maxlength="150"
                        >

                    </div>

                    <div class="campo-form">

                        <label>
                            Número de documento
                            <span class="obligatorio">*</span>
                        </label>

                        <input
                            type="text"
                            name="${prefijo}[${indice}][numero_documento]"
                            maxlength="100"
                            required
                        >

                    </div>

                    <div class="campo-form">

                        <label>
                            Monto
                            <span class="obligatorio">*</span>
                        </label>

                        <input
                            type="number"
                            class="gasto-monto" placeholder="Ejemplo 19990"
                            name="${prefijo}[${indice}][monto]"
                            min="1"
                            step="1"
                            required
                        >

                    </div>



                    <div class="campo-form form-grid-completo">

                        <label>
                            Boleta PDF
                            <span class="obligatorio">*</span>
                        </label>

                        <input
                            type="file"
                            class="gasto-boleta"
                            name="${prefijo}[${indice}][boleta]"
                            accept="application/pdf"
                            required
                        >

                        <small class="campo-ayuda">
                            Solo PDF, máximo 5 MB.
                        </small>

                        <input
                            type="hidden"
                            class="gasto-verificacion-token"
                            name="${prefijo}[${indice}][verificacion]"
                            value="">

                        <div class="rendicion-verificacion-boleta">

                            <button
                                type="button"
                                class="btn-verificar-boleta">
                                Verificar boleta
                            </button>

                            <span class="estado-boleta pendiente">
                                Pendiente de verificación
                            </span>

                        </div>
                    </div>

                </div>
            `;

                    activarCard(card);
                    activarCategoriaOtro(card);
                    activarVerificacionBoleta(card);

                    return card;
                }


                function activarCategoriaOtro(card) {

                    const select =
                        card.querySelector(".categoria-select");

                    const contenedor =
                        card.querySelector(".categoria-otro-container");

                    const input =
                        card.querySelector(".categoria-otro-input");


                    if (!select || !contenedor || !input) {
                        return;
                    }


                    function actualizar() {

                        if (select.value === "otro") {

                            contenedor.style.display = "";

                            input.required = true;

                        } else {

                            contenedor.style.display = "none";

                            input.required = false;

                            input.value = "";

                        }
                    }


                    select.addEventListener(
                        "change",
                        actualizar
                    );


                    actualizar();
                }


                function activarCard(
                    card
                ) {

                    const botonEliminar =
                        card.querySelector(
                            ".btn-eliminar-gasto"
                        );

                    if (botonEliminar) {

                        botonEliminar.addEventListener(
                            "click",
                            function() {

                                card.remove();
                                recalcularIndices();
                                calcularTotales();
                            }
                        );
                    }

                    card
                        .querySelectorAll(
                            ".gasto-monto"
                        )
                        .forEach(
                            function(input) {
                                input.addEventListener(
                                    "input",
                                    calcularTotales
                                );
                            }
                        );
                }


                function reindexarContenedor(
                    contenedor,
                    prefijo
                ) {

                    if (!contenedor) {
                        return;
                    }

                    const cards =
                        contenedor.querySelectorAll(
                            ".rendicion-gasto-card"
                        );

                    cards.forEach(
                        function(card, indice) {

                            card.dataset.indice = indice;

                            const titulo =
                                card.querySelector("h3");

                            if (titulo) {
                                titulo.textContent =
                                    "Gasto " + (indice + 1);
                            }

                            card
                                .querySelectorAll("[name]")
                                .forEach(
                                    function(campo) {

                                        const nombre =
                                            campo.getAttribute("name");

                                        if (!nombre) {
                                            return;
                                        }

                                        campo.setAttribute(
                                            "name",
                                            nombre.replace(
                                                /^([^\[]+)\[\d+\]/,
                                                prefijo + "[" + indice + "]"
                                            )
                                        );
                                    }
                                );
                        }
                    );
                }


                function recalcularIndices() {

                    reindexarContenedor(
                        contenedorGastos,
                        "gastos"
                    );
                }


                document
                    .querySelectorAll(
                        ".btn-agregar-gasto"
                    )
                    .forEach(
                        function(boton) {

                            boton.addEventListener(
                                "click",
                                function() {

                                    const indice =
                                        contenedorGastos
                                        .querySelectorAll(
                                            ".rendicion-gasto-card"
                                        )
                                        .length;

                                    contenedorGastos.appendChild(
                                        crearCard(
                                            "gastos",
                                            indice
                                        )
                                    );

                                    calcularTotales();
                                }
                            );
                        }
                    );


                function sumar(
                    contenedor
                ) {

                    if (!contenedor) {
                        return 0;
                    }

                    let total = 0;

                    contenedor
                        .querySelectorAll(".gasto-monto")
                        .forEach(
                            function(input) {

                                const valor =
                                    Number(input.value);

                                if (Number.isFinite(valor)) {
                                    total += valor;
                                }
                            }
                        );

                    return total;
                }


                function moneda(
                    valor
                ) {

                    return new Intl.NumberFormat(
                        "es-CL", {
                            style: "currency",
                            currency: "CLP",
                            maximumFractionDigits: 0
                        }
                    ).format(valor);
                }


                function calcularTotales() {

                    const total =
                        sumar(
                            contenedorGastos
                        );

                    const totalAnual =
                        document.getElementById(
                            "totalAnual"
                        );

                    if (totalAnual) {
                        totalAnual.textContent =
                            moneda(total);
                    }

                    actualizarSaldoDevolucion(total);
                }


                const tieneDevolucion =
                    document.getElementById(
                        "tieneDevolucion"
                    );


                const camposDevolucion =
                    document.getElementById(
                        "camposDevolucion"
                    );


                const montoDevolucion =
                    document.getElementById(
                        "monto_devuelto"
                    );


                const fechaDevolucion =
                    document.getElementById(
                        "fecha_deposito"
                    );


                const numeroDevolucion =
                    document.getElementById(
                        "numero_comprobante_devolucion"
                    );


                const archivoDevolucion =
                    document.getElementById(
                        "comprobante_devolucion"
                    );


                const montoAnualInput =
                    document.getElementById(
                        "monto_anual"
                    );

                const textoAvisoDevolucion =
                    document.getElementById(
                        "textoAvisoDevolucion"
                    );


                function actualizarSaldoDevolucion(
                    totalGastosRecibido = null
                ) {

                    if (
                        !montoAnualInput ||
                        !tieneDevolucion
                    ) {
                        return;
                    }

                    const montoAnual =
                        Number(
                            montoAnualInput.value
                        ) || 0;

                    const totalGastos =
                        totalGastosRecibido !== null ?
                        Number(totalGastosRecibido) || 0 :
                        sumar(contenedorGastos);

                    const saldo =
                        Math.max(
                            0,
                            montoAnual - totalGastos
                        );

                    if (saldo > 0) {

                        tieneDevolucion.checked = true;

                        if (montoDevolucion) {
                            montoDevolucion.value =
                                Math.round(saldo);
                        }

                        if (textoAvisoDevolucion) {
                            textoAvisoDevolucion.textContent =
                                "Debes realizar una devolución de " +
                                moneda(saldo) +
                                ", porque tus gastos son menores al monto anual recibido.";
                        }

                    } else {

                        tieneDevolucion.checked = false;

                        if (montoDevolucion) {
                            montoDevolucion.value = "";
                        }

                        if (textoAvisoDevolucion) {
                            textoAvisoDevolucion.textContent =
                                montoAnual > 0 ?
                                "No corresponde realizar una devolución porque el total de tus gastos alcanza o supera el monto anual recibido." :
                                "Ingresa el monto anual recibido para calcular si corresponde realizar una devolución.";
                        }
                    }

                    actualizarDevolucion();
                }


                function actualizarDevolucion() {

                    if (
                        !tieneDevolucion ||
                        !camposDevolucion
                    ) {

                        return;
                    }


                    const activo =
                        tieneDevolucion.checked;


                    camposDevolucion.style.display =
                        activo ?
                        "" :
                        "none";


                    [
                        montoDevolucion,
                        fechaDevolucion,
                        numeroDevolucion,
                        archivoDevolucion
                    ].forEach(
                        function(
                            campo
                        ) {

                            if (
                                campo
                            ) {

                                campo.required =
                                    activo;
                            }
                        }
                    );
                }


                if (
                    tieneDevolucion
                ) {

                    tieneDevolucion.addEventListener(
                        "change",
                        actualizarDevolucion
                    );


                    actualizarDevolucion();
                }


                if (montoAnualInput) {

                    montoAnualInput.addEventListener(
                        "input",
                        function() {
                            actualizarSaldoDevolucion();
                        }
                    );

                }


                const nombres =
                    document.getElementById(
                        "nombres"
                    );


                const apellidoPaterno =
                    document.getElementById(
                        "apellido_paterno"
                    );


                const apellidoMaterno =
                    document.getElementById(
                        "apellido_materno"
                    );


                const nombreDeclaracion =
                    document.getElementById(
                        "nombreDeclaracion"
                    );


                function actualizarNombre() {

                    if (
                        !nombreDeclaracion
                    ) {

                        return;
                    }


                    const nombre = [
                            nombres ?
                            nombres.value :
                            "",

                            apellidoPaterno ?
                            apellidoPaterno.value :
                            "",

                            apellidoMaterno ?
                            apellidoMaterno.value :
                            ""
                        ]
                        .join(
                            " "
                        )
                        .replace(
                            /\s+/g,
                            " "
                        )
                        .trim();


                    nombreDeclaracion.textContent =
                        nombre !== "" ?
                        nombre :
                        "beneficiario/a";
                }


                [
                    nombres,
                    apellidoPaterno,
                    apellidoMaterno
                ].forEach(
                    function(
                        campo
                    ) {

                        if (
                            campo
                        ) {

                            campo.addEventListener(
                                "input",
                                actualizarNombre
                            );
                        }
                    }
                );


                function nuevoTokenCliente() {
                    if (window.crypto && crypto.randomUUID) {
                        return crypto.randomUUID().replace(/-/g, "");
                    }
                    if (window.crypto && crypto.getRandomValues) {
                        const bytes = new Uint8Array(16);
                        crypto.getRandomValues(bytes);
                        return Array.from(
                            bytes,
                            function(byte) {
                                return byte.toString(16).padStart(2, "0");
                            }
                        ).join("");
                    }

                    return (
                        Date.now().toString(16) +
                        Math.random().toString(16).slice(2) +
                        Math.random().toString(16).slice(2)
                    ).replace(/[^a-f0-9]/gi, "").padEnd(32, "0");
                }


                function invalidarBoleta(card, mensaje) {
                    if (!card) return;
                    card.dataset.versionBoleta = String(Number(card.dataset.versionBoleta || 0) + 1);
                    const token = card.querySelector(".gasto-verificacion-token");
                    const estado = card.querySelector(".estado-boleta");
                    if (token) token.value = "";
                    if (estado) {
                        estado.textContent = mensaje || "Pendiente de verificación";
                        estado.className = "estado-boleta pendiente";
                    }
                    card.dataset.validado = "0";
                }


                async function verificarBoleta(card) {
                    invalidarBoleta(card);
                    const versionBoleta = card.dataset.versionBoleta;
                    const archivo = card.querySelector(".gasto-boleta");
                    const token = card.querySelector(".gasto-verificacion-token");
                    const boton = card.querySelector(".btn-verificar-boleta");
                    const estado = card.querySelector(".estado-boleta");
                    const rendicionId = document.querySelector('input[name="rendicion_id"]');

                    if (!archivo || !archivo.files || !archivo.files[0]) {
                        if (estado) {
                            estado.textContent = "Selecciona primero el PDF de la boleta.";
                            estado.className = "estado-boleta error";
                        }
                        return;
                    }

                    const pdf = archivo.files[0];
                    if (pdf.type !== "application/pdf") {
                        invalidarBoleta(card, "El archivo debe ser PDF.");
                        return;
                    }
                    if (pdf.size <= 0) {
                        invalidarBoleta(card, "El PDF está vacío.");
                        return;
                    }
                    if (pdf.size > <?= (int) (5 * 1024 * 1024) ?>) {
                        invalidarBoleta(card, "El PDF supera el máximo de 5 MB.");
                        return;
                    }

                    const tokenCliente = nuevoTokenCliente();
                    if (token) token.value = "";
                    if (estado) {
                        estado.textContent = "Verificando boleta...";
                        estado.className = "estado-boleta pendiente";
                    }
                    if (boton) boton.disabled = true;

                    const datos = new FormData();
                    datos.append("accion", "verificar_boleta");
                    datos.append("archivo", pdf);
                    datos.append("verificacion_id", tokenCliente);
                    datos.append("rendicion_id", rendicionId ? rendicionId.value : "0");

                    try {
                        const respuesta = await fetch("procesar_rendicion.php", {
                            method: "POST",
                            body: datos
                        });
                        const resultado = await respuesta.json();
                        if (card.dataset.versionBoleta !== versionBoleta) return;

                        if (resultado.ok && resultado.token) {
                            if (token) token.value = resultado.token;
                            if (estado) {
                                estado.textContent = "✓ " + (resultado.mensaje || "Boleta verificada correctamente.");
                                estado.className = "estado-boleta correcto";
                            }
                            card.dataset.validado = "1";
                        } else {
                            invalidarBoleta(card, "✕ " + (resultado.mensaje || "La boleta no pudo ser verificada."));
                        }
                    } catch (error) {
                        if (card.dataset.versionBoleta !== versionBoleta) return;
                        invalidarBoleta(card, "No fue posible verificar la boleta. Inténtalo nuevamente.");
                    } finally {
                        if (boton && archivo.files[0] === pdf) boton.disabled = false;
                    }
                }


                function activarVerificacionBoleta(card) {
                    if (!card) return;
                    const boton = card.querySelector(".btn-verificar-boleta");
                    const archivo = card.querySelector(".gasto-boleta");
                    if (!boton || !archivo) return;

                    boton.addEventListener("click", function() {
                        verificarBoleta(card);
                    });

                    [archivo].forEach(function(campo) {
                        campo.addEventListener("change", function() {
                            invalidarBoleta(card, "Pendiente de verificación");
                            boton.disabled = false;
                            if (archivo.files && archivo.files[0]) verificarBoleta(card);
                        });
                    });

                    card.querySelectorAll(".gasto-monto, input[name$='[numero_documento]']").forEach(function(campo) {
                        campo.addEventListener("input", function() {});
                    });
                }


                document.querySelectorAll(".rendicion-gasto-card").forEach(function(card) {
                    activarVerificacionBoleta(card);
                });


                const botonDevolucionVerificar =
                    document.getElementById("btnVerificarComprobanteDevolucion");
                const estadoDevolucionVerificar =
                    document.getElementById("estadoComprobanteDevolucion");
                const tokenDevolucionVerificar =
                    document.getElementById("tokenComprobanteDevolucion");

                let versionDevolucion = 0;
                let esperaVerificacionDevolucion;
                function invalidarVerificacionDevolucion(mensaje) {
                    versionDevolucion++;
                    if (tokenDevolucionVerificar) tokenDevolucionVerificar.value = "";
                    if (estadoDevolucionVerificar) {
                        estadoDevolucionVerificar.textContent = mensaje || "Pendiente de verificación";
                        estadoDevolucionVerificar.className = "estado-boleta pendiente";
                    }
                }


                async function verificarComprobanteDevolucion() {
                    if (!tieneDevolucion || !tieneDevolucion.checked) return;
                    invalidarVerificacionDevolucion();
                    const versionActualDevolucion = versionDevolucion;

                    const archivo = archivoDevolucion && archivoDevolucion.files ?
                        archivoDevolucion.files[0] :
                        null;

                    if (!montoDevolucion || !montoDevolucion.value || Number(montoDevolucion.value) <= 0) {
                        invalidarVerificacionDevolucion("Ingresa el monto devuelto.");
                        return;
                    }
                    if (!numeroDevolucion || !numeroDevolucion.value.trim()) {
                        invalidarVerificacionDevolucion("Ingresa el número de comprobante.");
                        return;
                    }
                    if (!archivo) {
                        invalidarVerificacionDevolucion("Selecciona primero el comprobante PDF.");
                        return;
                    }
                    if (archivo.type !== "application/pdf") {
                        invalidarVerificacionDevolucion("El archivo debe ser PDF.");
                        return;
                    }
                    if (archivo.size <= 0) {
                        invalidarVerificacionDevolucion("El PDF está vacío.");
                        return;
                    }
                    if (archivo.size > <?= (int) (5 * 1024 * 1024) ?>) {
                        invalidarVerificacionDevolucion("El PDF supera el máximo de 5 MB.");
                        return;
                    }

                    const rendicionId = document.querySelector('input[name="rendicion_id"]');
                    const tokenCliente = nuevoTokenCliente();
                    if (tokenDevolucionVerificar) tokenDevolucionVerificar.value = "";
                    if (estadoDevolucionVerificar) {
                        estadoDevolucionVerificar.textContent = "Verificando comprobante...";
                        estadoDevolucionVerificar.className = "estado-boleta pendiente";
                    }
                    if (botonDevolucionVerificar) botonDevolucionVerificar.disabled = true;

                    const datos = new FormData();
                    datos.append("accion", "verificar_comprobante_devolucion");
                    datos.append("archivo", archivo);
                    datos.append("monto", montoDevolucion.value);
                    datos.append("numero_comprobante", numeroDevolucion.value.trim());
                    datos.append("verificacion_id", tokenCliente);
                    datos.append("rendicion_id", rendicionId ? rendicionId.value : "0");

                    try {
                        const respuesta = await fetch("procesar_rendicion.php", {
                            method: "POST",
                            body: datos
                        });
                        const resultado = await respuesta.json();

                        if (versionActualDevolucion !== versionDevolucion) return;
                        if (resultado.ok && resultado.token) {
                            if (tokenDevolucionVerificar) tokenDevolucionVerificar.value = resultado.token;
                            if (estadoDevolucionVerificar) {
                                estadoDevolucionVerificar.textContent = "✓ " + (resultado.mensaje || "Comprobante verificado correctamente.");
                                estadoDevolucionVerificar.className = "estado-boleta correcto";
                            }
                        } else {
                            invalidarVerificacionDevolucion("✕ " + (resultado.mensaje || "El comprobante no pudo ser verificado."));
                        }
                    } catch (error) {
                        if (versionActualDevolucion !== versionDevolucion) return;
                        invalidarVerificacionDevolucion("No fue posible verificar el comprobante. Inténtalo nuevamente.");
                    } finally {
                        if (botonDevolucionVerificar && archivoDevolucion.files[0] === archivo) botonDevolucionVerificar.disabled = false;
                    }
                }

                if (botonDevolucionVerificar) {
                    botonDevolucionVerificar.addEventListener("click", verificarComprobanteDevolucion);
                }

                function programarVerificacionDevolucion() {
                    invalidarVerificacionDevolucion("Pendiente de verificación");
                    if (botonDevolucionVerificar) botonDevolucionVerificar.disabled = false;
                    clearTimeout(esperaVerificacionDevolucion);
                    if (archivoDevolucion?.files[0]) {
                        esperaVerificacionDevolucion = setTimeout(verificarComprobanteDevolucion, 500);
                    }
                }
                [montoDevolucion, numeroDevolucion, archivoDevolucion].forEach(function(campo) {
                    if (campo) {
                        campo.addEventListener("input", programarVerificacionDevolucion);
                        campo.addEventListener("change", programarVerificacionDevolucion);
                    }
                });

                document.addEventListener(
                    "change",
                    function(
                        event
                    ) {

                        const input =
                            event.target;


                        if (
                            !input.matches(
                                ".gasto-boleta, #comprobante_devolucion"
                            )
                        ) {

                            return;
                        }


                        const archivo =
                            input.files &&
                            input.files[0];


                        if (
                            !archivo
                        ) {

                            return;
                        }


                        const limite =
                            5 *
                            1024 *
                            1024;


                        if (
                            archivo.type !==
                            "application/pdf"
                        ) {

                            alert(
                                "El archivo debe ser PDF."
                            );


                            input.value =
                                "";

                            return;
                        }

                        if (
                            archivo.size >
                            limite
                        ) {

                            alert(
                                "El archivo no puede superar los 5 MB."
                            );


                            input.value =
                                "";
                        }
                    }
                );


                document
                    .querySelectorAll(
                        ".rendicion-gasto-card"
                    )
                    .forEach(
                        function(card) {
                            activarCard(card);
                            activarCategoriaOtro(card);
                        }
                    );


                recalcularIndices();

                calcularTotales();

                actualizarSaldoDevolucion();

                actualizarNombre();

            }
        );
    </script>


</body>

</html>
