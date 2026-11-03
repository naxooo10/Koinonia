@extends('layouts.koinonia')

@section('titulo', 'Nueva Casa Iglesia')
@section('icono', 'home')
@section('tipo', 'formulario-ui')
@section('descripcion', 'Un nuevo lugar para encontrarnos y crecer en comunidad.')

@section('encabezado')
Nueva Casa Iglesia
@endsection

@section('contenido')
<p><a href="/panel/casas">← Volver a Casas Iglesia</a></p>

    

    @if ($errors->any())
        <ul class="errores">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/casas">
        @csrf

        <label for="nombre_casa">Nombre de la casa</label>
        <input
            id="nombre_casa"
            name="nombre_casa"
            value="{{ old('nombre_casa') }}"
            maxlength="100"
            required
        >

        <label for="direccion">Dirección</label>
        <input
            id="direccion"
            name="direccion"
            value="{{ old('direccion') }}"
            maxlength="200"
            required
        >

        <button type="submit">Guardar casa</button>
    </form>
@endsection
