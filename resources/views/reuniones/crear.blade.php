<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registrar reunión | Koinonía</title>
</head>
<body>
    <h1>Registrar reunión y asistencia</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="GET" action="/reuniones/crear">
        <label for="casa">Casa Iglesia</label>

        <select id="casa" name="id_casa" required>
            <option value="">Selecciona una casa</option>

            @foreach ($casas as $opcion)
                <option value="{{ $opcion->id_casa }}"
                    @selected(($casa->id_casa ?? null) == $opcion->id_casa)>
                    {{ $opcion->nombre_casa }}
                </option>
            @endforeach
        </select>

        <button type="submit">Cargar participantes</button>
    </form>

    @if ($casa)
        <h2>{{ $casa->nombre_casa }}</h2>

        @if (! $lider)
            <p>Esta casa necesita un líder responsable activo.</p>

            <a href="/casas/{{ $casa->id_casa }}/editar">
                Asignar líder
            </a>
        @elseif ($participantes->isEmpty())
            <p>Esta casa todavía no tiene participantes activos.</p>
        @else
            <p>Líder responsable: {{ $lider->nombre }}</p>

            <form method="POST" action="/reuniones">
                @csrf

                <input type="hidden" name="id_casa"
                       value="{{ $casa->id_casa }}">

                <input type="hidden" name="id_lider"
                       value="{{ $lider->id_usuario }}">

                <p>
                    <label for="fecha">Fecha</label>
                    <input id="fecha" name="fecha" type="date"
                           value="{{ old('fecha') }}" required>
                </p>

                <p>
                    <label for="tema">Tema</label>
                    <input id="tema" name="tema"
                           value="{{ old('tema') }}"
                           required maxlength="150">
                </p>

                <fieldset>
                    <legend>Selecciona al menos un asistente</legend>

                    @foreach ($participantes as $participante)
                        <p>
                            <label>
                                <input type="checkbox"
                                       name="asistentes[]"
                                       value="{{ $participante->id_usuario }}"
                                       @checked(in_array(
                                           $participante->id_usuario,
                                           (array) old('asistentes', [])
                                       ))>
                                {{ $participante->nombre }}
                            </label>
                        </p>
                    @endforeach
                </fieldset>

                <button type="submit">
                    Guardar reunión y asistencia
                </button>
            </form>
        @endif
    @endif
</body>
</html>