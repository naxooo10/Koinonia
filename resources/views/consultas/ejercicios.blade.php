@extends('layouts.koinonia')

@section('titulo', 'Ejercicios MongoDB')
@section('icono', 'layers')
@section('tipo', 'listado-ui')
@section('descripcion', 'Demostración de operaciones sobre una colección temporal.')

@section('encabezado')
Ejercicios MongoDB
@endsection

@section('contenido')
<p><a href="/panel">Volver al panel</a></p>

    

    <p>
        La prueba utiliza una colección temporal independiente
        y la elimina al finalizar.
    </p>

    <ul class="ejercicios-lista">
        <li>Dos modificaciones de las reglas de validación.</li>
        <li>Tres inserciones de documentos.</li>
        <li>Dos actualizaciones de documentos.</li>
        <li>Dos eliminaciones de documentos.</li>
        <li>Eliminación de la colección temporal.</li>
    </ul>

    <form
        id="formulario-ejercicios"
        method="POST"
        action="/etapa1/sql/ejecutar"
    >
        @csrf

        <button type="submit">Ejecutar ejercicios MongoDB</button>
    </form>

    <p id="mensaje" role="status" aria-live="polite">
        Presiona el botón para iniciar la prueba.
    </p>

    <section id="resultado" hidden>
        <h2>Resumen de ejecución</h2>
        <ul id="resumen"></ul>

        <h2>Documentos antes de las eliminaciones</h2>
        <pre id="antes"></pre>

        <h2>Documentos después de las eliminaciones</h2>
        <pre id="despues"></pre>
    </section>
@endsection

@push('scripts')
<script>
        const formulario = document.getElementById(
            'formulario-ejercicios'
        );

        formulario.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const boton = formulario.querySelector('button');
            const mensaje = document.getElementById('mensaje');
            const resultado = document.getElementById('resultado');
            const resumen = document.getElementById('resumen');

            boton.disabled = true;
            boton.textContent = 'Ejecutando...';

            mensaje.className = '';
            mensaje.textContent = 'Ejecutando ejercicios MongoDB...';
            resultado.hidden = true;
            resumen.replaceChildren();
            formulario.setAttribute('aria-busy', 'true');

            try {
                const respuesta = await fetch(formulario.action, {
                    method: 'POST',
                    body: new FormData(formulario),
                    headers: {
                        Accept: 'application/json'
                    }
                });

                const tipo = respuesta.headers.get('content-type') ?? '';

                if (!tipo.includes('application/json')) {
                    throw new Error(
                        'El servidor no devolvió una respuesta JSON. ' +
                        'Recarga la página e inténtalo nuevamente.'
                    );
                }

                const datos = await respuesta.json();

                if (!respuesta.ok) {
                    const errores = Object.values(
                        datos.errors ?? {}
                    ).flat();

                    throw new Error(
                        errores.join(' ') ||
                        datos.message ||
                        'No se pudieron ejecutar los ejercicios.'
                    );
                }

                const lineas = [
                    `Motor: ${datos.motor}`,
                    `Colección temporal: ${datos.coleccion_temporal}`,
                    `Modificaciones de validación: ${
                        datos.cambios_validacion_ejecutados
                    }`,
                    `Documentos insertados por operación: ${
                        datos.insert_documentos.join(', ')
                    }`,
                    `Documentos modificados por operación: ${
                        datos.update_documentos.join(', ')
                    }`,
                    `Documentos eliminados por operación: ${
                        datos.delete_documentos.join(', ')
                    }`,
                    `Colección temporal eliminada: ${
                        datos.coleccion_temporal_eliminada ? 'Sí' : 'No'
                    }`
                ];

                for (const texto of lineas) {
                    const elemento = document.createElement('li');
                    elemento.textContent = texto;
                    resumen.appendChild(elemento);
                }

                document.getElementById('antes').textContent =
                    JSON.stringify(datos.antes_de_eliminar, null, 2);

                document.getElementById('despues').textContent =
                    JSON.stringify(datos.despues_de_eliminar, null, 2);

                resultado.hidden = false;
                mensaje.className = 'exito';
                mensaje.textContent =
                    'Los ejercicios finalizaron correctamente.';
            } catch (error) {
                resultado.hidden = true;
                mensaje.className = 'error';
                mensaje.textContent = error.message;
            } finally {
                boton.disabled = false;
                boton.textContent = 'Ejecutar ejercicios MongoDB';
                formulario.removeAttribute('aria-busy');
            }
        });
    </script>
@endpush
