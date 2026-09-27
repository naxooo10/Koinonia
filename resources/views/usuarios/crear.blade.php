<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo usuario | Koinonía</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #1f2937;
            margin: 0;
            padding: 32px 16px;
        }

        main {
            max-width: 480px;
            margin: 0 auto;
            background: white;
            padding: 28px;
            border: 1px solid #d9e0e8;
            border-radius: 12px;
        }

        h1 {
            margin-top: 0;
        }

        .campo {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input, select {
            box-sizing: border-box;
            display: block;
            width: 100%;
            padding: 10px;
            border: 1px solid #aeb8c5;
            border-radius: 6px;
            font: inherit;
        }

        button {
            padding: 11px 18px;
            border: 0;
            border-radius: 6px;
            background: #2459c5;
            color: white;
            font: inherit;
            cursor: pointer;
        }

        .errores {
            color: #9b1c1c;
        }
    </style>
</head>
<body>
    <main>
        <h1>Nuevo usuario</h1>

        @if ($errors->any())
            <ul class="errores">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="/usuarios">
            @csrf

            <div class="campo">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre"
                       value="{{ old('nombre') }}" required>
            </div>

            <div class="campo">
                <label for="email">Correo</label>
                <input id="email" name="email" type="email"
                       value="{{ old('email') }}" required>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password"
                       minlength="8" required>
            </div>

            <div class="campo">
                <label for="id_rol">Rol</label>
                <select id="id_rol" name="id_rol" required>
                    <option value="">Selecciona un rol</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->id_rol }}"
                            @selected(old('id_rol') == $rol->id_rol)>
                            {{ $rol->nombre_rol }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="id_casa_asignada">Casa Iglesia</label>
                <select id="id_casa_asignada" name="id_casa_asignada">
                    <option value="">Sin casa asignada</option>
                    @foreach ($casas as $casa)
                        <option value="{{ $casa->id_casa }}"
                            @selected(old('id_casa_asignada') == $casa->id_casa)>
                            {{ $casa->nombre_casa }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit">Guardar usuario</button>
        </form>
    </main>
</body>
</html>