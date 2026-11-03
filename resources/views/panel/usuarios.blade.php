@extends('layouts.koinonia')

@section('titulo', 'Usuarios')
@section('icono', 'users')
@section('tipo', 'listado-ui')
@section('descripcion', 'Personas, líderes y participantes que forman parte de Koinonía.')

@section('encabezado')
Usuarios
@endsection

@section('contenido')
<p><a href="/panel/casas">← Casas Iglesia</a></p>
    

    <nav class="ui-herramientas"><a class="ui-primario" href="/usuarios/crear">+ Crear usuario</a></nav>
    <p id="mensaje">Cargando usuarios...</p>

    <div class="ui-listado-busqueda"><svg class="icono" aria-hidden="true"><use href="#i-search"/></svg><label class="solo-lectores" for="ui-buscar-listado">Filtrar el listado cargado</label><input id="ui-buscar-listado" data-ui-buscar type="search" placeholder="Buscar en este listado…" autocomplete="off" aria-describedby="ui-estado-listado"></div><p class="ui-listado-contador" id="ui-estado-listado" role="status" aria-live="polite"></p>
<div class="tabla-ui">
<table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Casas de pertenencia</th>
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
        async function cargarUsuarios() {
            const mensaje = document.getElementById('mensaje');
            const filas = document.getElementById('filas');

            try {
                const respuesta = await fetch('/usuarios');

                if (!respuesta.ok) {
                    throw new Error('No se pudieron cargar los usuarios.');
                }

                const usuarios = await respuesta.json();
                filas.replaceChildren();

                for (const usuario of usuarios) {
                    const fila = document.createElement('tr');

                    for (const valor of [
                        usuario.nombre,
                        usuario.email,
                        usuario.nombre_rol,
                        usuario.nombre_casa ?? 'Sin casa asignada',
                        usuario.estado ? 'Activo' : 'Inactivo'
                    ]) {
                        const celda = document.createElement('td');
                        celda.textContent = valor;
                        fila.appendChild(celda);
                    }

                    const celdaAccion = document.createElement('td');
                    const enlace = document.createElement('a');
                    enlace.href = `/usuarios/${usuario.id_usuario}/editar`;
                    enlace.textContent = 'Editar';

                    celdaAccion.appendChild(enlace);
                    fila.appendChild(celdaAccion);
                    filas.appendChild(fila);
                }

                mensaje.textContent = usuarios.length
                    ? `${usuarios.length} usuario(s) encontrado(s).`
                    : 'No hay usuarios registrados.';
            } catch (error) {
                mensaje.textContent = error.message;
                mensaje.className = 'error';
            }
        }

        cargarUsuarios();
    </script>
@endpush
