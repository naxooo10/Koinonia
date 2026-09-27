<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nueva Casa Iglesia | Koinonía</title>
</head>
<body>
    <h1>Nueva Casa Iglesia</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/casas">
        @csrf

        <label for="nombre_casa">Nombre</label>
        <input id="nombre_casa" name="nombre_casa"
               value="{{ old('nombre_casa') }}" required maxlength="100">

        <label for="direccion">Dirección</label>
        <input id="direccion" name="direccion"
               value="{{ old('direccion') }}" required maxlength="200">

        <button type="submit">Guardar casa</button>
    </form>
</body>
</html>