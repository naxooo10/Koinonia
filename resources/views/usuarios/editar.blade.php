<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Editar usuario | Koinonía</title>
</head>
<body>
    <h1>Editar usuario</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/usuarios/{{ $usuario->id_usuario }}">
        @csrf
        @method('PATCH')

        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre"
               value="{{ old('nombre', $usuario->nombre) }}" required>

        <label for="email">Correo</label>
        <input id="email" name="email" type="email"
               value="{{ old('email', $usuario->email) }}" required>

        <button type="submit">Guardar cambios</button>
    </form>
</body>
</html>
@if ($usuario->estado)
    <form method="POST" action="/usuarios/{{ $usuario->id_usuario }}/desactivar">
        @csrf
        @method('PATCH')
        <button type="submit">Desactivar usuario</button>
    </form>
@endif
