/* ============ MENÚ LATERAL: cerrar y desplazarse a la sección ============ */
document.querySelectorAll('#menuLateral a[href^="#"]').forEach(enlace => {
    enlace.addEventListener('click', function (e) {
        e.preventDefault();
        const destino = document.querySelector(this.getAttribute('href'));
        const menu = document.getElementById('menuLateral');
        const instancia = bootstrap.Offcanvas.getInstance(menu);
        if (instancia) instancia.hide();
        if (destino) {
            setTimeout(() => destino.scrollIntoView({ behavior: 'smooth' }), 400);
        }
    });
});

/* ============ COPIAR DATOS DE TRANSFERENCIA ============ */
(function () {
    const boton = document.getElementById('btnCopiar');
    const contenedor = document.getElementById('datosTransferencia');
    if (!boton || !contenedor) return; // otra página (ej. login)

    const texto = document.getElementById('textoCopiar');
    const icono = document.getElementById('iconoCopiar');
    const aviso = document.getElementById('estadoCopiar');
    const textoOriginal = texto.textContent;
    let temporizador = null;

    // Arma el texto a copiar con los mismos datos que se ven en pantalla,
    // así, si cambian los datos en el HTML, la copia se actualiza sola.
    function datosParaCopiar() {
        return Array.from(contenedor.querySelectorAll('p'))
            .map(p => p.textContent.replace(/\s+/g, ' ').trim())
            .filter(Boolean)
            .join('\n');
    }

    // Método de respaldo para navegadores o contextos donde no existe navigator.clipboard
    function copiarConRespaldo(valor) {
        const area = document.createElement('textarea');
        area.value = valor;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        let ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        document.body.removeChild(area);
        return ok;
    }

    async function copiar(valor) {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(valor);
                return true;
            } catch (e) { /* se prueba el método de respaldo */ }
        }
        return copiarConRespaldo(valor);
    }

    function mostrar(ok) {
        texto.textContent = ok ? '¡Datos copiados!' : 'No se pudo copiar';
        icono.className = ok ? 'fa-solid fa-check ms-2' : 'fa-solid fa-triangle-exclamation ms-2';
        aviso.textContent = ok ? 'Datos de transferencia copiados al portapapeles.' : 'No se pudo copiar. Selecciona los datos y cópialos manualmente.';
        clearTimeout(temporizador);
        temporizador = setTimeout(() => {
            texto.textContent = textoOriginal;
            icono.className = 'fa-solid fa-copy ms-2';
        }, 2500);
    }

    boton.addEventListener('click', async () => {
        mostrar(await copiar(datosParaCopiar()));
    });
})();