@extends('layouts.koinonia')

@section('titulo', 'Editar usuario')
@section('icono', 'users')
@section('tipo', 'formulario-ui')
@section('descripcion', 'Administra su información, rol y Casas de pertenencia.')

@section('encabezado')
Editar usuario
@endsection

@section('contenido')
<a href="/panel/usuarios">← Volver a usuarios</a>

        

        <p class="estado">
            Estado:
            <strong>{{ $usuario->estado ? 'Activo' : ($usuario->pendiente ? 'Pendiente de aprobación' : 'Inactivo') }}</strong>
        </p>

        @if ($errors->any())
            <ul class="errores">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        @php
            $casasSeleccionadas = session()->hasOldInput('actualizar_casas')
                ? (array) old('casas_ids', [])
                : (array) ($usuario->casas_ids ?? []);

            $casasSeleccionadas = array_map('intval', $casasSeleccionadas);
        @endphp

        <form method="POST" action="/usuarios/{{ $usuario->id_usuario }}">
            @csrf
            @method('PATCH')

            <input type="hidden" name="actualizar_casas" value="1">

            <div class="campo">
                <label for="nombre">Nombre</label>
                <input
                    id="nombre"
                    name="nombre"
                    type="text"
                    maxlength="100"
                    value="{{ old('nombre', $usuario->nombre) }}"
                    required
                >
            </div>

            <div class="campo">
                <label for="email">Correo</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="100"
                    value="{{ old('email', $usuario->email) }}"
                    required
                >
            </div>

            <div class="campo">
                <label for="password">Nueva contraseña (opcional)</label>
                <p id="ayuda-password" class="ayuda">
                    Déjala en blanco para conservar la actual. Si la
                    cambias, debe tener al menos 8 caracteres e incluir
                    una letra y un número, y se cerrarán las sesiones
                    que esta persona tenga abiertas.
                </p>
                <input
                    id="password"
                    name="password"
                    type="password"
                    minlength="8"
                    maxlength="200"
                    autocomplete="new-password"
                    aria-describedby="ayuda-password"
                >
            </div>

            <div class="campo">
                <label for="id_rol">Rol</label>

                <select id="id_rol" name="id_rol" required>
                    <option value="">Selecciona un rol</option>

                    @foreach ($roles as $rol)
                        <option
                            value="{{ $rol->id_rol }}"
                            @selected(
                                (int) old('id_rol', $usuario->id_rol)
                                === (int) $rol->id_rol
                            )
                        >
                            {{ $rol->nombre_rol }}
                        </option>
                    @endforeach
                </select>
            </div>

            <fieldset>
                <legend>Casas de pertenencia</legend>

                <p class="ayuda">
                    Puedes seleccionar varias Casas o dejar al usuario sin
                    pertenencias. La responsabilidad como líder se asigna
                    desde la edición de cada Casa.
                </p>

                @forelse ($casas as $casa)
                    <label class="opcion-casa">
                        <input
                            type="checkbox"
                            name="casas_ids[]"
                            value="{{ $casa->id_casa }}"
                            @checked(
                                in_array(
                                    (int) $casa->id_casa,
                                    $casasSeleccionadas,
                                    true
                                )
                            )
                        >

                        <span>
                            {{ $casa->nombre_casa }}

                            @if (! $casa->estado)
                                — Inactiva
                            @endif
                        </span>
                    </label>
                @empty
                    <p>No hay Casas disponibles.</p>
                @endforelse
            </fieldset>

            <button type="submit">Guardar cambios</button>
        </form>

        <section class="desactivacion">
            @if ($usuario->estado)
                <h2>Desactivar usuario</h2>

                <p class="ayuda">
                    El usuario no podrá iniciar sesión. Sus datos y registros
                    de asistencia se conservarán.
                </p>

                <form data-ui-peligro
                    method="POST"
                    action="/usuarios/{{ $usuario->id_usuario }}/desactivar"
                >
                    @csrf
                    @method('PATCH')

                    <button type="submit" class="boton-desactivar">
                        Desactivar usuario
                    </button>
                </form>
            @elseif ($usuario->pendiente)
                <h2>Aprobar cuenta</h2>

                <p class="ayuda">
                    Esta persona creó su cuenta desde el registro público.
                    Revisa su rol y sus Casas, guarda los cambios y luego
                    apruébala para que pueda iniciar sesión.
                </p>

                <form data-ui-activar
                    method="POST"
                    action="/usuarios/{{ $usuario->id_usuario }}/activar"
                >
                    @csrf
                    @method('PATCH')

                    <button type="submit" class="boton-activar">
                        Aprobar cuenta
                    </button>
                </form>
            @else
                <h2>Activar usuario</h2>

                <p class="ayuda">
                    El usuario podrá volver a iniciar sesión con su cuenta.
                </p>

                <form data-ui-activar
                    method="POST"
                    action="/usuarios/{{ $usuario->id_usuario }}/activar"
                >
                    @csrf
                    @method('PATCH')

                    <button type="submit" class="boton-activar">
                        Activar usuario
                    </button>
                </form>
            @endif
        </section>
@endsection
