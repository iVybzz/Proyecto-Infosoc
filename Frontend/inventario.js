/* =====================================================================
   INVENTARIO DE ACTIVOS
   ---------------------------------------------------------------------
   Todo lo que toca datos está en el objeto "api" (más abajo). Por ahora
   usa datos de EJEMPLO en memoria: al recargar la página se pierden.

   MODELO DE DATOS (una fila por TIPO de activo, con su cantidad)
   Donar 30 pañales crea UN activo "Pañales" con cantidad = 30,
   no 30 filas.

   Activo = {
     id:          'ACT-0004',
     nombre:      'Pañales adulto talla G',
     categoria:   'Insumos',            // 'Implementos' | 'Insumos' | 'Navidad'
     estado:      'Nuevo',              // 'Nuevo' | 'Bueno' | 'Regular' | 'Dañado'
     descripcion: '',
     imagen:      null,                 // URL o null
     cantidad:    30,                   // TOTAL de unidades que tiene la organización
     prestamos:   [ Prestamo, ... ]     // solo préstamos ACTIVOS (sin devolver)
   }

   Prestamo = {
     id:          'PRE-0001',
     prestatario: 'Nombre Apellido',
     telefono:    '',
     cantidad:    3,                    // unidades prestadas en este préstamo
     desde:       '2026-10-26',         // AAAA-MM-DD
     devolucion:  '2026-11-09',         // AAAA-MM-DD (fecha estimada)
     nota:        ''
   }

   "Disponibles" NO se guarda: se calcula = cantidad - suma(prestamos.cantidad).

   Ver INVENTARIO_API.md para las tablas y endpoints sugeridos.
   ===================================================================== */

const CATEGORIAS = ['Implementos', 'Insumos', 'Navidad'];
const ESTADOS = ['Nuevo', 'Bueno', 'Regular', 'Dañado'];
const ICONOS = {
    'Implementos': 'fa-wheelchair',
    'Insumos': 'fa-pump-medical',
    'Navidad': 'fa-tree'
};

/* ---------- Utilidades ---------- */
function esc(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function normalizar(texto) {
    return String(texto ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
}

function aISO(fecha) {
    const a = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, '0');
    const d = String(fecha.getDate()).padStart(2, '0');
    return `${a}-${m}-${d}`;
}

function hoyISO() { return aISO(new Date()); }

function enDias(n) {
    const f = new Date();
    f.setDate(f.getDate() + n);
    return aISO(f);
}

function fechaCorta(iso) {
    if (!iso) return '';
    return new Date(iso + 'T00:00:00').toLocaleDateString('es-CL');
}

function unidades(n) { return n === 1 ? '1 unidad' : `${n} unidades`; }

/* ---------- Cálculos sobre un activo ---------- */
function totalPrestado(a) { return a.prestamos.reduce((suma, p) => suma + p.cantidad, 0); }
function disponibles(a) { return a.cantidad - totalPrestado(a); }
function prestamoAtrasado(p) { return !!p.devolucion && p.devolucion < hoyISO(); }
function hayAtrasado(a) { return a.prestamos.some(prestamoAtrasado); }

/* ---------- DATOS DE EJEMPLO (se eliminan al conectar el backend) ---------- */
let BASE_EJEMPLO = [
    { id: 'ACT-0001', nombre: 'Silla de ruedas plegable', categoria: 'Implementos', estado: 'Bueno', descripcion: 'Con frenos y reposapiés.', imagen: null, cantidad: 3,
      prestamos: [{ id: 'PRE-0001', prestatario: 'María González', telefono: '+56 9 1111 2222', cantidad: 1, desde: enDias(-5), devolucion: enDias(9), nota: '' }] },
    { id: 'ACT-0002', nombre: 'Bastón canadiense', categoria: 'Implementos', estado: 'Nuevo', descripcion: '', imagen: null, cantidad: 1,
      prestamos: [{ id: 'PRE-0002', prestatario: 'Luis Rojas', telefono: '', cantidad: 1, desde: enDias(-2), devolucion: enDias(12), nota: '' }] },
    { id: 'ACT-0003', nombre: 'Cama clínica manual', categoria: 'Implementos', estado: 'Regular', descripcion: 'Una manivela con desgaste.', imagen: null, cantidad: 1, prestamos: [] },
    { id: 'ACT-0004', nombre: 'Pañales adulto talla G (paquete)', categoria: 'Insumos', estado: 'Nuevo', descripcion: '', imagen: null, cantidad: 30, prestamos: [] },
    { id: 'ACT-0005', nombre: 'Guantes de examen (caja)', categoria: 'Insumos', estado: 'Nuevo', descripcion: '', imagen: null, cantidad: 12, prestamos: [] },
    { id: 'ACT-0006', nombre: 'Árbol de Navidad 1,5 m', categoria: 'Navidad', estado: 'Bueno', descripcion: 'Incluye base y luces.', imagen: null, cantidad: 2,
      prestamos: [{ id: 'PRE-0003', prestatario: 'Pedro Soto', telefono: '', cantidad: 1, desde: enDias(-20), devolucion: enDias(-3), nota: 'Para actividad del colegio.' }] }
];

/* ---------- CAPA DE ACCESO A DATOS: AQUÍ SE CONECTA EL BACKEND ---------- */
const api = {
    // TODO BACKEND: GET /api/activos  -> [Activo, ...] (con sus préstamos activos)
    async listarActivos() {
        return BASE_EJEMPLO.map(a => ({ ...a, prestamos: a.prestamos.map(p => ({ ...p })) }));
    },

    // TODO BACKEND: POST /api/activos  con { nombre, categoria, estado, descripcion, cantidad } (+ imagen)
    // Regla: si ya existe un activo con el mismo nombre, categoría y estado, el servidor SUMA la
    // cantidad a ese activo en vez de crear otro. Debe responder { activo, sumado: true|false }.
    // "datos.imagen" es un objeto File (o null).
    async registrarActivo(datos, cantidad) {
        const clave = a => [normalizar(a.nombre), a.categoria, a.estado].join('|');
        const existente = BASE_EJEMPLO.find(a => clave(a) === clave(datos));

        if (existente) {
            existente.cantidad += cantidad;
            if (!existente.imagen && datos.imagen) existente.imagen = URL.createObjectURL(datos.imagen);
            return { activo: { ...existente }, sumado: true };
        }

        const mayor = BASE_EJEMPLO.reduce((m, a) => Math.max(m, parseInt(a.id.replace(/\D/g, ''), 10) || 0), 0);
        const nuevo = {
            id: 'ACT-' + String(mayor + 1).padStart(4, '0'),
            nombre: datos.nombre,
            categoria: datos.categoria,
            estado: datos.estado,
            descripcion: datos.descripcion,
            imagen: datos.imagen ? URL.createObjectURL(datos.imagen) : null,
            cantidad,
            prestamos: []
        };
        BASE_EJEMPLO.push(nuevo);
        return { activo: { ...nuevo }, sumado: false };
    },

    // TODO BACKEND: POST /api/activos/:id/prestamos  con { prestatario, telefono, cantidad, devolucion, nota }
    // El servidor debe comprobar que "cantidad" no supere las unidades disponibles.
    async prestarActivo(id, prestamo) {
        const a = BASE_EJEMPLO.find(x => x.id === id);
        if (!a) throw new Error('Activo no encontrado');
        if (prestamo.cantidad < 1 || prestamo.cantidad > disponibles(a)) throw new Error('Cantidad no disponible');
        const mayor = BASE_EJEMPLO.flatMap(x => x.prestamos)
            .reduce((m, p) => Math.max(m, parseInt(p.id.replace(/\D/g, ''), 10) || 0), 0);
        a.prestamos.push({ ...prestamo, id: 'PRE-' + String(mayor + 1).padStart(4, '0'), desde: hoyISO() });
    },

    // TODO BACKEND: POST /api/prestamos/:id/devolucion  (marca el préstamo como devuelto)
    async devolverPrestamo(prestamoId) {
        const a = BASE_EJEMPLO.find(x => x.prestamos.some(p => p.id === prestamoId));
        if (!a) throw new Error('Préstamo no encontrado');
        a.prestamos = a.prestamos.filter(p => p.id !== prestamoId);
    }
};

/* ---------- Estado de la pantalla ---------- */
let activos = [];
let filtroCategoria = null;   // null = todas
let textoBusqueda = '';
let idPendiente = null;       // activo sobre el que se está operando en un modal

const el = id => document.getElementById(id);
const modales = {
    registrar: new bootstrap.Modal(el('modalRegistrar')),
    prestar: new bootstrap.Modal(el('modalPrestar')),
    devolver: new bootstrap.Modal(el('modalDevolver')),
    info: new bootstrap.Modal(el('modalInfo'))
};

/* ---------- Avisos ---------- */
function avisar(mensaje, tipo) {
    const toast = el('aviso');
    toast.classList.remove('text-bg-success', 'text-bg-danger');
    toast.classList.add(tipo === 'error' ? 'text-bg-danger' : 'text-bg-success');
    el('avisoTexto').textContent = mensaje;
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 4500 }).show();
}

/* ---------- Piezas de pantalla ---------- */
function htmlImagen(a) {
    if (a.imagen) return `<img src="${esc(a.imagen)}" alt="">`;
    return `<i class="fa-solid ${ICONOS[a.categoria] || 'fa-image'}" aria-hidden="true"></i>`;
}

function claseEstado(estado) {
    return { 'Nuevo': 'text-bg-info', 'Bueno': 'text-bg-success', 'Regular': 'text-bg-warning', 'Dañado': 'text-bg-danger' }[estado] || 'text-bg-secondary';
}

function htmlDisponibilidad(a) {
    const d = disponibles(a);
    let insignia;
    if (d === a.cantidad) insignia = '<span class="badge text-bg-success">Disponible</span>';
    else if (d === 0) insignia = `<span class="badge text-bg-warning">${a.cantidad === 1 ? 'Prestado' : 'Sin disponibles'}</span>`;
    else insignia = '<span class="badge text-bg-info">Parcial</span>';
    const cuenta = a.cantidad > 1 ? `<div class="small text-secondary mt-1">${d} de ${a.cantidad} disponibles</div>` : '';
    return insignia + cuenta;
}

function htmlDetallePrestamo(a) {
    if (a.prestamos.length === 0) return '<span class="text-secondary">—</span>';
    const lineas = a.prestamos.slice(0, 3).map(p =>
        `<div><strong>${esc(p.prestatario)}</strong>` +
        (a.cantidad > 1 ? ` · ${p.cantidad} ud.` : '') +
        ` · devuelve ${esc(fechaCorta(p.devolucion))}` +
        (prestamoAtrasado(p) ? ' <span class="badge text-bg-danger">Atrasado</span>' : '') + '</div>');
    if (a.prestamos.length > 3) lineas.push(`<div class="text-secondary">y ${a.prestamos.length - 3} más (ver Info)</div>`);
    return lineas.join('');
}

function htmlBotones(a) {
    let html = '';
    if (disponibles(a) > 0) {
        html += `<button type="button" class="btn btn-primary btn-sm" data-accion="prestar" data-id="${esc(a.id)}">Prestar</button>`;
    }
    if (a.prestamos.length > 0) {
        html += `<button type="button" class="btn btn-success btn-sm" data-accion="devolver" data-id="${esc(a.id)}">Devolver</button>`;
    }
    html += `<button type="button" class="btn btn-outline-primary btn-sm" data-accion="info" data-id="${esc(a.id)}">Info</button>`;
    return html;
}

function filaTabla(a) {
    return `<tr>
        <td class="fw-bold">${esc(a.id)}</td>
        <td><div class="activo-img chica">${htmlImagen(a)}</div></td>
        <td class="fw-semibold">${esc(a.nombre)}</td>
        <td>${esc(a.categoria)}</td>
        <td><span class="badge ${claseEstado(a.estado)}">${esc(a.estado)}</span></td>
        <td>${htmlDisponibilidad(a)}</td>
        <td class="detalle-prestamo">${htmlDetallePrestamo(a)}</td>
        <td><div class="d-flex gap-2 flex-wrap">${htmlBotones(a)}</div></td>
    </tr>`;
}

function barraMovil(a) {
    const d = disponibles(a);
    if (a.prestamos.length === 0) {
        return `<div class="disp disponible">DISPONIBLE${a.cantidad > 1 ? ` · ${unidades(a.cantidad)}` : ''}</div>`;
    }
    const primero = a.prestamos[0].prestatario;
    const otros = a.prestamos.length > 1 ? ` y ${a.prestamos.length - 1} más` : '';
    const atraso = hayAtrasado(a) ? ' (atrasado)' : '';
    if (d === 0) return `<div class="disp prestado">Prestado: ${esc(primero)}${esc(otros)}${atraso}</div>`;
    return `<div class="disp parcial">Disponibles ${d} de ${a.cantidad} · Prestado: ${esc(primero)}${esc(otros)}${atraso}</div>`;
}

function tarjetaMovil(a) {
    return `<article class="activo-card" aria-label="${esc(a.nombre)}">
        <div class="activo-img">${htmlImagen(a)}</div>
        <div>
            <div class="nombre">${esc(a.nombre)}</div>
            <div class="meta">${esc(a.id)} · ${esc(a.categoria)}</div>
            <div class="meta">Estado: <strong>${esc(a.estado)}</strong>${a.cantidad > 1 ? ` · Cantidad: <strong>${a.cantidad}</strong>` : ''}</div>
        </div>
        <div class="acciones">${htmlBotones(a)}</div>
        ${barraMovil(a)}
    </article>`;
}

/* ---------- Filtros ---------- */
function crearFiltros() {
    el('filtrosMovil').innerHTML = CATEGORIAS.map(c =>
        `<div class="col-4">
            <button type="button" class="filtro-tile" data-cat="${esc(c)}" aria-pressed="false">
                <span class="cantidad" data-cuenta="${esc(c)}">0</span>
                <span class="nombre">${esc(c)}</span>
            </button>
        </div>`).join('');

    el('filtrosEscritorio').innerHTML =
        `<button type="button" class="filtro-pill" data-cat="" aria-pressed="true">Todos</button>` +
        CATEGORIAS.map(c =>
            `<button type="button" class="filtro-pill" data-cat="${esc(c)}" aria-pressed="false">
                ${esc(c)} <span class="badge text-bg-light ms-1" data-cuenta="${esc(c)}">0</span>
            </button>`).join('');
}

function actualizarFiltros() {
    document.querySelectorAll('[data-cat]').forEach(btn => {
        const activo = btn.dataset.cat === (filtroCategoria || '');
        btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
    });
    document.querySelectorAll('[data-cuenta]').forEach(span => {
        span.textContent = activos.filter(a => a.categoria === span.dataset.cuenta).length;
    });
}

function filtrados() {
    const q = normalizar(textoBusqueda);
    return activos.filter(a => {
        if (filtroCategoria && a.categoria !== filtroCategoria) return false;
        if (!q) return true;
        return normalizar(a.nombre).includes(q) ||
               normalizar(a.id).includes(q) ||
               a.prestamos.some(p => normalizar(p.prestatario).includes(q));
    });
}

/* ---------- Dibujar ---------- */
function render() {
    actualizarFiltros();
    const lista = filtrados();

    let resumen = `Mostrando ${lista.length} de ${activos.length} activos`;
    if (filtroCategoria) resumen += ` · Categoría: ${filtroCategoria}`;
    el('resumen').textContent = resumen;

    if (lista.length === 0) {
        const vacio = '<div class="estado-vacio"><i class="fa-regular fa-folder-open fs-2 mb-2" aria-hidden="true"></i><p class="mb-0">No hay activos que coincidan con la búsqueda o el filtro.</p></div>';
        el('listaMovil').innerHTML = vacio;
        el('tablaCuerpo').innerHTML = `<tr><td colspan="8">${vacio}</td></tr>`;
        return;
    }

    el('listaMovil').innerHTML = lista.map(tarjetaMovil).join('');
    el('tablaCuerpo').innerHTML = lista.map(filaTabla).join('');
}

async function recargar() {
    try {
        activos = await api.listarActivos();
        render();
    } catch (e) {
        el('resumen').textContent = '';
        avisar('No se pudo cargar el inventario. Inténtalo de nuevo.', 'error');
    }
}

/* ---------- Eventos: filtros, búsqueda y botones ---------- */
document.addEventListener('click', e => {
    const filtro = e.target.closest('[data-cat]');
    if (filtro) {
        const cat = filtro.dataset.cat;
        filtroCategoria = (cat === '' || cat === filtroCategoria) ? null : cat;
        render();
        return;
    }

    const boton = e.target.closest('[data-accion]');
    if (!boton) return;
    const a = activos.find(x => x.id === boton.dataset.id);
    if (!a) return;
    idPendiente = a.id;

    if (boton.dataset.accion === 'prestar') abrirPrestar(a);
    if (boton.dataset.accion === 'devolver') abrirDevolver(a);
    if (boton.dataset.accion === 'info') abrirInfo(a);
});

el('buscador').addEventListener('input', e => {
    textoBusqueda = e.target.value;
    render();
});

/* ---------- Registrar activo (una fila con su cantidad) ---------- */
const formRegistrar = el('formRegistrar');

el('modalRegistrar').addEventListener('show.bs.modal', () => {
    formRegistrar.reset();
    formRegistrar.classList.remove('was-validated');
    el('errorImagen').classList.add('d-none');
});

formRegistrar.addEventListener('submit', async e => {
    e.preventDefault();
    el('regNombre').value = el('regNombre').value.trim();
    formRegistrar.classList.add('was-validated');

    const imagen = el('regImagen').files[0] || null;
    const imagenGrande = !!imagen && imagen.size > 2 * 1024 * 1024;
    el('errorImagen').classList.toggle('d-none', !imagenGrande);

    if (!formRegistrar.checkValidity() || imagenGrande) return;

    const cantidad = parseInt(el('regCantidad').value, 10);
    const boton = formRegistrar.querySelector('[type="submit"]');
    boton.disabled = true;

    try {
        const { activo, sumado } = await api.registrarActivo({
            nombre: el('regNombre').value,
            categoria: el('regCategoria').value,
            estado: el('regEstado').value,
            descripcion: el('regDescripcion').value.trim(),
            imagen
        }, cantidad);
        await recargar();
        modales.registrar.hide();
        avisar(sumado
            ? `Se sumaron ${unidades(cantidad)} a «${activo.nombre}». Ahora hay ${activo.cantidad}.`
            : `Activo registrado: «${activo.nombre}» con ${unidades(cantidad)}.`);
    } catch (err) {
        avisar('No se pudo registrar. Inténtalo de nuevo.', 'error');
    } finally {
        boton.disabled = false;
    }
});

/* ---------- Prestar ---------- */
const formPrestar = el('formPrestar');

function abrirPrestar(a) {
    const d = disponibles(a);
    formPrestar.reset();
    formPrestar.classList.remove('was-validated');
    el('prestarResumen').textContent = `${a.nombre} (${a.id}) · ${d} de ${a.cantidad} disponibles`;
    el('prestCantidad').min = 1;
    el('prestCantidad').max = d;
    el('prestCantidad').value = 1;
    el('prestCantidadError').textContent = d === 1 ? 'Solo queda 1 unidad disponible.' : `Escribe un número entre 1 y ${d}.`;
    el('prestDevolucion').min = hoyISO();
    el('prestDevolucion').value = enDias(14);
    modales.prestar.show();
}

formPrestar.addEventListener('submit', async e => {
    e.preventDefault();
    el('prestNombre').value = el('prestNombre').value.trim();
    formPrestar.classList.add('was-validated');
    if (!formPrestar.checkValidity()) return;

    const cantidad = parseInt(el('prestCantidad').value, 10);
    const boton = formPrestar.querySelector('[type="submit"]');
    boton.disabled = true;
    try {
        await api.prestarActivo(idPendiente, {
            prestatario: el('prestNombre').value,
            telefono: el('prestTelefono').value.trim(),
            cantidad,
            devolucion: el('prestDevolucion').value,
            nota: el('prestNota').value.trim()
        });
        await recargar();
        modales.prestar.hide();
        avisar(`Préstamo registrado (${unidades(cantidad)}).`);
    } catch (err) {
        avisar('No se pudo registrar el préstamo. Inténtalo de nuevo.', 'error');
    } finally {
        boton.disabled = false;
    }
});

/* ---------- Devolver (se elige qué préstamo se devuelve) ---------- */
function pintarDevoluciones(a) {
    el('devolverResumen').textContent = `${a.nombre} (${a.id})`;
    el('devolverLista').innerHTML = a.prestamos.map(p => `
        <div class="d-flex justify-content-between align-items-center gap-2 border rounded p-2 mb-2">
            <div>
                <strong>${esc(p.prestatario)}</strong>
                <div class="small text-secondary">${unidades(p.cantidad)} · desde ${esc(fechaCorta(p.desde))} · devuelve ${esc(fechaCorta(p.devolucion))}
                ${prestamoAtrasado(p) ? '<span class="badge text-bg-danger ms-1">Atrasado</span>' : ''}</div>
            </div>
            <button type="button" class="btn btn-success btn-sm text-nowrap" data-devolver="${esc(p.id)}">Registrar devolución</button>
        </div>`).join('');
}

function abrirDevolver(a) {
    pintarDevoluciones(a);
    modales.devolver.show();
}

el('devolverLista').addEventListener('click', async e => {
    const boton = e.target.closest('[data-devolver]');
    if (!boton) return;
    boton.disabled = true;
    try {
        await api.devolverPrestamo(boton.dataset.devolver);
        await recargar();
        const a = activos.find(x => x.id === idPendiente);
        if (a && a.prestamos.length > 0) pintarDevoluciones(a);
        else modales.devolver.hide();
        avisar('Devolución registrada.');
    } catch (err) {
        boton.disabled = false;
        avisar('No se pudo registrar la devolución. Inténtalo de nuevo.', 'error');
    }
});

/* ---------- Información ---------- */
function abrirInfo(a) {
    const d = disponibles(a);
    const prestamos = a.prestamos.length === 0
        ? '<p class="text-secondary mb-0">No hay préstamos activos.</p>'
        : a.prestamos.map(p => `
            <div class="border rounded p-2 mb-2">
                <strong>${esc(p.prestatario)}</strong> · ${unidades(p.cantidad)}
                ${prestamoAtrasado(p) ? '<span class="badge text-bg-danger ms-1">Atrasado</span>' : ''}
                <div class="small text-secondary">Desde ${esc(fechaCorta(p.desde))} · Devuelve ${esc(fechaCorta(p.devolucion))}</div>
                ${p.telefono ? `<div class="small text-secondary">Teléfono: ${esc(p.telefono)}</div>` : ''}
                ${p.nota ? `<div class="small text-secondary">Nota: ${esc(p.nota)}</div>` : ''}
            </div>`).join('');

    el('infoCuerpo').innerHTML = `
        <div class="d-flex gap-3 align-items-center mb-3">
            <div class="activo-img">${htmlImagen(a)}</div>
            <div>
                <div class="fw-bold fs-5 custom-text-blue">${esc(a.nombre)}</div>
                <div class="text-secondary">${esc(a.id)}</div>
            </div>
        </div>
        <dl class="row mb-3">
            <dt class="col-5">Categoría</dt><dd class="col-7">${esc(a.categoria)}</dd>
            <dt class="col-5">Estado</dt><dd class="col-7"><span class="badge ${claseEstado(a.estado)}">${esc(a.estado)}</span></dd>
            <dt class="col-5">Cantidad total</dt><dd class="col-7">${a.cantidad}</dd>
            <dt class="col-5">Disponibles</dt><dd class="col-7">${d}</dd>
            <dt class="col-5">Prestadas</dt><dd class="col-7">${totalPrestado(a)}</dd>
            <dt class="col-5">Descripción</dt><dd class="col-7">${a.descripcion ? esc(a.descripcion) : '<span class="text-secondary">Sin descripción</span>'}</dd>
        </dl>
        <h3 class="h6 fw-bold custom-text-blue">Préstamos activos</h3>
        ${prestamos}`;
    modales.info.show();
}

/* ---------- Inicio ---------- */
function iniciar() {
    el('regCategoria').innerHTML = '<option value="" selected disabled>Elige...</option>' +
        CATEGORIAS.map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join('');
    el('regEstado').innerHTML = '<option value="" selected disabled>Elige...</option>' +
        ESTADOS.map(s => `<option value="${esc(s)}">${esc(s)}</option>`).join('');

    crearFiltros();
    el('resumen').textContent = 'Cargando inventario...';
    recargar();
}

iniciar();