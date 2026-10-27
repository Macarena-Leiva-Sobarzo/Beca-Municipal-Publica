// Actualiza el listado sin perder filtros ni desplazar la página. La confirmación precede al borrado y el servidor valida permisos y CSRF.
(() => {
    const modal = document.getElementById("editar-cuenta-dialog");
    if (!modal) return;
    function actualizarCamposClave(form) {
        if (!form) return;
        const accion = form.querySelector('[name="usuarios_accion"]')?.value;
        const rol = form.querySelector('[name="rol"]')?.value;
        const soloRendicion = form.dataset.soloRendicion === "1";
        const sinClave = accion === "crear" && soloRendicion && rol === "usuario";
        const clave = form.querySelector('[name="password"]');
        const confirmacion = form.querySelector('[name="password_confirmacion"]');
        const etiqueta = form.querySelector("#etiqueta-cuenta-clave");
        const ayuda = form.querySelector("#ayuda-cuenta-clave");
        if (clave) clave.required = accion === "crear" && !sinClave;
        if (confirmacion) confirmacion.required = accion === "crear" && !sinClave;
        if (accion !== "crear") return;
        if (etiqueta) etiqueta.textContent = sinClave ? "Contraseña (no requerida)" : "Contraseña";
        if (ayuda) ayuda.textContent = sinClave
            ? "Las cuentas con rol usuario ingresan solo con RUT durante este proceso."
            : "Mínimo ocho caracteres.";
    }
    function actualizarTodosLosCamposClave(contenedor = document) {
        contenedor.querySelectorAll('form[data-solo-rendicion]').forEach(actualizarCamposClave);
    }
    actualizarTodosLosCamposClave();
    function formatearRut(campo) {
        const rut = campo.value.replace(/[.\-\s]/g, "").toUpperCase();
        if (!/^[0-9]{7,8}[0-9K]$/.test(rut)) return;
        campo.value = rut.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, ".") + "-" + rut.slice(-1);
    }
    document.addEventListener("focusout", (event) => {
        if (event.target.matches("#cuenta-rut")) formatearRut(event.target);
    });
    const aviso = document.getElementById("aviso-accion-usuarios");
    let ocupado = false;
    function informar(texto, ok) {
        aviso.textContent = texto;
        aviso.hidden = false;
        aviso.className = ok ? "aviso-usuarios-exito" : "aviso-usuarios-error";
    }
    function urlLista(limpiar = false) {
        const url = new URL("configuracion.php", location.href);
        if (!limpiar) {
            const filtro = document.querySelector("#panel-usuarios .usuarios-filtros");
            if (filtro)
                for (const [clave, valor] of new FormData(filtro))
                    url.searchParams.set(clave, valor);
        }
        const proceso = new URL(location.href).searchParams.get("proceso_id");
        if (proceso) url.searchParams.set("proceso_id", proceso);
        return url;
    }
    async function cargar(url) {
        const respuesta = await fetch(url, { credentials: "same-origin" });
        if (!respuesta.ok) throw new Error("No se pudo actualizar la pantalla. Recarga la página.");
        const doc = new DOMParser().parseFromString(await respuesta.text(), "text/html");
        if (!doc.querySelector("#panel-usuarios"))
            throw new Error("La sesión expiró. Vuelve a iniciar sesión.");
        return doc;
    }
    async function actualizar(limpiar = false) {
        const x = scrollX,
            y = scrollY;
        const url = urlLista(limpiar),
            doc = await cargar(url);
        document.getElementById("panel-usuarios").replaceWith(doc.querySelector("#panel-usuarios"));
        actualizarTodosLosCamposClave();
        // No añadir fragmentos que hagan saltar al formulario de creación.
        history.replaceState(null, "", url.pathname + url.search);
        window.scrollTo(x, y);
    }
    document.addEventListener("click", async (event) => {
        const cerrar = event.target.closest("[data-cerrar-edicion]");
        if (cerrar) {
            if (!ocupado) modal.close();
            return;
        }
        const editar = event.target.closest("[data-usuarios-editar]");
        const limpiar = event.target.closest("[data-usuarios-limpiar]");
        const cancelar = event.target.closest("#editar-cuenta-dialog a");
        if (cancelar) {
            event.preventDefault();
            if (!ocupado) modal.close();
            return;
        }
        if (!editar && !limpiar) return;
        event.preventDefault();
        if (ocupado) return;
        ocupado = true;
        try {
            if (limpiar) {
                await actualizar(true);
                informar("Filtros eliminados.", true);
            } else {
                const doc = await cargar(editar.href);
                const seccion = doc.querySelector("#gestion-usuarios");
                if (!seccion.querySelector('[name="usuarios_accion"][value="editar"]'))
                    throw new Error("La cuenta ya no existe.");
                seccion.removeAttribute("id");
                document.getElementById("contenido-editar-cuenta").replaceChildren(seccion);
                actualizarTodosLosCamposClave(modal);
                modal.showModal();
            }
        } catch (e) {
            informar(e.message, false);
        } finally {
            ocupado = false;
        }
    });
    modal.addEventListener("cancel", (event) => {
        if (ocupado) event.preventDefault();
    });
    document.addEventListener("submit", async (event) => {
        const form = event.target;
        if (!form.closest("#panel-usuarios, #editar-cuenta-dialog")) return;
        // Respetar una cancelación del confirm de eliminación.
        if (event.defaultPrevented) return;
        event.preventDefault();
        if (ocupado) return;
        const campoRut = form.querySelector("#cuenta-rut");
        if (campoRut) formatearRut(campoRut);
        ocupado = true;
        const botones = [...form.querySelectorAll('button[type="submit"]')];
        const datos = new FormData(form);
        botones.forEach((b) => (b.disabled = true));
        try {
            if (form.classList.contains("usuarios-filtros")) {
                await actualizar();
                informar("Búsqueda actualizada.", true);
                return;
            }
            const respuesta = await fetch(new URL("configuracion.php", location.href), {
                method: "POST",
                body: datos,
                credentials: "same-origin",
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!respuesta.ok)
                throw new Error("No se pudo completar la operación. Verifica tu sesión.");
            const resultado = await respuesta.json();
            if (resultado.ok) {
                if (modal.open) modal.close();
                try {
                    await actualizar();
                } catch (_) {
                    informar(resultado.mensaje + " Recarga la página para ver los cambios.", true);
                    return;
                }
            }
            informar(resultado.mensaje, resultado.ok);
            if (modal.open) {
                let mensaje = modal.querySelector("[data-error-edicion]");
                if (!mensaje) {
                    mensaje = document.createElement("p");
                    mensaje.dataset.errorEdicion = "";
                    mensaje.setAttribute("role", "alert");
                    modal.querySelector("form").prepend(mensaje);
                }
                mensaje.textContent = resultado.mensaje;
            }
        } catch (_) {
            informar(
                "No se pudo confirmar la operación. Revisa la conexión y actualiza el listado antes de reintentar.",
                false,
            );
        } finally {
            ocupado = false;
            botones.forEach((b) => (b.disabled = false));
        }
    });
    document.addEventListener("change", (event) => {
        if (event.target.matches('[name="rol"]')) {
            actualizarCamposClave(event.target.closest("form"));
        }
    });
})();
