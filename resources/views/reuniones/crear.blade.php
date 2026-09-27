<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registrar reunión de prueba | Koinonía</title>
</head>
<body>
    <h1>Registrar reunión de prueba</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/reuniones">
        @csrf

        <p>Casa Centro Demo. Líder Demo.</p>
        <input type="hidden" name="id_casa" value="1">
        <input type="hidden" name="id_lider" value="2">

        <p>
            <label for="fecha">Fecha</label>
            <input id="fecha" name="fecha" type="date" required>
        </p>

        <p>
            <label for="tema">Tema</label>
            <input id="tema" name="tema" required maxlength="150">
        </p>

        <p>
            <label>
                <input type="checkbox" name="asistentes[]" value="3" required>
                Asistente Demo
            </label>
        </p>

        <button type="submit">Guardar reunión y asistencia</button>
    </form>
</body>
</html>