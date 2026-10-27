document.addEventListener("DOMContentLoaded", function () {
    /* Carrusel */
    const carrusel = document.getElementById("carrusel");

    const btnAnterior = document.getElementById("btnAnterior");

    const btnSiguiente = document.getElementById("btnSiguiente");

    const indicadores = document.getElementById("indicadores");

    if (carrusel && btnAnterior && btnSiguiente) {
        const tarjetas = carrusel.querySelectorAll(".tarjeta");

        let indiceActual = 0;

        if (indicadores) {
            tarjetas.forEach(function (_, indice) {
                const indicador = document.createElement("div");

                indicador.classList.add("indicador");

                if (indice === 0) {
                    indicador.classList.add("activo");
                }

                indicador.addEventListener("click", function () {
                    indiceActual = indice;

                    desplazarCarrusel();
                });

                indicadores.appendChild(indicador);
            });
        }

        function actualizarIndicadores() {
            if (!indicadores) {
                return;
            }

            const elementos = indicadores.querySelectorAll(".indicador");

            elementos.forEach(function (elemento, indice) {
                elemento.classList.toggle("activo", indice === indiceActual);
            });
        }

        function desplazarCarrusel() {
            const ancho = carrusel.clientWidth;

            carrusel.scrollTo({
                left: ancho * indiceActual,

                behavior: "smooth",
            });

            actualizarIndicadores();
        }

        btnAnterior.addEventListener("click", function () {
            indiceActual--;

            if (indiceActual < 0) {
                indiceActual = tarjetas.length - 1;
            }

            desplazarCarrusel();
        });

        btnSiguiente.addEventListener("click", function () {
            indiceActual++;

            if (indiceActual >= tarjetas.length) {
                indiceActual = 0;
            }

            desplazarCarrusel();
        });

        carrusel.addEventListener("scroll", function () {
            const ancho = carrusel.clientWidth;

            if (ancho <= 0) {
                return;
            }

            const nuevoIndice = Math.round(carrusel.scrollLeft / ancho);

            if (nuevoIndice !== indiceActual) {
                indiceActual = nuevoIndice;

                actualizarIndicadores();
            }
        });
    }

    /* En caso de que sea otro banco */
    const banco = document.getElementById("banco");

    const bancoOtro = document.getElementById("bancoOtro");

    if (banco && bancoOtro) {
        banco.addEventListener("change", function () {
            if (banco.value === "otro") {
                bancoOtro.classList.add("visible");
            } else {
                bancoOtro.classList.remove("visible");
            }
        });
    }

    /* La validación del RUT */
    const rut = document.getElementById("rut");

    const rutEstado = document.getElementById("rutEstado");

    if (rut && rutEstado) {
        rut.addEventListener("input", function () {
            let valor = rut.value.replace(/[^0-9kK]/g, "").toUpperCase();

            if (valor.length < 2) {
                rutEstado.textContent = "";

                rutEstado.className = "rut-estado";

                return;
            }

            const cuerpo = valor.slice(0, -1);

            const dv = valor.slice(-1);

            if (!/^\d+$/.test(cuerpo)) {
                rutEstado.textContent = "RUT inválido";

                rutEstado.className = "rut-estado rut-invalido";

                return;
            }

            let suma = 0;

            let multiplicador = 2;

            for (let i = cuerpo.length - 1; i >= 0; i--) {
                suma += parseInt(cuerpo[i]) * multiplicador;

                multiplicador++;

                if (multiplicador > 7) {
                    multiplicador = 2;
                }
            }

            const resto = 11 - (suma % 11);

            let dvEsperado;

            if (resto === 11) {
                dvEsperado = "0";
            } else if (resto === 10) {
                dvEsperado = "K";
            } else {
                dvEsperado = resto.toString();
            }

            if (dv === dvEsperado) {
                rutEstado.textContent = "RUT válido";

                rutEstado.className = "rut-estado rut-valido";
            } else {
                rutEstado.textContent = "RUT inválido";

                rutEstado.className = "rut-estado rut-invalido";
            }
        });
    }

    /* Validación de los archivos */
    const archivos = document.querySelectorAll(".documento-input");

    const MAXIMO = 100 * 1024 * 1024;

    archivos.forEach(function (input) {
        input.addEventListener("change", function () {
            const archivo = input.files[0];

            if (!archivo) {
                return;
            }

            const contenedor = input.closest(".documento-item");

            const error = contenedor.querySelector(".documento-error");

            error.textContent = "";

            error.classList.remove("visible");

            if (archivo.size > MAXIMO) {
                error.textContent = "El archivo supera el tamaño máximo permitido de 100 MB.";

                error.classList.add("visible");

                input.value = "";

                return;
            }

            const extension = archivo.name.split(".").pop().toLowerCase();

            if (extension !== "pdf") {
                error.textContent = "Solo se permiten archivos PDF.";

                error.classList.add("visible");

                input.value = "";

                return;
            }

            if (archivo.type !== "application/pdf") {
                error.textContent = "El archivo seleccionado no corresponde a un PDF válido.";

                error.classList.add("visible");

                input.value = "";

                return;
            }
        });
    });

    /* Confirmación del formulario */
    const formulario = document.getElementById("formPostulacion");

    if (formulario) {
        formulario.addEventListener("submit", function (evento) {
            const inputs = formulario.querySelectorAll("[required]");

            let completo = true;

            inputs.forEach(function (input) {
                if (!input.value) {
                    completo = false;
                }
            });

            if (!completo) {
                evento.preventDefault();

                alert("Debes completar todos los campos obligatorios.");

                return;
            }
        });
    }
});
