@extends('layouts.koinonia')

@section('titulo', 'Informe de reuniones')
@section('icono', 'chart')
@section('tipo', 'listado-ui')
@section('descripcion', 'Una mirada a los encuentros y la asistencia de nuestras Casas.')

@section('encabezado')
Informe de reuniones
@endsection

@section('contenido')
<nav aria-label="Navegación principal">
            <a href="/panel">Panel principal</a>
            <a href="/panel/casas">Casas</a>
            <a href="/panel/usuarios">Usuarios</a>
        </nav>

        

        <p>
            Selecciona el mes y el año.
            Los demás filtros son opcionales.
        </p>

        <form id="filtros">
            <div>
                <label for="mes">Mes *</label>
                <select id="mes" name="mes" required>
                    @foreach ([
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                        4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                        10 => 'Octubre', 11 => 'Noviembre',
                        12 => 'Diciembre'
                    ] as $numero => $nombre)
                        <option
                            value="{{ $numero }}"
                            @selected(
                                $numero === (int) now()->format('n')
                            )
                        >
                            {{ $nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="anio">Año *</label>
                <input
                    id="anio"
                    name="anio"
                    type="number"
                    min="2000"
                    max="2100"
                    value="{{ now()->year }}"
                    required
                >
            </div>

            <div>
                <label for="id_lider">Líder</label>
                <select id="id_lider" name="id_lider" disabled>
                    <option value="">Todos</option>
                </select>
            </div>

            <div>
                <label for="id_casa">Casa Iglesia</label>
                <select id="id_casa" name="id_casa" disabled>
                    <option value="">Todas</option>
                </select>
            </div>

            <div>
                <label for="id_participante">Integrante</label>
                <select
                    id="id_participante"
                    name="id_participante"
                    aria-describedby="ayuda-integrante"
                    disabled
                >
                    <option value="">Todos</option>
                </select>
                <p id="ayuda-integrante" class="ayuda">
                    Busca a la persona registrada, sin importar
                    su rol o estado de asistencia.
                </p>
            </div>

            <div>
                <label for="cantidad_asistentes">
                    Cantidad de presentes
                </label>
                <input
                    id="cantidad_asistentes"
                    name="cantidad_asistentes"
                    type="number"
                    min="0"
                    placeholder="Cualquier cantidad"
                    aria-describedby="ayuda-cantidad"
                >
                <p id="ayuda-cantidad" class="ayuda">
                    Busca una cantidad exacta. No incluye
                    ausentes ni pendientes.
                </p>
            </div>

            <div class="acciones">
                <button type="submit">Consultar reuniones</button>
            </div>
        </form>

        <p id="estado-filtros" class="ayuda" role="status">
            Cargando opciones de filtros...
        </p>

        <p id="mensaje" role="status" aria-live="polite">
            Selecciona los filtros y presiona «Consultar reuniones».
        </p>

        <section
            id="resultados"
            aria-label="Reuniones encontradas"
            aria-busy="false"
        ></section>
@endsection

@push('scripts')
<script>
        const formulario = document.getElementById('filtros');
        const mensaje = document.getElementById('mensaje');
        const resultados = document.getElementById('resultados');

        function crearElemento(etiqueta, texto, clase) {
            const elemento = document.createElement(etiqueta);

            if (texto !== undefined && texto !== null) {
                elemento.textContent = texto;
            }

            if (clase) {
                elemento.className = clase;
            }

            return elemento;
        }

        function agregarOpcion(selector, valor, texto) {
            const opcion = crearElemento('option', texto);
            opcion.value = valor;
            selector.appendChild(opcion);
        }

        async function obtenerJson(url) {
            const respuesta = await fetch(url, {
                headers: { Accept: 'application/json' }
            });

            const tipo = respuesta.headers.get('content-type') ?? '';

            if (!tipo.includes('application/json')) {
                throw new Error(
                    'El servidor no devolvió una respuesta válida. ' +
                    'Recarga la página e inténtalo nuevamente.'
                );
            }

            const datos = await respuesta.json();

            if (!respuesta.ok) {
                const errores = Object.values(datos.errors ?? {}).flat();

                throw new Error(
                    errores.join(' ') ||
                    datos.message ||
                    'No se pudo completar la consulta.'
                );
            }

            if (!Array.isArray(datos)) {
                throw new Error(
                    'La respuesta recibida tiene un formato inesperado.'
                );
            }

            return datos;
        }

        async function cargarFiltros() {
            const estado = document.getElementById('estado-filtros');

            const selectorCasas = document.getElementById('id_casa');
            const selectorLideres = document.getElementById('id_lider');
            const selectorPersonas = document.getElementById(
                'id_participante'
            );

            try {
                const [casas, usuarios] = await Promise.all([
                    obtenerJson('/casas'),
                    obtenerJson('/usuarios')
                ]);

                for (const casa of casas) {
                    agregarOpcion(
                        selectorCasas,
                        casa.id_casa,
                        casa.nombre_casa +
                            (casa.estado ? '' : ' (Inactiva)')
                    );
                }

                const responsables = new Set(
                    casas
                        .map(casa => casa.id_lider_responsable)
                        .filter(id => id !== null && id !== undefined)
                        .map(String)
                );

                for (const usuario of usuarios) {
                    const nombre = usuario.nombre +
                        (usuario.estado ? '' : ' (Inactivo)');

                    agregarOpcion(
                        selectorPersonas,
                        usuario.id_usuario,
                        nombre
                    );

                    if (
                        usuario.nombre_rol === 'Líder' ||
                        responsables.has(String(usuario.id_usuario))
                    ) {
                        agregarOpcion(
                            selectorLideres,
                            usuario.id_usuario,
                            nombre
                        );
                    }
                }

                selectorCasas.disabled = false;
                selectorLideres.disabled = false;
                selectorPersonas.disabled = false;

                estado.textContent = '';
            } catch (error) {
                estado.className = 'error';
                estado.textContent =
                    'No se pudieron cargar los filtros adicionales. ' +
                    error.message +
                    ' Puedes consultar por período y cantidad de presentes.';
            }
        }

        function agregarDato(lista, etiqueta, valor) {
            const grupo = crearElemento('div');
            grupo.append(
                crearElemento('dt', etiqueta),
                crearElemento('dd', valor)
            );
            lista.appendChild(grupo);
        }

        function mostrarReunion(reunion) {
            const tarjeta = crearElemento('article', null, 'tarjeta');

            tarjeta.appendChild(
                crearElemento(
                    'h2',
                    `Reunión #${reunion.id_reunion}: ${reunion.tema}`
                )
            );

            const datos = crearElemento('dl', null, 'datos');

            const partesFecha = String(reunion.fecha).split('-');
            const fecha = partesFecha.length === 3
                ? partesFecha.reverse().join('/')
                : reunion.fecha;

            agregarDato(datos, 'Fecha', fecha);
            agregarDato(datos, 'Casa Iglesia', reunion.nombre_casa);
            agregarDato(
                datos,
                'Líder actual',
                reunion.nombre_lider ?? 'Sin líder asignado'
            );
            agregarDato(datos, 'Presentes', reunion.cantidad_asistentes);
            agregarDato(datos, 'Ausentes', reunion.cantidad_ausentes);
            agregarDato(datos, 'Pendientes', reunion.cantidad_pendientes);

            tarjeta.appendChild(datos);

            const detalle = crearElemento('details');

            detalle.appendChild(
                crearElemento(
                    'summary',
                    'Ver asistencia y justificaciones'
                )
            );

            const asistencia = reunion.asistencia ?? [];

            if (asistencia.length === 0) {
                detalle.appendChild(
                    crearElemento(
                        'p',
                        'Esta reunión no tiene registros de asistencia.'
                    )
                );
            } else {
                const lista = crearElemento('ul', null, 'asistencia');

                const estados = {
                    presente: 'Presente',
                    ausente: 'Ausente',
                    pendiente: 'Pendiente'
                };

                for (const registro of asistencia) {
                    const persona = crearElemento('li', null, 'persona');

                    persona.appendChild(
                        crearElemento('strong', registro.nombre)
                    );

                    const lineaEstado = crearElemento('div');
                    const estadoValido = Object.prototype.hasOwnProperty.call(
                        estados,
                        registro.estado
                    );

                    lineaEstado.appendChild(
                        crearElemento(
                            'span',
                            estadoValido
                                ? estados[registro.estado]
                                : 'Estado no disponible',
                            estadoValido
                                ? `estado ${registro.estado}`
                                : 'estado'
                        )
                    );

                    persona.appendChild(lineaEstado);

                    persona.appendChild(
                        crearElemento(
                            'p',
                            registro.justificacion
                                ? `Justificación: ${registro.justificacion}`
                                : 'Sin justificación registrada.'
                        )
                    );

                    lista.appendChild(persona);
                }

                detalle.appendChild(lista);
            }

            tarjeta.appendChild(detalle);
            resultados.appendChild(tarjeta);
        }

        formulario.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const boton = formulario.querySelector(
                'button[type="submit"]'
            );

            const parametros = new URLSearchParams();

            for (const [clave, valor] of new FormData(formulario)) {
                if (String(valor).trim() !== '') {
                    parametros.append(clave, valor);
                }
            }

            mensaje.className = '';
            mensaje.textContent = 'Consultando reuniones...';
            resultados.replaceChildren();
            resultados.setAttribute('aria-busy', 'true');

            boton.disabled = true;
            boton.textContent = 'Consultando...';

            try {
                const reuniones = await obtenerJson(
                    `/informes/reuniones?${parametros.toString()}`
                );

                for (const reunion of reuniones) {
                    mostrarReunion(reunion);
                }

                mensaje.textContent = reuniones.length
                    ? `${reuniones.length} reunión(es) encontrada(s).`
                    : 'No se encontraron reuniones. Prueba otro período ' +
                      'o modifica los filtros.';
            } catch (error) {
                resultados.replaceChildren();
                mensaje.className = 'error';
                mensaje.textContent = error.message;
            } finally {
                resultados.setAttribute('aria-busy', 'false');
                boton.disabled = false;
                boton.textContent = 'Consultar reuniones';
            }
        });

        cargarFiltros();
    </script>
@endpush
