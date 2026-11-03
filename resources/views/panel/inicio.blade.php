<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel | Koinonía</title>
    <link rel="stylesheet" href="{{ asset('css/koinonia-panel.css') }}?v=3">
</head>
<body>
    <svg class="iconos-definiciones" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <symbol id="i-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7M5 9v12h5v-7h4v7h5V9"/></symbol>
        <symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3H3ZM16 4a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v2"/></symbol>
        <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 11h18M8 15h2M14 15h2M8 18h2"/></symbol>
        <symbol id="i-chart" viewBox="0 0 24 24"><rect x="3" y="13" width="4" height="8" rx="1"/><rect x="10" y="8" width="4" height="13" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></symbol>
        <symbol id="i-folder" viewBox="0 0 24 24"><path d="M3 7a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7ZM3 11h18"/></symbol>
        <symbol id="i-database" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 4 16 4 16 0V5M4 12c0 4 16 4 16 0"/></symbol>
        <symbol id="i-layers" viewBox="0 0 24 24"><path d="m12 3 10 5-10 5L2 8l10-5ZM2 12l10 5 10-5M2 16l10 5 10-5"/></symbol>
        <symbol id="i-user-plus" viewBox="0 0 24 24"><circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3H3ZM19 7v6M16 10h6"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="17" rx="2"/><path d="M9 2h6v4H9zM8 13l3 3 5-6"/></symbol>
        <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></symbol>
        <symbol id="i-pin" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
        <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
        <symbol id="i-chevron" viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></symbol>
        <symbol id="i-search" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/></symbol>
        <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
        <symbol id="i-logout" viewBox="0 0 24 24"><path d="M10 4H4v16h6M9 12h12m-5-5 5 5-5 5"/></symbol>
        <symbol id="i-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 9a3 3 0 1 1 4 3c-1 .5-1 1-1 2M12 17h.01"/></symbol>
        <symbol id="i-spark" viewBox="0 0 24 24"><path d="m13 2-8 12h6l-1 8 9-13h-6l1-7Z"/></symbol>
    </svg>

    <a class="saltar" href="#contenido">Saltar al contenido</a>

    <aside class="lateral" id="barra-lateral" aria-label="Navegación principal">
        <div class="marca-fila">
            <button class="boton-icono menu-boton" id="alternar-menu" type="button" aria-label="Contraer menú" aria-expanded="true" aria-controls="barra-lateral">
                <svg class="icono"><use href="#i-menu"/></svg>
            </button>
            <a class="marca" href="/panel" aria-label="Koinonía, inicio">
                <img class="marca-logo" src="{{ asset('images/koinonia/logo-koinonia.png') }}" alt="Koinonía" width="2076" height="758">
            </a>
        </div>

        <nav class="menu">
            @foreach ($navegacion as $enlace)
                <a class="menu-enlace {{ $enlace['href'] === '/panel' ? 'seleccionado' : '' }}" href="{{ $enlace['href'] }}" title="{{ $enlace['titulo'] }}" @if ($enlace['href'] === '/panel') aria-current="page" @endif>
                    <svg class="icono"><use href="#i-{{ $enlace['icono'] }}"/></svg>
                    <span>{{ $enlace['titulo'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="lateral-inferior">
            <button class="menu-enlace" id="abrir-ayuda" type="button" title="Ayuda del panel">
                <svg class="icono"><use href="#i-help"/></svg><span>Ayuda del panel</span>
            </button>
            <blockquote class="cita-lateral">
                “Porque donde dos o tres se reúnen en mi nombre, allí estoy yo con ellos.”
                <cite>Mateo 18:20</cite>
            </blockquote>
            <p class="pie-lateral">Más personas, más comunidad.</p>
        </div>
    </aside>

    <div class="zona-principal">
        <header class="cabecera">
            <div class="buscador">
                <svg class="icono"><use href="#i-search"/></svg>
                <label class="solo-lectores" for="buscar-panel">Buscar accesos y Casas en este panel</label>
                <input id="buscar-panel" type="search" placeholder="Buscar accesos y Casas en este panel…" autocomplete="off" aria-describedby="estado-busqueda">
            </div>
            <details class="perfil">
                <summary>
                    <span class="avatar">{{ $iniciales }}</span>
                    <span class="perfil-nombre">{{ $usuario->nombre }}<small>{{ $nombreRol }}</small></span>
                    <svg class="icono desplegar"><use href="#i-chevron"/></svg>
                </summary>
                <div class="perfil-menu">
                    <p>Sesión iniciada como<strong>{{ $usuario->nombre }}</strong></p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"><svg class="icono"><use href="#i-logout"/></svg>Cerrar sesión</button>
                    </form>
                </div>
            </details>
        </header>

        <main id="contenido" tabindex="-1">
            <section class="bienvenida {{ $esAdministrador || $esLider ? '' : 'bienvenida-participante' }}">
                <div class="bienvenida-texto">
                    <span class="etiqueta-bienvenida">{{ $esAdministrador ? 'PERSONAS QUE CONSTRUYEN COMUNIDAD' : '¡HOLA, ' . mb_strtoupper($primerNombre) . '!' }}</span>
                    <h1>{{ $esAdministrador ? 'Panel administrativo' : ($esLider ? '¡Bienvenido a Koinonía!' : 'Mi comunidad') }}</h1>
                    <p>{{ $esAdministrador ? 'Juntos para ver más vidas transformadas.' : ($esLider ? 'Una familia de familias, creciendo juntos en la fe, el amor y el propósito de Dios.' : 'Formar parte también transforma. Gracias por ser parte de Koinonía.') }}</p>
                </div>
                <span class="insignia-rol"><svg class="icono"><use href="#i-users"/></svg>{{ $nombreRol }}</span>
            </section>

            <p class="estado-busqueda" id="estado-busqueda" role="status" aria-live="polite"></p>

            @if ($esAdministrador)
                <section class="estadisticas" aria-label="Resumen general">
                    @foreach ($estadisticas as $dato)
                        <article class="estadistica tarjeta">
                            <span class="icono-caja {{ $dato['color'] }}"><svg class="icono"><use href="#i-{{ $dato['icono'] }}"/></svg></span>
                            <div><strong class="valor">{{ $dato['valor'] }}</strong><h2>{{ $dato['titulo'] }}</h2><p>{{ $dato['nota'] }}</p></div>
                        </article>
                    @endforeach
                </section>

                <nav class="modulos" aria-label="Áreas de administración">
                    @foreach ($modulos as $modulo)
                        <a class="modulo tarjeta" href="{{ $modulo['href'] }}" data-busqueda="{{ $modulo['titulo'] . ' ' . $modulo['texto'] }}">
                            <span class="icono-caja {{ $modulo['color'] }}"><svg class="icono"><use href="#i-{{ $modulo['icono'] }}"/></svg></span>
                            <h2>{{ $modulo['titulo'] }}</h2><p>{{ $modulo['texto'] }}</p>
                            <svg class="icono flecha"><use href="#i-chevron"/></svg>
                        </a>
                    @endforeach
                </nav>

                <div class="panel-inferior">
                    <section class="tarjeta seccion">
                        <div class="titulo-seccion"><span class="icono-titulo"><svg class="icono"><use href="#i-spark"/></svg></span><div><h2>Gestión rápida</h2><p>Acciones frecuentes para tu administración.</p></div></div>
                        <div class="acciones-rapidas">
                            @foreach ($acciones as $accion)
                                <a class="accion" href="{{ $accion['href'] }}" data-busqueda="{{ $accion['titulo'] . ' ' . $accion['texto'] }}">
                                    <span class="icono-caja {{ $accion['color'] }}"><svg class="icono"><use href="#i-{{ $accion['icono'] }}"/></svg></span>
                                    <span><strong>{{ $accion['titulo'] }}</strong><small>{{ $accion['texto'] }}</small></span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                    <section class="tarjeta seccion">
                        <div class="titulo-seccion"><span class="icono-titulo"><svg class="icono"><use href="#i-clock"/></svg></span><div><h2>Reuniones recientes</h2><p>Últimas fechas de reunión registradas.</p></div><a class="enlace-pequeno" href="/panel/reuniones">Ver informes <span aria-hidden="true">→</span></a></div>
                        @if (count($recientes) === 0)
                            <div class="vacio"><svg class="icono"><use href="#i-calendar"/></svg><h3>Aún no hay reuniones</h3><p>Los registros de los líderes aparecerán aquí.</p></div>
                        @else
                            <div class="recientes">
                                @foreach ($recientes as $reunion)
                                    <article class="reciente"><span class="icono-caja azul"><svg class="icono"><use href="#i-calendar"/></svg></span><div><h3>{{ $reunion['titulo'] }}</h3><p>{{ $reunion['casa'] }}</p></div><time>{{ $reunion['fecha'] }}</time></article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            @else
                @if ($esLider)
                    <nav class="acciones-lider" aria-label="Acciones del líder">
                        @foreach ($acciones as $accion)
                            <a class="accion tarjeta" href="{{ $accion['href'] }}" data-busqueda="{{ $accion['titulo'] . ' ' . $accion['texto'] }}">
                                <span class="icono-caja {{ $accion['color'] }}"><svg class="icono"><use href="#i-{{ $accion['icono'] }}"/></svg></span>
                                <span><strong>{{ $accion['titulo'] }}</strong><small>{{ $accion['texto'] }}</small></span>
                            </a>
                        @endforeach
                    </nav>
                @endif

                <div class="comunidad-grid">
                    <section class="tarjeta seccion casas-seccion" id="mis-casas">
                        <div class="titulo-seccion"><span class="icono-titulo"><svg class="icono"><use href="#i-users"/></svg></span><div><h2>Mis Casas Iglesias</h2><p>{{ $esLider ? 'Comunidades activas bajo tu responsabilidad.' : 'Tus lugares para crecer, servir y compartir.' }}</p></div><span class="contador">{{ count($casasPanel) }}</span></div>
                        @if (count($casasPanel) === 0)
                            <div class="vacio"><svg class="icono"><use href="#i-home"/></svg><h3>{{ $esLider ? 'Sin Casas activas a tu cargo' : 'Todavía no tienes una Casa asignada' }}</h3><p>Consulta con la administración para revisar tu asignación.</p></div>
                        @else
                            <div class="lista-casas">
                                @foreach ($casasPanel as $casa)
                                    <article class="casa" data-busqueda="{{ $casa['nombre'] . ' ' . $casa['direccion'] . ' ' . $casa['lider'] }}">
                                        <div class="casa-cabecera"><span class="casa-ilustracion"><svg class="icono"><use href="#i-home"/></svg></span><div><span class="pill {{ $casa['activa'] ? 'activa' : 'inactiva' }}">{{ $casa['activa'] ? 'Comunidad activa' : 'Casa inactiva' }}</span><h3>{{ $casa['nombre'] }}</h3><p class="direccion"><svg class="icono"><use href="#i-pin"/></svg>{{ $casa['direccion'] }}</p></div></div>
                                        <div class="casa-datos"><div><span class="rotulo">Líder responsable</span><strong>{{ $casa['lider'] }}</strong>@if ($casa['lider_inactivo'])<small>Cuenta inactiva</small>@endif</div><div><span class="rotulo">Próxima reunión</span><strong>{{ ! $casa['activa'] ? 'Casa inactiva' : ($casa['proxima_fecha'] ?? 'Por programar') }}</strong>@if ($casa['proximo_tema'])<small>{{ $casa['proximo_tema'] }}</small>@endif</div></div>
                                        @if ($esLider)
                                            <a class="enlace-casa" href="/reuniones/crear?id_casa={{ $casa['id'] }}">Registrar reunión en esta Casa <svg class="icono"><use href="#i-arrow"/></svg></a>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="tarjeta seccion proxima-seccion">
                        <div class="titulo-seccion"><span class="icono-titulo violeta-texto"><svg class="icono"><use href="#i-calendar"/></svg></span><h2>Próxima reunión</h2></div>
                        @if ($proximaReunion === null)
                            <div class="vacio"><svg class="icono"><use href="#i-calendar"/></svg><h3>Un nuevo encuentro por venir</h3><p>Cuando haya una reunión programada para tus Casas activas, la verás aquí.</p></div>
                        @else
                            <div class="proxima-detalle"><div class="fecha-grande"><span>{{ $proximaReunion['mes'] }}</span><strong>{{ $proximaReunion['dia'] }}</strong></div><div><h3>{{ $proximaReunion['tema'] }}</h3><p><svg class="icono"><use href="#i-calendar"/></svg>{{ $proximaReunion['fecha'] }}</p><p><svg class="icono"><use href="#i-home"/></svg>{{ $proximaReunion['casa'] }}</p><p class="texto-suave">{{ $proximaReunion['direccion'] }}</p></div></div>
                        @endif
                        <div class="mensaje-comunidad"><svg class="icono"><use href="#i-users"/></svg><p><strong>Nos vemos pronto.</strong>Sigamos creciendo juntos en la Palabra.</p></div>
                    </section>

                    <section class="tarjeta seccion agenda-seccion" id="agenda">
                        <div class="titulo-seccion"><span class="icono-titulo"><svg class="icono"><use href="#i-calendar"/></svg></span><div><h2>Próximas actividades</h2><p>Reuniones y eventos de tu comunidad.</p></div><a class="enlace-pequeno" href="/eventos">Ver eventos <span aria-hidden="true">→</span></a></div>
                        @if (count($agenda) === 0)
                            <div class="vacio"><svg class="icono"><use href="#i-clock"/></svg><h3>Una agenda por construir</h3><p>No hay próximas actividades disponibles para mostrar.</p><a href="/eventos">Consultar eventos</a></div>
                        @else
                            <div class="agenda-lista">
                                @foreach ($agenda as $actividad)
                                    <a class="agenda-fila" href="{{ $actividad['href'] }}" data-busqueda="{{ $actividad['titulo'] . ' ' . $actividad['lugar'] }}"><span class="fecha-mini {{ $actividad['tipo'] === 'evento' ? 'violeta' : 'azul' }}"><small>{{ $actividad['mes'] }}</small><strong>{{ $actividad['dia'] }}</strong></span><span class="actividad-texto"><strong>{{ $actividad['titulo'] }}</strong><small>{{ $actividad['detalle'] }} · {{ $actividad['fecha'] }}</small></span><span class="actividad-lugar"><svg class="icono"><use href="#i-pin"/></svg>{{ $actividad['lugar'] }}</span><svg class="icono ir-actividad"><use href="#i-chevron"/></svg></a>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <aside class="cita-tarjeta tarjeta"><div><span class="sobre-titulo">JUNTOS EN EL MISMO PROPÓSITO</span><blockquote>“En comunidad descubrimos que la fe se vive mejor juntos.”</blockquote><span class="linea-cita"></span><p>Un lugar para crecer, servir y compartir.</p></div></aside>
                </div>
            @endif

            <footer class="pie-panel"><span>Koinonía <span aria-hidden="true">·</span> Personas, Casas y propósito.</span><span>{{ $nombreRol }}</span></footer>
        </main>
    </div>

    <dialog id="ayuda-panel" class="dialogo">
        <h2>Tu espacio en Koinonía</h2>
        <p>Usa el menú lateral para recorrer las funciones disponibles para tu rol.</p>
        <p>El buscador filtra los accesos, las Casas y las actividades que aparecen en este panel. Para buscar registros de otras pantallas, entra a la sección correspondiente.</p>
        <p>{{ $esAdministrador ? 'Puedes gestionar Casas, usuarios, eventos, materiales y consultar informes.' : ($esLider ? 'Puedes registrar reuniones de tus Casas activas, gestionar tus eventos y consultar materiales.' : 'Puedes consultar tus Casas de pertenencia, sus próximas reuniones y los eventos disponibles para ti.') }}</p>
        <form method="dialog"><button class="boton-primario">Entendido</button></form>
    </dialog>

    <script>
        const alternar = document.getElementById('alternar-menu');
        const buscar = document.getElementById('buscar-panel');
        const estadoBusqueda = document.getElementById('estado-busqueda');
        const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

        alternar.addEventListener('click', () => {
            const compacto = document.body.classList.toggle('menu-compacto');
            alternar.setAttribute('aria-expanded', String(!compacto));
            alternar.setAttribute('aria-label', compacto ? 'Expandir menú' : 'Contraer menú');
        });

        buscar.addEventListener('input', () => {
            const texto = normalizar(buscar.value.trim());
            let visibles = 0;
            for (const elemento of document.querySelectorAll('[data-busqueda]')) {
                const coincide = normalizar(elemento.dataset.busqueda).includes(texto);
                elemento.hidden = !coincide;
                if (coincide) visibles++;
            }
            estadoBusqueda.textContent = texto ? (visibles ? `${visibles} coincidencia(s) en este panel.` : 'No hay coincidencias en este panel. Prueba otro nombre.') : '';
        });

        const ayuda = document.getElementById('ayuda-panel');
        document.getElementById('abrir-ayuda').addEventListener('click', () => ayuda.showModal());

        document.addEventListener('keydown', evento => {
            if ((evento.ctrlKey || evento.metaKey) && evento.key.toLowerCase() === 'k') {
                evento.preventDefault();
                buscar.focus();
            }
        });

        document.addEventListener('click', evento => {
            const perfil = document.querySelector('.perfil');
            if (!perfil.contains(evento.target)) perfil.open = false;
        });
    </script>
</body>
</html>
