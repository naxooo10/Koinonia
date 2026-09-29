<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel | Koinonía</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .opciones {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 24px;
        }

        .opcion {
            display: block;
            padding: 24px;
            border: 1px solid #ddd;
            border-radius: 8px;
            color: #222;
            text-decoration: none;
        }

        .opcion:hover {
            background: #f3f3f3;
        }

        @media (max-width: 650px) {
            .opciones { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <h1>Koinonía — Etapa 1</h1>
    <p>Selecciona qué parte del sistema quieres mostrar.</p>

    <div class="opciones">
        <a class="opcion" href="/panel/casas">
            <strong>Casas Iglesia</strong>
            <p>Consultar, crear y editar casas.</p>
        </a>

        <a class="opcion" href="/panel/usuarios">
            <strong>Usuarios</strong>
            <p>Consultar, crear y editar usuarios.</p>
        </a>

        <a class="opcion" href="/panel/reuniones">
            <strong>Reuniones</strong>
            <p>Consultar reuniones y aplicar los seis filtros.</p>
        </a>

        <a class="opcion" href="/panel/consultas">
            <strong>Consultas SELECT</strong>
            <p>Ejecutar las tres consultas de la Etapa 1.</p>
        </a>

        <a class="opcion" href="/etapa1/sql">
            <strong>Ejercicios SQL</strong>
            <p>Mostrar la ejecución de ALTER, INSERT, UPDATE, DELETE y DROP.</p>
        </a>
    </div>
</body>
</html>