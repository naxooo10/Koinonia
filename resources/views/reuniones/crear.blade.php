@extends('layouts.koinonia')

@section('titulo', 'Registrar reunión')
@section('icono', 'calendar')
@section('tipo', 'listado-ui')
@section('descripcion', 'Prepara un encuentro y registra la asistencia de tu Casa.')

@section('encabezado')
Registrar reunión y asistencia
@endsection

@section('contenido')
<p><a href="/panel">Volver al panel</a></p>

        

        @if ($errors->any())
            <div class="error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            class="selector-casa"
            method="GET"
            action="/reuniones/crear"
        >
            <label for="casa">Casa Iglesia *</label>

            <select id="casa" name="id_casa" required>
                <option value="">Selecciona una Casa</option>

                @foreach ($casas as $opcion)
                    <option
                        value="{{ $opcion->id_casa }}"
                        @selected(
                            ($casa->id_casa ?? null)
                            == $opcion->id_casa
                        )
                    >
                        {{ $opcion->nombre_casa }}
                    </option>
                @endforeach
            </select>

            <button type="submit">Cargar integrantes</button>
        </form>

        @if ($casa)
            <h2>{{ $casa->nombre_casa }}</h2>

            @if (! $lider)
                <p>Esta Casa necesita un líder responsable activo.</p>
                <a href="/casas/{{ $casa->id_casa }}/editar">
                    Asignar líder
                </a>
            @else
                <p>Líder responsable: {{ $lider->nombre }}</p>

                <form
                    id="formulario-reunion"
                    method="POST"
                    action="/reuniones"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="id_casa"
                        value="{{ $casa->id_casa }}"
                    >

                    <input
                        type="hidden"
                        name="id_lider"
                        value="{{ $lider->id_usuario }}"
                    >

                    <div class="campo">
                        <label for="fecha">Fecha *</label>
                        <input
                            id="fecha"
                            name="fecha"
                            type="date"
                            value="{{ old('fecha') }}"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="tema">Tema *</label>
                        <input
                            id="tema"
                            name="tema"
                            type="text"
                            value="{{ old('tema') }}"
                            maxlength="150"
                            required
                        >
                    </div>

                    <h3>Asistencia</h3>

                    <p class="ayuda">
                        Revisa el estado de cada integrante.
                        Las personas sin evaluar permanecen pendientes.
                        Solo los presentes se cuentan como asistentes.
                    </p>

                    @forelse ($participantes as $participante)
                        @php
                            $indice = $loop->index;

                            $estado = old(
                                "asistencia.$indice.estado",
                                'pendiente'
                            );
                        @endphp

                        <fieldset class="persona">
                            <legend>{{ $participante->nombre }}</legend>

                            <input
                                type="hidden"
                                name="asistencia[{{ $indice }}][usuario_id]"
                                value="{{ $participante->id_usuario }}"
                            >

                            <div class="campo">
                                <label for="estado-{{ $indice }}">
                                    Estado *
                                </label>

                                <select
                                    id="estado-{{ $indice }}"
                                    name="asistencia[{{ $indice }}][estado]"
                                    required
                                >
                                    <option
                                        value="pendiente"
                                        @selected($estado === 'pendiente')
                                    >
                                        Pendiente
                                    </option>

                                    <option
                                        value="presente"
                                        @selected($estado === 'presente')
                                    >
                                        Presente
                                    </option>

                                    <option
                                        value="ausente"
                                        @selected($estado === 'ausente')
                                    >
                                        Ausente
                                    </option>
                                </select>
                            </div>

                            <label for="justificacion-{{ $indice }}">
                                Justificación (opcional)
                            </label>

                            <textarea
                                id="justificacion-{{ $indice }}"
                                name="asistencia[{{ $indice }}][justificacion]"
                                maxlength="1000"
                            >{{ old("asistencia.$indice.justificacion") }}</textarea>
                        </fieldset>
                    @empty
                        <p>
                            Esta Casa no tiene integrantes activos.
                            La reunión se guardará sin registros de asistencia.
                        </p>
                    @endforelse

                    <p class="ayuda">* Campos obligatorios.</p>

                    <div class="acciones">
                        <button type="submit">
                            Guardar reunión y asistencia
                        </button>

                        <a href="/panel">Cancelar</a>
                    </div>
                </form>

                <div
                    id="resultado"
                    role="status"
                    aria-live="polite"
                    tabindex="-1"
                ></div>
            @endif
        @endif
@endsection

@push('scripts')
<script>
        const formulario = document.getElementById('formulario-reunion');

        if (formulario) {
            formulario.addEventListener('submit', async (evento) => {
                evento.preventDefault();

                const resultado = document.getElementById('resultado');
                const boton = formulario.querySelector(
                    'button[type="submit"]'
                );

                resultado.replaceChildren();
                resultado.className = '';
                resultado.textContent = 'Guardando reunión...';

                boton.disabled = true;
                boton.textContent = 'Guardando...';
                formulario.setAttribute('aria-busy', 'true');

                try {
                    const respuesta = await fetch(formulario.action, {
                        method: 'POST',
                        body: new FormData(formulario),
                        headers: {
                            Accept: 'application/json'
                        }
                    });

                    const tipoContenido = respuesta.headers.get(
                        'content-type'
                    ) ?? '';

                    if (!tipoContenido.includes('application/json')) {
                        throw new Error(
                            'El servidor no devolvió una respuesta válida. ' +
                            'Recarga la página antes de intentarlo nuevamente.'
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
                            'No se pudo guardar la reunión.'
                        );
                    }

                    resultado.className = 'exito';

                    const mensaje = document.createElement('p');
                    mensaje.textContent =
                        `Reunión #${datos.id_reunion} guardada.`;

                    const resumen = document.createElement('p');
                    resumen.textContent =
                        `Presentes: ${datos.asistentes_registrados}. ` +
                        `Ausentes: ${datos.ausentes}. ` +
                        `Pendientes: ${datos.pendientes}.`;

                    const enlace = document.createElement('a');
                    enlace.href = '/reuniones/crear';
                    enlace.textContent = 'Registrar otra reunión';

                    resultado.append(mensaje, resumen, enlace);
                    formulario.hidden = true;
                } catch (error) {
                    resultado.className = 'error';
                    resultado.textContent = error.message;
                } finally {
                    boton.disabled = false;
                    boton.textContent = 'Guardar reunión y asistencia';
                    formulario.removeAttribute('aria-busy');
                    resultado.focus();
                }
            });
        }
    </script>
@endpush
