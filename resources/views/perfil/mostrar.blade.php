@extends('layouts.koinonia')

@section('titulo', 'Mi perfil')
@section('icono', 'users')
@section('tipo', 'formulario-ui')
@section('descripcion', 'Revisa tus datos y mantén segura tu cuenta.')

@section('encabezado')
Mi perfil
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

        <fieldset>
            <legend>Mi cuenta</legend>

            <p class="estado">Rol: <strong>{{ $perfil->nombre_rol }}</strong></p>

            <p class="estado">
                Casas de pertenencia:
                @forelse ($casas as $casa)
                    <strong>{{ $casa->nombre_casa }}{{ $casa->estado ? '' : ' (inactiva)' }}{{ $loop->last ? '' : ', ' }}</strong>
                @empty
                    <strong>Sin Casas asignadas</strong>
                @endforelse
            </p>

            <p class="ayuda">
                El rol y las Casas los administra el equipo de administración.
            </p>
        </fieldset>

        <form method="POST" action="/perfil">
            @csrf
            @method('PATCH')

            <fieldset>
                <legend>Mis datos</legend>

                <div class="campo">
                    <label for="nombre">Nombre *</label>
                    <input
                        id="nombre"
                        name="nombre"
                        type="text"
                        maxlength="100"
                        autocomplete="name"
                        value="{{ old('nombre', $perfil->nombre) }}"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="email">Correo *</label>
                    <p id="ayuda-email" class="ayuda">
                        Es el correo con el que inicias sesión.
                    </p>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        maxlength="100"
                        autocomplete="email"
                        aria-describedby="ayuda-email"
                        value="{{ old('email', $perfil->email) }}"
                        required
                    >
                </div>

                <div class="acciones">
                    <button type="submit">Guardar mis datos</button>
                </div>
            </fieldset>
        </form>

        <form method="POST" action="/perfil/contrasena">
            @csrf
            @method('PATCH')

            <fieldset>
                <legend>Cambiar contraseña</legend>

                <p class="ayuda">
                    Al cambiarla se cerrarán tus sesiones abiertas en otros
                    dispositivos. En este seguirás conectado.
                </p>

                <div class="campo">
                    <label for="password_actual">Contraseña actual *</label>
                    <input
                        id="password_actual"
                        name="password_actual"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="password">Nueva contraseña *</label>
                    <p id="ayuda-password" class="ayuda">{{ $ayudaContrasena }}</p>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        minlength="8"
                        maxlength="200"
                        autocomplete="new-password"
                        aria-describedby="ayuda-password"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="password_confirmation">Repite la nueva contraseña *</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        minlength="8"
                        maxlength="200"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="acciones">
                    <button type="submit">Cambiar contraseña</button>
                </div>
            </fieldset>
        </form>

        <p class="ayuda">* Campos obligatorios.</p>
@endsection
