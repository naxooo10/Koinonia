@extends('layouts.koinonia')

@section('titulo', 'Editar reunión')
@section('icono', 'calendar')
@section('tipo', 'formulario-ui')
@section('descripcion', 'Actualiza el encuentro y la asistencia de sus integrantes originales.')

@section('encabezado')
Editar reunión #{{ $reunion->id_reunion }}
@endsection

@section('contenido')
<a href="/reuniones">← Volver a mis reuniones</a>

    

    <h2>{{ $casa->nombre_casa }}</h2>

    <p class="ayuda">
        Puedes corregir la fecha, el tema, los estados de asistencia y las
        justificaciones. Se conservan los integrantes registrados
        originalmente en esta reunión.
    </p>

    @if ($errors->any())
        <ul class="error" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/reuniones/{{ $reunion->id_reunion }}">
        @csrf
        @method('PATCH')

        @if ($asistencia->isEmpty())
            <input type="hidden" name="asistencia" value="">
        @endif

        <div class="campo">
            <label for="fecha">Fecha</label>

            <input
                id="fecha"
                name="fecha"
                type="date"
                value="{{ old('fecha', $reunion->fecha_formulario) }}"
                required
            >
        </div>

        <div class="campo">
            <label for="tema">Tema</label>

            <input
                id="tema"
                name="tema"
                type="text"
                maxlength="150"
                value="{{ old('tema', $reunion->tema) }}"
                required
            >
        </div>

        <h2>Asistencia registrada</h2>

        @forelse ($asistencia as $indice => $registro)
            <fieldset>
                <legend>{{ $registro->nombre }}</legend>

                <input
                    type="hidden"
                    name="asistencia[{{ $indice }}][usuario_id]"
                    value="{{ $registro->usuario_id }}"
                >

                <div class="campo">
                    <label for="estado-{{ $indice }}">Estado</label>

                    <select
                        id="estado-{{ $indice }}"
                        name="asistencia[{{ $indice }}][estado]"
                        required
                    >
                        @foreach ([
                            'pendiente' => 'Pendiente',
                            'presente' => 'Presente',
                            'ausente' => 'Ausente'
                        ] as $valor => $texto)
                            <option
                                value="{{ $valor }}"
                                @selected(
                                    old(
                                        "asistencia.$indice.estado",
                                        $registro->estado
                                    ) === $valor
                                )
                            >
                                {{ $texto }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="justificacion-{{ $indice }}">
                        Justificación u observación
                    </label>

                    <textarea
                        id="justificacion-{{ $indice }}"
                        name="asistencia[{{ $indice }}][justificacion]"
                        maxlength="1000"
                    >{{ old("asistencia.$indice.justificacion", $registro->justificacion) }}</textarea>
                </div>
            </fieldset>
        @empty
            <p>Esta reunión no tiene integrantes registrados.</p>
        @endforelse

        <button type="submit">Guardar cambios</button>
    </form>
@endsection
