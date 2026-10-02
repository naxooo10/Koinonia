<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Editar Casa Iglesia | Koinonía</title>
</head>
<body>
    <h1>Editar {{ $casa->nombre_casa }}</h1>

    <p>
        Estado actual:
        <strong>{{ $casa->estado ? 'Activa' : 'Inactiva' }}</strong>
    </p>

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
        <form method="POST"
              action="/casas/{{ $casa->id_casa }}/desactivar">
            @csrf
            @method('PATCH')

            <button type="submit">Desactivar casa</button>
        </form>
    @else
        <form method="POST"
              action="/casas/{{ $casa->id_casa }}/activar">
            @csrf
            @method('PATCH')

            <button type="submit">Activar casa</button>
        </form>
    @endif

    <p>
        <a href="/panel/casas">Volver al listado de Casas</a>
    </p>
</body>
</html>