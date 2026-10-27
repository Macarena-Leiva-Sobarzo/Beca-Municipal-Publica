(() => {
    const parametros = new URLSearchParams(window.location.search);
    const documento = parametros.get("documento");
    const mensaje = document.getElementById("mensaje");
    const canvas = document.getElementById("pdf");
    const anterior = document.getElementById("anterior");
    const siguiente = document.getElementById("siguiente");
    const pagina = document.getElementById("pagina");

    if (!documento || !window.pdfjsLib || !canvas) {
        mensaje.textContent = "No fue posible abrir el documento.";
        return;
    }

    pdfjsLib.GlobalWorkerOptions.workerSrc = "pdfjs/pdf.worker.min.js";

    let pdf;
    let paginaActual = 1;
    let renderizando = false;

    const actualizarControles = () => {
        anterior.disabled = paginaActual <= 1 || renderizando;
        siguiente.disabled = !pdf || paginaActual >= pdf.numPages || renderizando;
        pagina.textContent = pdf ? `Página ${paginaActual} de ${pdf.numPages}` : "Cargando…";
    };

    const renderizar = async () => {
        if (!pdf || renderizando) return;

        renderizando = true;
        actualizarControles();
        try {
            const hoja = await pdf.getPage(paginaActual);
            const base = hoja.getViewport({ scale: 1 });
            const escala = Math.min(1.8, Math.max(0.8, (window.innerWidth - 32) / base.width));
            const viewport = hoja.getViewport({ scale: escala });
            const contexto = canvas.getContext("2d");
            canvas.width = Math.ceil(viewport.width);
            canvas.height = Math.ceil(viewport.height);
            await hoja.render({ canvasContext: contexto, viewport }).promise;
            mensaje.hidden = true;
        } catch {
            mensaje.hidden = false;
            mensaje.textContent = "No fue posible mostrar esta página del documento.";
        } finally {
            renderizando = false;
            actualizarControles();
        }
    };

    anterior.addEventListener("click", () => {
        if (paginaActual > 1) {
            paginaActual--;
            renderizar();
        }
    });
    siguiente.addEventListener("click", () => {
        if (pdf && paginaActual < pdf.numPages) {
            paginaActual++;
            renderizar();
        }
    });
    window.addEventListener("resize", () => renderizar());

    pdfjsLib
        .getDocument({ url: documento, withCredentials: true })
        .promise.then((cargado) => {
            pdf = cargado;
            actualizarControles();
            return renderizar();
        })
        .catch(() => {
            mensaje.textContent = "No fue posible cargar el documento.";
            pagina.textContent = "Documento no disponible";
        });
})();
