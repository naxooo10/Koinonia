<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva Casa Iglesia | Koinonía</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
        }

        label {
            display: block;
            margin: 18px 0 6px;
            font-weight: bold;
        }

        input {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
        }

        button {
            margin-top: 24px;
            padding: 10px 18px;
            cursor: pointer;
        }

        .errores {
            color: #b00020;
            background: #fff1f1;
            padding: 12px 16px;
        }
    </style>
</head>
<body>
    <p><a href="/panel/casas">← Volver a Casas Iglesia</a></p>

    <h1>Nueva Casa Iglesia</h1>

    @if ($errors->any())
        <ul class="errores">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/casas">
        @csrf

        <label for="nombre_casa">Nombre de la casa</label>
        <input
            id="nombre_casa"
            name="nombre_casa"
            value="{{ old('nombre_casa') }}"
            maxlength="100"
            required
        >

        <label for="direccion">Dirección</label>
        <input
            id="direccion"
            name="direccion"
            value="{{ old('direccion') }}"
            maxlength="200"
            required
        >

        <button type="submit">Guardar casa</button>
    </form>
</body>
</html>