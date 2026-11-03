@extends('layouts.koinonia')

@section('titulo', 'Gestionar material')
@section('icono', 'folder')
@section('tipo', 'formulario-ui')
@section('descripcion', 'Comparte recursos que acompañen el crecimiento de la comunidad.')

@section('encabezado')
{{ $material ? 'Editar material' : 'Subir material' }}
@endsection

@section('contenido')
<a href="/materiales">← Volver a materiales</a>

    

    @if ($errors->any())
        <ul class="error" role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ $material ? '/materiales/'.$material->id_material : '/materiales' }}"
    >
        @csrf

        @if ($material)
            @method('PATCH')
        @endif

        <div class="campo">
            <label for="titulo">Título</label>

            <input
                id="titulo"
                name="titulo"
                type="text"
                maxlength="150"
                value="{{ old('titulo', $material->titulo ?? '') }}"
                required
            >
        </div>

        <div class="campo">
            <label for="categoria">Categoría</label>

            <input
                id="categoria"
                name="categoria"
                type="text"
                maxlength="100"
                placeholder="Por ejemplo: Formación"
                value="{{ old('categoria', $material->categoria ?? '') }}"
                required
            >
        </div>

        <div class="campo">
            <label for="archivo">
                {{ $material ? 'Reemplazar archivo' : 'Archivo' }}
            </label>

            <input
                id="archivo"
                name="archivo"
                type="file"
                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt"
                aria-describedby="ayuda-archivo"
                @required(! $material)
            >

            <p class="ayuda" id="ayuda-archivo">
                Formatos: PDF, Word, PowerPoint, Excel o TXT.
                Tamaño máximo permitido por la aplicación: 10 MB.
            </p>

            @if ($material)
                <p class="ayuda">
                    Si no seleccionas un archivo, se conservará el actual.
                </p>
            @endif
        </div>

        <button type="submit">
            {{ $material ? 'Guardar cambios' : 'Subir material' }}
        </button>
    </form>
@endsection
