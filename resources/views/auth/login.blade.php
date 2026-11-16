<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Koinonía</title>
    <link rel="stylesheet" href="{{ asset('css/koinonia-panel.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('css/koinonia-interior.css') }}?v=2">
    <script src="{{ asset('js/koinonia-interior.js') }}?v=1" defer></script>
</head>
<body class="login-pagina">
    @include('components.iconos')
    <main class="login-layout">
        <section class="login-visual" aria-label="Nuestra comunidad"><div><span class="sobre-titulo">UNA FAMILIA DE FAMILIAS</span><h2>Más personas.<br>Más comunidad.<br>Más de Él.</h2><p>“Porque donde dos o tres se reúnen en mi nombre, allí estoy yo con ellos.”</p><cite>Mateo 18:20</cite></div></section>
        <section class="login-form-card" aria-label="Acceso a Koinonía">
            <img class="login-logo" src="{{ asset('images/koinonia/logo-koinonia.png') }}" alt="Koinonía" width="2076" height="758">
<h1>Iniciar sesión</h1>
        <p>Ingresa a Koinonía con tu cuenta.</p>

        @if ($errors->any())
            <div id="errores-login" class="errores" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="/login">
            @csrf

            <div class="campo">
                <label for="email">Correo</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="username"
                    value="{{ old('email') }}"
                    @if ($errors->has('email'))
                        aria-invalid="true"
                        aria-describedby="errores-login"
                    @endif
                    required
                >
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <div class="login-clave">
<input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    @if ($errors->has('password'))
                        aria-invalid="true"
                        aria-describedby="errores-login"
                    @endif
                    required
                >
<button type="button" data-toggle-password="password" aria-controls="password" aria-pressed="false">Mostrar</button>
</div>
            </div>

            <button type="submit">Iniciar sesión</button>
        </form>
            <p class="login-alterno">¿No tienes cuenta? <a href="/registro">Crea una</a></p>
            <div class="login-nota"><svg class="icono" aria-hidden="true"><use href="#i-users"/></svg><span>Personas, Casas y propósito.</span></div>
        </section>
    </main>
</body>
</html>
