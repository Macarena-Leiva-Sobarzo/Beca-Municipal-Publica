<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal para el estudiante | Becas Estudiantiles</title>
    <link rel="stylesheet" href="../css/estilos.css?v=mobile-20261020-2&solo-rendicion-20261019-4">
    <style>
        /* Regla local para conservar el orden de contenido en los teléfonos. */
        @media (max-width: 700px) {
            .portal-modulos-solo-rendicion .tarjeta-modulo-solo {
                display: grid !important;
                grid-template-rows: auto auto auto;
                align-content: start;
                gap: 0;
                min-height: 0 !important;
            }

            .portal-modulos-solo-rendicion .tarjeta-modulo-solo .descripcion-modulo-solo {
                position: static !important;
                display: block !important;
                float: none !important;
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                margin: 0 0 24px !important;
            }

            .portal-modulos-solo-rendicion .tarjeta-modulo-solo .boton-modulo-solo {
                position: static !important;
                display: inline-flex !important;
                float: none !important;
                width: 100% !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <header class="header"><div class="logo">Becas Estudiantiles</div><?php require __DIR__ . "/menu.php"; ?></header>
    <main class="portal-usuario">
        <div class="portal-encabezado">
            <h1>Portal para el estudiante</h1>
            <?php require __DIR__ . "/../proceso_modulo.php"; ?>
            <p>Desde este portal puedes entregar el certificado de alumno regular y realizar la rendición de tu beca.</p>
        </div>
        <div class="portal-grid portal-modulos-solo-rendicion">
            <section class="portal-card tarjeta-modulo-solo">
                <h2>Segundo semestre</h2>
                <p class="descripcion-modulo-solo">Sube tu certificado de alumno regular del segundo semestre en formato PDF.</p>
                <a class="btn-formulario-principal portal-enlace-modulo boton-modulo-solo" href="segundo_semestre.php">Subir certificado</a>
            </section>
            <section class="portal-card tarjeta-modulo-solo">
                <h2>Rendición de beca</h2>
                <p class="descripcion-modulo-solo">Registra los gastos, adjunta tus boletas y entrega el comprobante de devolución cuando corresponda.</p>
                <a class="btn-formulario-principal portal-enlace-modulo boton-modulo-solo" href="rendicion.php">Ir a rendición</a>
            </section>
        </div>
    </main>
    <footer class="footer"><p>© <?= date("Y") ?> Becas Estudiantiles</p></footer>
</body>
</html>
