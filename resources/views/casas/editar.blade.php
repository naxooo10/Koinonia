@extends('layouts.koinonia')

@section('titulo', 'Editar Casa Iglesia')
@section('icono', 'home')
@section('tipo', 'formulario-ui')
@section('descripcion', 'Actualiza la información y el liderazgo de esta comunidad.')

@section('encabezado')
Editar {{ $casa->nombre_casa }}
@endsection

@section('contenido')
<p>
        Estado actual:
        <strong>{{ $casa->estado ? 'Activa' : 'Inactiva' }}</strong>
    </p>

    @if ($errors->any())
        <ul class="errores" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/casas/{{ $casa->id_casa }}">
        @csrf
        @method('PATCH')

        <p>
            <label for="nombre_casa">Nombre</label>
            <input
                id="nombre_casa"
                name="nombre_casa"
                value="{{ old('nombre_casa', $casa->nombre_casa) }}"
                required
                maxlength="100"
            >
        </p>

        <p>
            <label for="direccion">Dirección</label>
            <input
                id="direccion"
                name="direccion"
                value="{{ old('direccion', $casa->direccion) }}"
                required
                maxlength="200"
            >
        </p>

        <p>
            <label for="id_lider_responsable">Líder responsable</label>
            <select id="id_lider_responsable" name="id_lider_responsable">
                <option value="">Sin líder asignado</option>

                @foreach ($lideres as $lider)
                    <option
                        value="{{ $lider->id_usuario }}"
                        @selected(
                            old('id_lider_responsable', $casa->id_lider_responsable)
                            == $lider->id_usuario
                        )
                    >
                        {{ $lider->nombre }}
                    </option>
                @endforeach
            </select>
        </p>

        <button type="submit">Guardar cambios</button>
    </form>

    <hr>

    @if ($casa->estado)
        <form data-ui-peligro method="POST"
              action="/casas/{{ $casa->id_casa }}/desactivar">
            @csrf
            @method('PATCH')

            <button type="submit">Desactivar casa</button>
        </form>
    @else
        <form data-ui-activar method="POST"
              action="/casas/{{ $casa->id_casa }}/activar">
            @csrf
            @method('PATCH')

            <button type="submit">Activar casa</button>
        </form>
    @endif

    <p>
        <a href="/panel/casas">Volver al listado de Casas</a>
    </p>
@endsection
