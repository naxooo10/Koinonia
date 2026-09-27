<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Editar Casa Iglesia | Koinonía</title>
</head>
<body>
    <h1>Editar {{ $casa->nombre_casa }}</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/casas/{{ $casa->id_casa }}">
        @csrf
        @method('PATCH')

        <label for="nombre_casa">Nombre</label>
        <input id="nombre_casa" name="nombre_casa"
               value="{{ old('nombre_casa', $casa->nombre_casa) }}"
               required maxlength="100">

        <label for="direccion">Dirección</label>
        <input id="direccion" name="direccion"
               value="{{ old('direccion', $casa->direccion) }}"
               required maxlength="200">

        <button type="submit">Guardar cambios</button>
    </form>
    @if ($casa->estado)
    <form method="POST" action="/casas/{{ $casa->id_casa }}/desactivar">
        @csrf
        @method('PATCH')

        <button type="submit">Desactivar casa</button>
    </form>
@endif
</body>
</html>