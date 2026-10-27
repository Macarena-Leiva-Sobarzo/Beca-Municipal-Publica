// Permite K o k en los formularios de cuenta y presenta siempre K mayúscula.
(() => {
    function esRut(campo) {
        return campo instanceof HTMLInputElement && campo.name === "rut" && campo.type !== "hidden";
    }
    document.addEventListener("input", (event) => {
        if (!esRut(event.target)) return;
        const campo = event.target,
            inicio = campo.selectionStart,
            fin = campo.selectionEnd;
        campo.value = campo.value.toUpperCase();
        if (inicio !== null) campo.setSelectionRange(inicio, fin);
    });
    document.addEventListener("focusout", (event) => {
        if (!esRut(event.target)) return;
        const campo = event.target;
        const rut = campo.value.replace(/[.\s-]/g, "").toUpperCase();
        if (/^[0-9]{7,8}[0-9K]$/.test(rut)) {
            campo.value =
                rut.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, ".") + "-" + rut.slice(-1);
        }
    });
})();
