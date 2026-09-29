<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informe de reuniones | Koinonía</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        nav a { margin-right: 16px; }
        form { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 24px 0; }
        label { display: block; margin-bottom: 6px; font-weight: bold; }
        input, select { box-sizing: border-box; width: 100%; padding: 9px; }
        button { padding: 10px 18px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f3f3f3; }
        .acciones { align-self: end; }
        .error { color: #b00020; }

        @media (max-width: 700px) {
            form { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav>
        <a href="/panel/casas">Casas</a>
        <a href="/panel/usuarios">Usuarios</a>
        <a href="/reuniones/crear">+ Registrar reunión</a>
    </nav>

    <h1>Informe de reuniones</h1>
    <p>Selecciona el mes y el anio. Los demás filtros son opcionales.</p>

    <form id="filtros">
        <div>
            <label for="mes">Mes *</label>
            <select id="mes" name="mes" required>
                @foreach ([
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                ] as $numero => $nombre)
                    <option value="{{ $numero }}" @selected($numero === (int) now()->format('n'))>
                        {{ $nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="anio">Anio *</label>
            <input id="anio" name="anio" type="number"
                   min="2000" max="2100" value="{{ now()->year }}" required>
        </div>

        <div>
            <label for="id_lider">Líder</label>
            <select id="id_lider" name="id_lider">
                <option value="">Todos</option>
            </select>
        </div>

        <div>
            <label for="id_casa">Casa Iglesia</label>
            <select id="id_casa" name="id_casa">
                <option value="">Todas</option>
            </select>
        </div>

        <div>
            <label for="id_participante">Participante</label>
            <select id="id_participante" name="id_participante">
                <option value="">Todos</option>
            </select>
        </div>

        <div>
            <label for="cantidad_asistentes">Cantidad de asistentes</label>
            <input id="cantidad_asistentes" name="cantidad_asistentes"
                   type="number" min="0" placeholder="Cualquier cantidad">
        </div>

        <div class="acciones">
            <button type="submit">Consultar reuniones</button>
        </div>
    </form>

    <p id="mensaje">Selecciona los filtros y presiona «Consultar reuniones».</p>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tema</th>
                <th>Casa Iglesia</th>
                <th>Líder</th>
                <th>Asistentes</th>
            </tr>
        </thead>
        <tbody id="filas"></tbody>
    </table>

    <script>
        function agregarOpcion(select, valor, texto) {
            const opcion = document.createElement('option');
            opcion.value = valor;
            opcion.textContent = texto;
            select.appendChild(opcion);
        }

        async function cargarFiltros() {
            try {
                const [respuestaCasas, respuestaUsuarios] = await Promise.all([
                    fetch('/casas'),
                    fetch('/usuarios')
                ]);

                if (!respuestaCasas.ok || !respuestaUsuarios.ok) {
                    throw new Error('No se pudieron cargar las opciones de filtros.');
                }

                const casas = await respuestaCasas.json();
                const usuarios = await respuestaUsuarios.json();

                for (const casa of casas) {
                    agregarOpcion(
                        document.getElementById('id_casa'),
                        casa.id_casa,
                        casa.nombre_casa
                    );
                }

                for (const usuario of usuarios) {
                    if (usuario.nombre_rol === 'Líder') {
                        agregarOpcion(
                            document.getElementById('id_lider'),
                            usuario.id_usuario,
                            usuario.nombre
                        );
                    }

                    if (usuario.nombre_rol === 'Participante') {
                        agregarOpcion(
                            document.getElementById('id_participante'),
                            usuario.id_usuario,
                            usuario.nombre
                        );
                    }
                }
            } catch (error) {
                const mensaje = document.getElementById('mensaje');
                mensaje.textContent = error.message;
                mensaje.className = 'error';
            }
        }

        document.getElementById('filtros').addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const mensaje = document.getElementById('mensaje');
            const filas = document.getElementById('filas');
            const parametros = new URLSearchParams(
                new FormData(evento.currentTarget)
            );

            for (const [clave, valor] of parametros) {
                if (valor === '') parametros.delete(clave);
            }

            mensaje.className = '';
            mensaje.textContent = 'Consultando...';
            filas.replaceChildren();

            try {
                const respuesta = await fetch(
                    `/informes/reuniones?${parametros.toString()}`,
                    { headers: { Accept: 'application/json' } }
                );

                if (!respuesta.ok) {
                    throw new Error('No se pudo realizar la consulta.');
                }

                const reuniones = await respuesta.json();

                for (const reunion of reuniones) {
                    const fila = document.createElement('tr');

                    for (const valor of [
                        reunion.fecha,
                        reunion.tema,
                        reunion.nombre_casa,
                        reunion.nombre_lider ?? 'Sin líder asignado',
                        reunion.cantidad_asistentes
                    ]) {
                        const celda = document.createElement('td');
                        celda.textContent = valor;
                        fila.appendChild(celda);
                    }

                    filas.appendChild(fila);
                }

                mensaje.textContent = reuniones.length
                    ? `${reuniones.length} reunión(es) encontrada(s).`
                    : 'No se encontraron reuniones con esos filtros.';
            } catch (error) {
                mensaje.textContent = error.message;
                mensaje.className = 'error';
            }
        });

        cargarFiltros();
    </script>
</body>
</html>