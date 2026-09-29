<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios | Koinonía</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f3f3f3; }
        .error { color: #b00020; }
    </style>
</head>
<body>
    <p><a href="/panel/casas">← Casas Iglesia</a></p>
    <h1>Usuarios</h1>

    <p><a href="/usuarios/crear">+ Crear usuario</a></p>
    <p id="mensaje">Cargando usuarios...</p>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Casa asignada</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="filas"></tbody>
    </table>

    <script>
        async function cargarUsuarios() {
            const mensaje = document.getElementById('mensaje');
            const filas = document.getElementById('filas');

            try {
                const respuesta = await fetch('/usuarios');

                if (!respuesta.ok) {
                    throw new Error('No se pudieron cargar los usuarios.');
                }

                const usuarios = await respuesta.json();
                filas.replaceChildren();

                for (const usuario of usuarios) {
                    const fila = document.createElement('tr');

                    for (const valor of [
                        usuario.nombre,
                        usuario.email,
                        usuario.nombre_rol,
                        usuario.nombre_casa ?? 'Sin casa asignada',
                        usuario.estado ? 'Activo' : 'Inactivo'
                    ]) {
                        const celda = document.createElement('td');
                        celda.textContent = valor;
                        fila.appendChild(celda);
                    }

                    const celdaAccion = document.createElement('td');
                    const enlace = document.createElement('a');
                    enlace.href = `/usuarios/${usuario.id_usuario}/editar`;
                    enlace.textContent = 'Editar';

                    celdaAccion.appendChild(enlace);
                    fila.appendChild(celdaAccion);
                    filas.appendChild(fila);
                }

                mensaje.textContent = usuarios.length
                    ? `${usuarios.length} usuario(s) encontrado(s).`
                    : 'No hay usuarios registrados.';
            } catch (error) {
                mensaje.textContent = error.message;
                mensaje.className = 'error';
            }
        }

        cargarUsuarios();
    </script>
</body>
</html>