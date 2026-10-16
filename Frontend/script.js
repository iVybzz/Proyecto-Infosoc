document.querySelectorAll('.enlace-menu').forEach(enlace => {
    enlace.addEventListener('click', function(e) {
        e.preventDefault(); 
        const destinoId = this.getAttribute('href'); 
        const seccionDestino = document.querySelector(destinoId);
        const menu = document.getElementById('menuLateral');
        const instanciaMenu = bootstrap.Offcanvas.getInstance(menu);
        
        if (instanciaMenu) {
            instanciaMenu.hide(); 
        }

        setTimeout(() => {
            seccionDestino.scrollIntoView({ behavior: 'smooth' });
        }, 400);
    });
});