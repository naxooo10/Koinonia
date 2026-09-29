<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Casas Iglesia | Koinonía</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f3f3f3;
        }

        .error {
            color: #b00020;
        }
    </style>
</head>
<body>
    <h1>Casas Iglesia</h1>

    <p><a href="/casas/crear">+ Crear casa</a></p>
    <p id="mensaje">Cargando casas...</p>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="filas"></tbody>
    </table>

    <script>
        async function cargarCasas() {
            const mensaje = document.getElementById('mensaje');
            const filas = document.getElementById('filas');

            try {
                const respuesta = await fetch('/casas');

                if (!respuesta.ok) {
                    throw new Error('No se pudieron cargar las casas.');
                }

                const casas = await respuesta.json();
                filas.replaceChildren();

                for (const casa of casas) {
                    const fila = document.createElement('tr');

                    for (const valor of [
                        casa.nombre_casa,
                        casa.direccion,
                        casa.estado ? 'Activa' : 'Inactiva'
                    ]) {
                        const celda = document.createElement('td');
                        celda.textContent = valor ?? '';
                        fila.appendChild(celda);
                    }

                    const celdaAccion = document.createElement('td');
                    const enlace = document.createElement('a');

                    enlace.href = `/casas/${casa.id_casa}/editar`;
                    enlace.textContent = 'Editar';

                    celdaAccion.appendChild(enlace);
                    fila.appendChild(celdaAccion);
                    filas.appendChild(fila);
                }

                mensaje.textContent = casas.length
                    ? `${casas.length} casa(s) encontrada(s).`
                    : 'No hay casas registradas.';
            } catch (error) {
                mensaje.textContent = error.message;
                mensaje.className = 'error';
            }
        }

        cargarCasas();
    </script>
</body>
</html>