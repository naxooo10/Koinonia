@extends('layouts.koinonia')

@section('titulo', 'Nuevo usuario')
@section('icono', 'user-plus')
@section('tipo', 'formulario-ui')
@section('descripcion', 'Cada persona tiene un lugar en nuestra comunidad.')

@section('encabezado')
Nuevo usuario
@endsection

@section('contenido')
@if ($errors->any())
            <div class="errores" role="alert">
                <p>Revisa los siguientes campos:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="/usuarios">
            @csrf

            <div class="campo">
                <label for="nombre">Nombre *</label>
                <input
                    id="nombre"
                    name="nombre"
                    type="text"
                    maxlength="100"
                    autocomplete="name"
                    value="{{ old('nombre') }}"
                    required
                >
            </div>

            <div class="campo">
                <label for="email">Correo *</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="100"
                    autocomplete="email"
                    value="{{ old('email') }}"
                    required
                >
            </div>

            <div class="campo">
                <label for="password">Contraseña *</label>
                <p id="ayuda-password" class="ayuda">
                    Debe tener al menos 8 caracteres.
                </p>
                <input
                    id="password"
                    name="password"
                    type="password"
                    minlength="8"
                    autocomplete="new-password"
                    aria-describedby="ayuda-password"
                    required
                >
            </div>

            <div class="campo">
                <label for="id_rol">Rol *</label>
                <select id="id_rol" name="id_rol" required>
                    <option value="">Selecciona un rol</option>

                    @foreach ($roles as $rol)
                        <option
                            value="{{ $rol->id_rol }}"
                            @selected(old('id_rol') == $rol->id_rol)
                        >
                            {{ $rol->nombre_rol }}
                        </option>
                    @endforeach
                </select>
            </div>

            <fieldset aria-describedby="ayuda-casas">
                <legend>Casas de pertenencia</legend>

                <p id="ayuda-casas" class="ayuda">
                    Puedes seleccionar varias Casas o dejar al usuario
                    sin pertenencias. Esta selección no asigna
                    responsabilidades de liderazgo.
                </p>

                @forelse ($casas as $casa)
                    <label class="opcion-casa">
                        <input
                            type="checkbox"
                            name="casas_ids[]"
                            value="{{ $casa->id_casa }}"
                            @checked(in_array(
                                (string) $casa->id_casa,
                                array_map(
                                    'strval',
                                    (array) old('casas_ids', [])
                                ),
                                true
                            ))
                        >
                        <span>{{ $casa->nombre_casa }}</span>
                    </label>
                @empty
                    <p>No hay Casas activas disponibles.</p>
                @endforelse
            </fieldset>

            <p class="ayuda">* Campos obligatorios.</p>

            <div class="acciones">
                <button type="submit">Guardar usuario</button>
                <a href="/panel/usuarios">Cancelar</a>
            </div>
        </form>
@endsection
