@extends('layouts.koinonia')

@section('titulo', 'Consultas MongoDB')
@section('icono', 'database')
@section('tipo', 'listado-ui')
@section('descripcion', 'Explora los datos de Casas, líderes, asistentes y reuniones.')

@section('encabezado')
Consultas MongoDB
@endsection

@section('contenido')
<nav aria-label="Navegación principal">
        <a href="/panel">Panel principal</a>
        <a href="/panel/casas">Casas</a>
        <a href="/panel/usuarios">Usuarios</a>
        <a href="/panel/reuniones">Informe de reuniones</a>
    </nav>

    
    <p>Selecciona una consulta para ejecutarla desde la aplicación.</p>

    <div class="consultas-selector" role="group" aria-label="Consultas disponibles">
<button
        type="button"
        data-url="/etapa1/consultas/1"
        data-titulo="Casas activas"
    >
        1. Casas activas
    </button>

    <button
        type="button"
        data-url="/etapa1/consultas/2"
        data-titulo="Líderes por Casa"
    >
        2. Líderes por Casa
    </button>

    <button
        type="button"
        data-url="/etapa1/consultas/3"
        data-titulo="Asistentes por reunión"
    >
        3. Asistentes por reunión
    </button>

    <button
        type="button"
        data-url="/etapa1/consultas/4"
        data-titulo="Resumen por Casa"
    >
        4. Resumen por Casa
    </button>
</div>

    <h2 id="titulo">Resultado</h2>

    <p id="mensaje" role="status" aria-live="polite">
        Elige una consulta.
    </p>

    <div
        class="tabla"
        role="region"
        aria-labelledby="titulo"
        tabindex="0"
    >
        <table id="tabla-resultados" hidden>
            <thead>
                <tr id="encabezados"></tr>
            </thead>
            <tbody id="filas"></tbody>
        </table>
    </div>
@endsection

@push('scripts')
<script>
        const nombres = {
            id_casa: 'ID Casa',
            nombre_casa: 'Casa Iglesia',
            direccion: 'Dirección',
            nombre_lider: 'Líder',
            id_reunion: 'ID reunión',
            fecha: 'Fecha',
            tema: 'Tema',
            nombre_asistente: 'Asistente',
            cantidad_reuniones: 'Cantidad de reuniones',
            total_presentes: 'Total de presencias'
        };

        const botones = document.querySelectorAll('button[data-url]');

        async function ejecutarConsulta(boton) {
            const titulo = document.getElementById('titulo');
            const mensaje = document.getElementById('mensaje');
            const encabezados = document.getElementById('encabezados');
            const filas = document.getElementById('filas');
            const tabla = document.getElementById('tabla-resultados');

            titulo.textContent = boton.dataset.titulo;
            mensaje.className = '';
            mensaje.textContent = 'Consultando...';
            encabezados.replaceChildren();
            filas.replaceChildren();
            tabla.hidden = true;

            botones.forEach(elemento => {
                elemento.disabled = true;
            });

            try {
                const respuesta = await fetch(boton.dataset.url, {
                    headers: { Accept: 'application/json' }
                });

                const tipo = respuesta.headers.get('content-type') ?? '';

                if (!tipo.includes('application/json')) {
                    throw new Error(
                        'El servidor no devolvió una respuesta JSON. ' +
                        'Comprueba que la ruta esté configurada.'
                    );
                }

                const resultados = await respuesta.json();

                if (!respuesta.ok) {
                    const errores = Object.values(
                        resultados.errors ?? {}
                    ).flat();

                    throw new Error(
                        errores.join(' ') ||
                        resultados.message ||
                        'No se pudo ejecutar la consulta.'
                    );
                }

                if (!Array.isArray(resultados)) {
                    throw new Error(
                        'La respuesta tiene un formato inesperado.'
                    );
                }

                if (resultados.length === 0) {
                    mensaje.textContent =
                        'La consulta se ejecutó, pero no encontró registros.';
                    return;
                }

                const columnas = Object.keys(resultados[0]);

                for (const columna of columnas) {
                    const th = document.createElement('th');
                    th.scope = 'col';
                    th.textContent = nombres[columna] ?? columna;
                    encabezados.appendChild(th);
                }

                for (const resultado of resultados) {
                    const fila = document.createElement('tr');

                    for (const columna of columnas) {
                        const celda = document.createElement('td');
                        celda.textContent = resultado[columna] ?? '—';
                        fila.appendChild(celda);
                    }

                    filas.appendChild(fila);
                }

                tabla.hidden = false;

                mensaje.textContent =
                    `${resultados.length} registro(s) encontrado(s).`;

                if (boton.dataset.url === '/etapa1/consultas/4') {
                    mensaje.textContent +=
                        ' Se incluyen las Casas con reuniones registradas. ' +
                        'Las presencias se suman entre reuniones; ' +
                        'no representan personas únicas.';
                }
            } catch (error) {
                mensaje.textContent = error.message;
                mensaje.className = 'error';
            } finally {
                botones.forEach(elemento => {
                    elemento.disabled = false;
                });
            }
        }

        botones.forEach(boton => {
            boton.addEventListener('click', () => ejecutarConsulta(boton));
        });
    </script>
@endpush
