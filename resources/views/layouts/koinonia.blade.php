@php
    $cuentaActual = auth()->user();
    $rolDeCuenta = $cuentaActual === null ? null : \Illuminate\Support\Facades\DB::connection('mongodb')
        ->table('roles')->where('_id', (int) $cuentaActual->id_rol)->first();
    $rolDeNavegacion = $rolDeCuenta->nombre_rol ?? '';
    $nombreDeCuenta = $cuentaActual->nombre ?? 'Mi cuenta';
    $inicialesDeCuenta = \Illuminate\Support\Str::of($nombreDeCuenta)->trim()->explode(' ')
        ->filter()->take(2)->map(fn ($parte) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($parte, 0, 1)))->implode('');
    $esAdminDeCuenta = $rolDeNavegacion === 'Administrador';
    $esLiderDeCuenta = $rolDeNavegacion === 'Líder';
    $seccionDeRuta = match (true) {
        request()->is('panel/casas', 'casas', 'casas/*') => 'casas',
        request()->is('panel/usuarios', 'usuarios', 'usuarios/*') => 'usuarios',
        request()->is('panel/reuniones', 'informes/reuniones', 'reuniones', 'reuniones/*') => 'reuniones',
        request()->is('eventos', 'eventos/*') => 'eventos',
        request()->is('materiales', 'materiales/*') => 'materiales',
        request()->is('panel/consultas', 'etapa1/consultas/*') => 'consultas',
        request()->is('etapa1/sql', 'etapa1/sql/*') => 'ejercicios',
        default => 'panel',
    };
    $enlacesDeNavegacion = [
        ['icono' => 'home', 'titulo' => 'Inicio', 'href' => '/panel', 'seccion' => 'panel'],
    ];
    if ($esAdminDeCuenta) {
        $enlacesDeNavegacion = array_merge($enlacesDeNavegacion, [
            ['icono' => 'home', 'titulo' => 'Casas Iglesias', 'href' => '/panel/casas', 'seccion' => 'casas'],
            ['icono' => 'users', 'titulo' => 'Usuarios', 'href' => '/panel/usuarios', 'seccion' => 'usuarios'],
            ['icono' => 'chart', 'titulo' => 'Informes de reuniones', 'href' => '/panel/reuniones', 'seccion' => 'reuniones'],
            ['icono' => 'calendar', 'titulo' => 'Eventos', 'href' => '/eventos', 'seccion' => 'eventos'],
            ['icono' => 'folder', 'titulo' => 'Materiales', 'href' => '/materiales', 'seccion' => 'materiales'],
            ['icono' => 'database', 'titulo' => 'Consultas MongoDB', 'href' => '/panel/consultas', 'seccion' => 'consultas'],
            ['icono' => 'layers', 'titulo' => 'Ejercicios MongoDB', 'href' => '/etapa1/sql', 'seccion' => 'ejercicios'],
        ]);
    } elseif ($esLiderDeCuenta) {
        $enlacesDeNavegacion = array_merge($enlacesDeNavegacion, [
            ['icono' => 'users', 'titulo' => 'Mis Casas Iglesias', 'href' => '/panel#mis-casas', 'seccion' => 'casas'],
            ['icono' => 'calendar', 'titulo' => 'Mis reuniones', 'href' => '/reuniones', 'seccion' => 'reuniones'],
            ['icono' => 'calendar', 'titulo' => 'Eventos', 'href' => '/eventos', 'seccion' => 'eventos'],
            ['icono' => 'folder', 'titulo' => 'Materiales', 'href' => '/materiales', 'seccion' => 'materiales'],
        ]);
    } else {
        $enlacesDeNavegacion = array_merge($enlacesDeNavegacion, [
            ['icono' => 'users', 'titulo' => 'Mis Casas Iglesias', 'href' => '/panel#mis-casas', 'seccion' => 'casas'],
            ['icono' => 'calendar', 'titulo' => 'Eventos', 'href' => '/eventos', 'seccion' => 'eventos'],
        ]);
    }
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Koinonía') | Koinonía</title>
    <link rel="stylesheet" href="{{ asset('css/koinonia-panel.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('css/koinonia-interior.css') }}?v=1">
    <script src="{{ asset('js/koinonia-interior.js') }}?v=1" defer></script>
    @stack('estilos')
</head>
<body class="koinonia-exterior">
    @include('components.iconos')
    <a class="saltar" href="#contenido">Saltar al contenido</a>

    <aside class="lateral" id="barra-lateral" aria-label="Navegación principal">
        <div class="marca-fila">
            <button class="boton-icono menu-boton" data-ui-menu type="button" aria-label="Contraer menú" aria-expanded="true" aria-controls="barra-lateral"><svg class="icono"><use href="#i-menu"/></svg></button>
            <a class="marca" href="/panel" aria-label="Koinonía, inicio"><img class="marca-logo" src="{{ asset('images/koinonia/logo-koinonia.png') }}" alt="Koinonía" width="2076" height="758"></a>
        </div>
        <nav class="menu">
            @foreach ($enlacesDeNavegacion as $enlaceDeMenu)
                <a class="menu-enlace {{ $seccionDeRuta === $enlaceDeMenu['seccion'] ? 'seleccionado' : '' }}" href="{{ $enlaceDeMenu['href'] }}" title="{{ $enlaceDeMenu['titulo'] }}" @if ($seccionDeRuta === $enlaceDeMenu['seccion']) aria-current="page" @endif>
                    <svg class="icono"><use href="#i-{{ $enlaceDeMenu['icono'] }}"/></svg><span>{{ $enlaceDeMenu['titulo'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="lateral-inferior"><blockquote class="cita-lateral">“Porque donde dos o tres se reúnen en mi nombre, allí estoy yo con ellos.”<cite>Mateo 18:20</cite></blockquote><p class="pie-lateral">Más personas, más comunidad.</p></div>
    </aside>

    <div class="zona-principal">
        <header class="cabecera">
            <nav class="migas" aria-label="Ubicación"><a href="/panel">Panel principal</a><span aria-hidden="true">/</span><strong>@yield('titulo', 'Comunidad')</strong></nav>
            <details class="perfil">
                <summary><span class="avatar">{{ $inicialesDeCuenta }}</span><span class="perfil-nombre">{{ $nombreDeCuenta }}<small>{{ $rolDeNavegacion }}</small></span><svg class="icono desplegar"><use href="#i-chevron"/></svg></summary>
                <div class="perfil-menu"><p>Sesión iniciada como<strong>{{ $nombreDeCuenta }}</strong></p><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><svg class="icono"><use href="#i-logout"/></svg>Cerrar sesión</button></form></div>
            </details>
        </header>

        <main id="contenido" class="interior-main" tabindex="-1">
            <header class="encabezado-pagina"><span class="encabezado-icono"><svg class="icono"><use href="#i-@yield('icono', 'home')"/></svg></span><div><p class="sobre-titulo">TU COMUNIDAD, MÁS CERCA</p><h1>@yield('encabezado')</h1><p class="descripcion-pagina">@yield('descripcion')</p></div></header>
            <div class="pagina-contenido @yield('tipo', 'listado-ui')">
                @yield('contenido')
            </div>
            <footer class="pie-panel"><span>Koinonía · Personas, Casas y propósito.</span><span>{{ $rolDeNavegacion }}</span></footer>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
