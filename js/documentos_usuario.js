(() => {
    const script = document.currentScript;
    const visor = new URL("visor_pdf.html", script.src);
    function urlVisor(url) {
        const destino = new URL(visor);
        destino.searchParams.set("documento", new URL(url, location.href).href);
        return destino.href;
    }
    function convertir(frame) {
        const src = frame.getAttribute("src");
        if (!src || src.includes("visor_pdf.html")) return;
        const url = new URL(src, location.href);
        if (url.origin === location.origin && url.pathname.endsWith(".php"))
            frame.src = urlVisor(url.href);
    }
    function iniciar() {
        document.querySelectorAll("iframe").forEach(convertir);
        new MutationObserver((cambios) => {
            for (const cambio of cambios) {
                if (cambio.type === "attributes" && cambio.target.tagName === "IFRAME")
                    convertir(cambio.target);
                for (const nodo of cambio.addedNodes)
                    if (nodo.nodeType === 1) {
                        if (nodo.tagName === "IFRAME") convertir(nodo);
                        nodo.querySelectorAll("iframe").forEach(convertir);
                    }
            }
        }).observe(document.body, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ["src"],
        });
        const modal = document.createElement("dialog");
        modal.className = "segundo-modal";
        modal.innerHTML =
            '<div class="modal-pdf-header"><h2>Vista previa del documento</h2><div style="display:flex;align-items:center;gap:10px"><a class="btn-cerrar-pdf" data-descargar-documento download title="Descargar documento" aria-label="Descargar documento" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M5 17v4h14v-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a><button type="button" class="btn-cerrar-pdf" aria-label="Cerrar documento">×</button></div></div><iframe title="Vista previa del documento"></iframe>';
        document.body.append(modal);
        let enlace;
        modal.querySelector("button").onclick = () => modal.close();
        modal.addEventListener("close", () => {
            modal.querySelector("iframe").removeAttribute("src");
            document.body.classList.remove("modal-abierto");
            enlace?.focus();
        });
        modal.addEventListener("click", (event) => {
            const r = modal.getBoundingClientRect();
            if (
                event.target === modal &&
                (event.clientX < r.left ||
                    event.clientX > r.right ||
                    event.clientY < r.top ||
                    event.clientY > r.bottom)
            )
                modal.close();
        });
        document.addEventListener("click", (event) => {
            // Los botones de instrucciones no navegan: muestran el PDF en el
            // visor de esta misma página y dejan disponible su descarga.
            const disparador = event.target.closest("[data-documento]");
            if (disparador) {
                const destino = disparador.dataset.documento;
                if (!destino) return;
                event.preventDefault();
                enlace = disparador;
                const descarga =
                    disparador.dataset.descarga || destino;
                const titulo = disparador.dataset.titulo || "Vista previa del documento";
                modal.querySelector("h2").textContent = titulo;
                modal.querySelector("[data-descargar-documento]").href = new URL(
                    descarga,
                    location.href,
                ).href;
                modal.querySelector("iframe").src = urlVisor(destino);
                modal.showModal();
                document.body.classList.add("modal-abierto");
                return;
            }
            const link = event.target.closest("a");
            if (!link || link.classList.contains("segundo-ver-certificado")) return;
            const url = new URL(link.href);
            if (
                url.origin !== location.origin ||
                url.searchParams.get("modo") === "descargar" ||
                url.searchParams.has("descargar") ||
                link.hasAttribute("download")
            )
                return;
            const archivo = url.pathname.split("/").pop();
            const fuentes = {
                "comprobante.php": "generar_comprobante.php?modo=ver",
                "comprobante_rendicion.php": "generar_comprobante_rendicion.php?modo=ver",
            };
            const destino =
                fuentes[archivo] ||
                (/^(generar_comprobante|ver_).*.php$/.test(archivo) ? url.href : null);
            if (!destino) return;
            event.preventDefault();
            enlace = link;
            const descarga = new URL(destino, location.href);
            if (/^generar_comprobante/.test(descarga.pathname.split("/").pop()))
                descarga.searchParams.set("modo", "descargar");
            else descarga.searchParams.set("descargar", "1");
            modal.querySelector("[data-descargar-documento]").href = descarga.href;
            modal.querySelector("iframe").src = urlVisor(destino);
            modal.showModal();
            document.body.classList.add("modal-abierto");
        });
    }
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", iniciar);
    else iniciar();
})();
