<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') | Koinonía</title>
    <link rel="stylesheet" href="{{ asset('css/koinonia-panel.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('css/koinonia-interior.css') }}?v=1">
</head>
<body class="ui-error-body">
    <main class="ui-error-layout">
        <section class="ui-error-card" aria-labelledby="titulo-error">
            <a href="/panel"><img class="login-logo" src="{{ asset('images/koinonia/logo-koinonia.png') }}" alt="Koinonía" width="2076" height="758"></a>
            <span class="ui-error-codigo">@yield('codigo')</span>
            <h1 id="titulo-error">@yield('titulo')</h1>
            <p>@yield('mensaje')</p>
            <div class="ui-error-acciones"><a href="/panel">Ir al panel</a><a href="/login">Iniciar sesión</a></div>
        </section>
        <p class="ui-error-pie">Koinonía · Personas, Casas y propósito.</p>
    </main>
</body>
</html>
