(() => {
    'use strict';

    if (window.KoinoniaToast) return;

    const pendientes = [];
    const recientes = new Map();
    let region = null;

    function mostrar(mensaje, tipo = 'exito') {
        const texto = String(mensaje ?? '').trim().slice(0, 650);
        if (!texto) return;
        const categoria = tipo === 'error' ? 'error' : 'exito';
        if (!region) {
            pendientes.push([texto, categoria]);
            return;
        }

        const ahora = Date.now();
        for (const [clave, fecha] of recientes) {
            if (ahora - fecha > 2000) recientes.delete(clave);
        }
        const clave = categoria + ':' + texto;
        if (recientes.has(clave)) return;
        recientes.set(clave, ahora);

        const toast = document.createElement('div');
        toast.className = 'k-toast k-toast--' + categoria;
        toast.setAttribute('role', categoria === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-atomic', 'true');

        const icono = document.createElement('span');
        icono.className = 'k-toast-icono';
        icono.setAttribute('aria-hidden', 'true');
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        const trazo = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        trazo.setAttribute('d', categoria === 'error' ? 'M12 5v9M12 18h.01' : 'm5 12 4 4L19 6');
        svg.appendChild(trazo);
        icono.appendChild(svg);

        const contenido = document.createElement('div');
        contenido.className = 'k-toast-texto';
        const titulo = document.createElement('strong');
        titulo.className = 'k-toast-titulo';
        titulo.textContent = categoria === 'error' ? 'No se pudo completar' : '¡Listo!';
        const parrafo = document.createElement('p');
        parrafo.className = 'k-toast-mensaje';
        parrafo.textContent = texto;
        contenido.append(titulo, parrafo);

        const cerrar = document.createElement('button');
        cerrar.type = 'button';
        cerrar.className = 'k-toast-cerrar';
        cerrar.setAttribute('aria-label', 'Cerrar notificación');
        cerrar.textContent = '×';

        let temporizador = null;
        let restante = categoria === 'error' ? 10000 : 5500;
        let inicio = 0;
        let pausadoPorRaton = false;
        let pausadoPorFoco = false;

        function detener() {
            if (temporizador !== null) {
                clearTimeout(temporizador);
                restante = Math.max(0, restante - (Date.now() - inicio));
                temporizador = null;
            }
        }

        function eliminar() {
            detener();
            toast.remove();
        }

        function continuar() {
            if (pausadoPorRaton || pausadoPorFoco || temporizador !== null) return;
            inicio = Date.now();
            temporizador = setTimeout(eliminar, restante);
        }

        cerrar.addEventListener('click', eliminar);
        toast.addEventListener('mouseenter', () => { pausadoPorRaton = true; detener(); });
        toast.addEventListener('mouseleave', () => { pausadoPorRaton = false; continuar(); });
        toast.addEventListener('focusin', () => { pausadoPorFoco = true; detener(); });
        toast.addEventListener('focusout', evento => {
            if (!toast.contains(evento.relatedTarget)) {
                pausadoPorFoco = false;
                continuar();
            }
        });

        toast.append(icono, contenido, cerrar);
        if (region.children.length >= 4) region.firstElementChild.dispatchEvent(new Event('k-toast-cerrar'));
        toast.addEventListener('k-toast-cerrar', eliminar);
        region.appendChild(toast);
        continuar();
    }

    window.KoinoniaToast = {
        exito: mensaje => mostrar(mensaje, 'exito'),
        error: mensaje => mostrar(mensaje, 'error')
    };

    function iniciar() {
        region = document.createElement('div');
        region.className = 'k-toast-region';
        region.setAttribute('aria-label', 'Notificaciones');
        document.body.appendChild(region);

        const datos = document.getElementById('koinonia-toast-data');
        if (datos) {
            try {
                const avisos = JSON.parse(datos.textContent);
                if (Array.isArray(avisos)) {
                    for (const aviso of avisos) mostrar(aviso.mensaje, aviso.tipo);
                }
            } catch (_) {}
        }

        for (const [mensaje, tipo] of pendientes.splice(0)) mostrar(mensaje, tipo);
    }

    function mensajeDeError(estado, datos) {
        const mensajes = {
            401: 'Tu sesión no está disponible. Inicia sesión nuevamente.',
            403: 'No tienes permiso para realizar esta acción.',
            404: 'El recurso solicitado no está disponible.',
            413: 'El archivo o la solicitud supera el tamaño permitido.',
            419: 'Tu sesión venció. Recarga la página antes de volver a intentarlo.',
            422: 'Revisa los datos del formulario e inténtalo nuevamente.',
            429: 'Has realizado demasiados intentos. Espera un momento.'
        };

        if (estado >= 500) return 'No se pudo completar la acción. Inténtalo nuevamente.';
        if (estado === 419) return mensajes[419];
        const errores = Object.values(datos?.errors ?? {}).flat().filter(x => typeof x === 'string');
        return errores.slice(0, 3).join(' ') ||
            (typeof datos?.message === 'string' ? datos.message : '') ||
            mensajes[estado] || 'No se pudo completar la solicitud.';
    }

    function confirmacion(ruta, metodo, datos) {
        if (ruta === '/etapa1/sql/ejecutar' && metodo === 'POST') return 'Los ejercicios MongoDB finalizaron correctamente.';
        const coincidencia = ruta.match(/^\/(casas|usuarios|eventos|materiales|reuniones)(?:\/(\d+))?(?:\/(activar|desactivar))?\/?$/);
        if (!coincidencia || !['POST', 'PATCH', 'DELETE'].includes(metodo)) return null;
        if (typeof datos?.mensaje === 'string' && datos.mensaje.trim()) return datos.mensaje;

        const nombres = {casas: 'La Casa', usuarios: 'El usuario', eventos: 'El evento', materiales: 'El material', reuniones: 'La reunión'};
        const nombre = nombres[coincidencia[1]];
        if (coincidencia[3] === 'activar') return nombre + ' se activó correctamente.';
        if (coincidencia[3] === 'desactivar') return nombre + ' se desactivó correctamente.';
        if (metodo === 'POST' && !coincidencia[2]) return nombre + ' se creó correctamente.';
        if (metodo === 'PATCH' && coincidencia[2]) return nombre + ' se actualizó correctamente.';
        if (metodo === 'DELETE' && coincidencia[2]) return nombre + ' se eliminó correctamente.';
        return null;
    }

    if (typeof window.fetch === 'function') {
        const fetchOriginal = window.fetch.bind(window);
        window.fetch = async (entrada, opciones) => {
            let ruta;
            let metodo;
            let mismoOrigen;
            try {
                const esRequest = typeof Request !== 'undefined' && entrada instanceof Request;
                const url = new URL(esRequest ? entrada.url : String(entrada), window.location.href);
                mismoOrigen = url.origin === window.location.origin;
                ruta = url.pathname;
                metodo = String(opciones?.method ?? (esRequest ? entrada.method : 'GET')).toUpperCase();
                const cuerpo = opciones?.body;
                if (typeof FormData !== 'undefined' && cuerpo instanceof FormData && cuerpo.has('_method')) {
                    metodo = String(cuerpo.get('_method')).toUpperCase();
                }
            } catch (_) {
                return fetchOriginal(entrada, opciones);
            }

            let respuesta;
            try {
                respuesta = await fetchOriginal(entrada, opciones);
            } catch (error) {
                if (mismoOrigen && error?.name !== 'AbortError') {
                    mostrar('No pudimos conectar con el servidor. Revisa tu conexión e inténtalo nuevamente.', 'error');
                }
                throw error;
            }

            if (!mismoOrigen) return respuesta;

            const revisar = async () => {
                const tipo = respuesta.headers.get('content-type') ?? '';
                let datos = null;
                if (tipo.includes('application/json')) {
                    try { datos = await respuesta.clone().json(); } catch (_) {}
                }

                if (!respuesta.ok) {
                    mostrar(mensajeDeError(respuesta.status, datos), 'error');
                } else if (respuesta.redirected && new URL(respuesta.url).pathname === '/login') {
                    mostrar('Tu sesión venció. Inicia sesión nuevamente.', 'error');
                } else if (tipo.includes('application/json') && datos !== null && !respuesta.redirected) {
                    const mensaje = confirmacion(ruta, metodo, datos);
                    if (mensaje) mostrar(mensaje, 'exito');
                }
            };

            revisar().catch(() => {});
            return respuesta;
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, {once: true});
    } else {
        iniciar();
    }
})();
