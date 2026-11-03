@extends('layouts.koinonia')

@section('titulo', 'Casas Iglesias')
@section('icono', 'home')
@section('tipo', 'listado-ui')
@section('descripcion', 'Acompaña y organiza los lugares donde crece tu comunidad.')

@section('encabezado')
Casas Iglesia
@endsection

@section('contenido')
<nav class="ui-herramientas"><a class="ui-primario" href="/casas/crear">+ Crear casa</a></nav>
    <p id="mensaje">Cargando casas...</p>

    <div class="ui-listado-busqueda"><svg class="icono" aria-hidden="true"><use href="#i-search"/></svg><label class="solo-lectores" for="ui-buscar-listado">Filtrar el listado cargado</label><input id="ui-buscar-listado" data-ui-buscar type="search" placeholder="Buscar en este listado…" autocomplete="off" aria-describedby="ui-estado-listado"></div><p class="ui-listado-contador" id="ui-estado-listado" role="status" aria-live="polite"></p>
<div class="tabla-ui">
<table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="filas"></tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
        async function cargarCasas() {
            const mensaje = document.getElementById('mensaje');
            const filas = document.getElementById('filas');

            try {
                const respuesta = await fetch('/casas');

                if (!respuesta.ok) {
                    throw new Error('No se pudieron cargar las casas.');
                }

                const casas = await respuesta.json();
                filas.replaceChildren();

                for (const casa of casas) {
                    const fila = document.createElement('tr');

                    for (const valor of [
                        casa.nombre_casa,
                        casa.direccion,
                        casa.estado ? 'Activa' : 'Inactiva'
                    ]) {
                        const celda = document.createElement('td');
                        celda.textContent = valor ?? '';
                        fila.appendChild(celda);
                    }

                    const celdaAccion = document.createElement('td');
                    const enlace = document.createElement('a');

                    enlace.href = `/casas/${casa.id_casa}/editar`;
                    enlace.textContent = 'Editar';

                    celdaAccion.appendChild(enlace);
                    fila.appendChild(celdaAccion);
                    filas.appendChild(fila);
                }

                mensaje.textContent = casas.length
                    ? `${casas.length} casa(s) encontrada(s).`
                    : 'No hay casas registradas.';
            } catch (error) {
                mensaje.textContent = error.message;
                mensaje.className = 'error';
            }
        }

        cargarCasas();
    </script>
@endpush
