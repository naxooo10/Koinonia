<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consultas de la Etapa 1 | Koinonía</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        nav a { margin-right: 16px; }
        button { padding: 10px 14px; margin: 5px 8px 5px 0; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f3f3f3; }
        .error { color: #b00020; }
    </style>
</head>
<body>
    <nav>
        <a href="/panel/casas">Casas</a>
        <a href="/panel/usuarios">Usuarios</a>
        <a href="/panel/reuniones">Informe de reuniones</a>
    </nav>

    <h1>Consultas SELECT — Etapa 1</h1>
    <p>Selecciona una consulta para ejecutarla desde la aplicación.</p>

    <button type="button" data-url="/etapa1/consultas/1" data-titulo="Casas activas">
        1. Casas activas
    </button>

    <button type="button" data-url="/etapa1/consultas/2" data-titulo="Líderes por casa">
        2. Líderes por casa
    </button>

    <button type="button" data-url="/etapa1/consultas/3" data-titulo="Asistentes por reunión">
        3. Asistentes por reunión
    </button>

    <h2 id="titulo">Resultado</h2>
    <p id="mensaje">Elige una consulta.</p>

    <table>
        <thead><tr id="encabezados"></tr></thead>
        <tbody id="filas"></tbody>
    </table>

    <script>
        const nombres = {
            id_casa: 'ID casa',
            nombre_casa: 'Casa Iglesia',
            direccion: 'Dirección',
            nombre_lider: 'Líder',
            id_reunion: 'ID reunión',
            fecha: 'Fecha',
            tema: 'Tema',
            nombre_asistente: 'Asistente'
        };

        async function ejecutarConsulta(boton) {
            const titulo = document.getElementById('titulo');
            const mensaje = document.getElementById('mensaje');
            const encabezados = document.getElementById('encabezados');
            const filas = document.getElementById('filas');

            titulo.textContent = boton.dataset.titulo;
            mensaje.className = '';
            mensaje.textContent = 'Consultando...';
            encabezados.replaceChildren();
            filas.replaceChildren();

            try {
                const respuesta = await fetch(boton.dataset.url);

                if (!respuesta.ok) {
                    throw new Error('No se pudo ejecutar la consulta.');
                }

                const resultados = await respuesta.json();

                if (resultados.length === 0) {
                    mensaje.textContent = 'La consulta se ejecutó, pero no encontró registros.';
                    return;
                }

                const columnas = Object.keys(resultados[0]);

                for (const columna of columnas) {
                    const th = document.createElement('th');
                    th.textContent = nombres[columna] ?? columna;
                    encabezados.appendChild(th);
                }

                for (const resultado of resultados) {
                    const fila = document.createElement('tr');

                    for (const columna of columnas) {
                        const celda = document.createElement('td');
                        celda.textContent = resultado[columna] ?? '—';
                        fila.appendChild(celda);
                    }

                    filas.appendChild(fila);
                }

                mensaje.textContent = `${resultados.length} registro(s) encontrado(s).`;
            } catch (error) {
                mensaje.textContent = error.message;
                mensaje.className = 'error';
            }
        }

        document.querySelectorAll('button[data-url]').forEach(boton => {
            boton.addEventListener('click', () => ejecutarConsulta(boton));
        });
    </script>
</body>
</html>