@extends('layouts.koinonia')

@section('titulo', 'Gestionar evento')
@section('icono', 'calendar')
@section('tipo', 'formulario-ui')
@section('descripcion', 'Dale forma a un nuevo encuentro para tu comunidad.')

@section('encabezado')
{{ $evento ? 'Editar evento' : 'Crear evento' }}
@endsection

@section('contenido')
<a href="/eventos">← Volver a eventos</a>

    

    @if ($errors->any())
        <ul class="error" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form
        method="POST"
        action="{{ $evento ? '/eventos/'.$evento->id_evento : '/eventos' }}"
    >
        @csrf

        @if ($evento)
            @method('PATCH')
        @endif

        <div class="campo">
            <label for="titulo">Título</label>

            <input
                id="titulo"
                name="titulo"
                type="text"
                maxlength="150"
                value="{{ old('titulo', $evento->titulo ?? '') }}"
                required
            >
        </div>

        <div class="campo">
            <label for="descripcion">Descripción</label>

            <textarea
                id="descripcion"
                name="descripcion"
                maxlength="3000"
                required
            >{{ old('descripcion', $evento->descripcion ?? '') }}</textarea>
        </div>

        <div class="campo">
            <label for="fecha_hora">Fecha y hora de Santiago de Chile</label>

            <input
                id="fecha_hora"
                name="fecha_hora"
                type="datetime-local"
                value="{{ old('fecha_hora', $evento->fecha_formulario ?? '') }}"
                required
            >
        </div>

        <div class="campo">
            <label for="id_casa">Casa Iglesia</label>

            <select
                id="id_casa"
                name="id_casa"
                @required(! $esAdministrador)
            >
                @if ($esAdministrador)
                    <option value="">Evento general para todos</option>
                @else
                    <option value="">Selecciona una Casa</option>
                @endif

                @foreach ($casas as $casa)
                    <option
                        value="{{ $casa->id_casa }}"
                        @selected(
                            (string) old('id_casa', $evento->id_casa ?? '')
                            === (string) $casa->id_casa
                        )
                    >
                        {{ $casa->nombre_casa }}
                        {{ $casa->estado ? '' : '— Inactiva' }}
                    </option>
                @endforeach
            </select>
        </div>

        @if (! $esAdministrador && $casas->isEmpty())
            <p>No tienes Casas activas a tu cargo para crear eventos.</p>
        @else
            <button type="submit">
                {{ $evento ? 'Guardar cambios' : 'Crear evento' }}
            </button>
        @endif
    </form>
@endsection
