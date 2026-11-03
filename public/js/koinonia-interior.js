(() => {
    'use strict';

    const botonMenu = document.querySelector('[data-ui-menu]');
    botonMenu?.addEventListener('click', () => {
        const contraido = document.body.classList.toggle('menu-compacto');
        botonMenu.setAttribute('aria-expanded', String(!contraido));
        botonMenu.setAttribute('aria-label', contraido ? 'Expandir menú' : 'Contraer menú');
    });

    const perfil = document.querySelector('.perfil');
    document.addEventListener('click', evento => {
        if (perfil && !perfil.contains(evento.target)) perfil.open = false;
    });
    document.addEventListener('keydown', evento => {
        if (evento.key === 'Escape' && perfil) perfil.open = false;
    });

    for (const boton of document.querySelectorAll('[data-toggle-password]')) {
        boton.addEventListener('click', () => {
            const campo = document.getElementById(boton.dataset.togglePassword);
            if (!campo) return;
            const visible = campo.type === 'password';
            campo.type = visible ? 'text' : 'password';
            boton.textContent = visible ? 'Ocultar' : 'Mostrar';
            boton.setAttribute('aria-pressed', String(visible));
        });
    }

    const contenido = document.querySelector('.pagina-contenido');
    if (!contenido) return;

    for (const boton of contenido.querySelectorAll('button[data-url]')) {
        boton.setAttribute('aria-pressed', 'false');
        boton.addEventListener('click', () => {
            for (const otro of contenido.querySelectorAll('button[data-url]')) {
                otro.setAttribute('aria-pressed', String(otro === boton));
            }
        });
    }

    const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const buscador = document.querySelector('[data-ui-buscar]');
    const estado = document.getElementById('ui-estado-listado');
    const aplicarBusqueda = () => {
        if (!buscador) return;
        const texto = normalizar(buscador.value.trim());
        const filas = contenido.querySelectorAll('table tbody tr');
        let visibles = 0;
        for (const fila of filas) {
            const coincide = normalizar(fila.textContent).includes(texto);
            fila.hidden = !coincide;
            if (coincide) visibles++;
        }
        if (estado) estado.textContent = texto ? `${visibles} coincidencia(s) en el listado cargado.` : '';
    };
    buscador?.addEventListener('input', aplicarBusqueda);

    const decorarEstados = () => {
        for (const celda of contenido.querySelectorAll('td')) {
            if (celda.children.length !== 0) continue;
            const texto = celda.textContent.trim();
            if (!['Activo', 'Activa', 'Inactivo', 'Inactiva'].includes(texto)) continue;
            const etiqueta = document.createElement('span');
            etiqueta.className = 'ui-estado ' + (texto.startsWith('Inactiv') ? 'ui-estado--inactivo' : 'ui-estado--activo');
            etiqueta.textContent = texto;
            celda.replaceChildren(etiqueta);
        }
    };

    const observador = new MutationObserver(() => {
        decorarEstados();
        aplicarBusqueda();
    });
    for (const tabla of contenido.querySelectorAll('table')) {
        observador.observe(tabla, {childList: true, subtree: true});
    }
    decorarEstados();
    aplicarBusqueda();
})();
