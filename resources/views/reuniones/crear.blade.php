<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrar reunión | Koinonía</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        label {
            display: inline-block;
            margin: 8px 0;
        }

        input[type="date"],
        input[type="text"],
        select {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
        }

        button {
            padding: 10px 16px;
            margin-top: 12px;
            cursor: pointer;
        }

        fieldset {
            margin-top: 20px;
            padding: 15px;
        }

        .error {
            color: #b00020;
        }

        .exito {
            margin-top: 20px;
            padding: 16px;
            background: #e7f6e7;
            border: 1px solid #89c489;
        }
    </style>
</head>
<body>
    <p><a href="/panel">← Volver al panel</a></p>

    <h1>Registrar reunión y asistencia</h1>

    @if ($errors->any())
        <ul class="error">
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
            <a href="/casas/{{ $casa->id_casa }}/editar">Asignar líder</a>
        @elseif ($participantes->isEmpty())
            <p>Esta casa todavía no tiene participantes activos.</p>
        @else
            <p>Líder responsable: {{ $lider->nombre }}</p>

            <form id="formulario-reunion" method="POST" action="/reuniones">
                @csrf

                <input type="hidden" name="id_casa" value="{{ $casa->id_casa }}">
                <input type="hidden" name="id_lider" value="{{ $lider->id_usuario }}">

                <p>
                    <label for="fecha">Fecha</label><br>
                    <input id="fecha" name="fecha" type="date"
                           value="{{ old('fecha') }}" required>
                </p>

                <p>
                    <label for="tema">Tema</label><br>
                    <input id="tema" name="tema" type="text"
                           value="{{ old('tema') }}"
                           maxlength="150" required>
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

                <button type="submit">Guardar reunión y asistencia</button>
            </form>

            <div id="resultado" role="status" aria-live="polite"></div>
        @endif
    @endif

    <script>
        const formulario = document.getElementById('formulario-reunion');

        if (formulario) {
            formulario.addEventListener('submit', async (evento) => {
                evento.preventDefault();

                const resultado = document.getElementById('resultado');
                const boton = formulario.querySelector('button[type="submit"]');
                const seleccionados = formulario.querySelectorAll(
                    'input[name="asistentes[]"]:checked'
                );

                resultado.replaceChildren();
                resultado.className = '';

                if (seleccionados.length === 0) {
                    resultado.textContent = 'Selecciona al menos un asistente.';
                    resultado.className = 'error';
                    return;
                }

                boton.disabled = true;
                boton.textContent = 'Guardando...';

                try {
                    const respuesta = await fetch(formulario.action, {
                        method: 'POST',
                        body: new FormData(formulario),
                        headers: { Accept: 'application/json' }
                    });

                    const datos = await respuesta.json();

                    if (!respuesta.ok) {
                        const errores = Object.values(datos.errors ?? {}).flat();
                        throw new Error(
                            errores.join(' ') || datos.message || 'No se pudo guardar la reunión.'
                        );
                    }

                    resultado.className = 'exito';

                    const mensaje = document.createElement('p');
                    mensaje.textContent =
                        `Reunión #${datos.id_reunion} guardada. ` +
                        `Asistentes registrados: ${datos.asistentes_registrados}.`;

                    const enlace = document.createElement('a');
                    enlace.href = '/panel/reuniones';
                    enlace.textContent = 'Ver informe de reuniones';

                    resultado.append(mensaje, enlace);
                    formulario.hidden = true;
                } catch (error) {
                    resultado.textContent = error.message;
                    resultado.className = 'error';
                } finally {
                    boton.disabled = false;
                    boton.textContent = 'Guardar reunión y asistencia';
                }
            });
        }
    </script>
</body>
</html>