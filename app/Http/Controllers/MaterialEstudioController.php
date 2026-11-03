<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Operation\FindOneAndUpdate;
use Throwable;

class MaterialEstudioController extends Controller
{
    public function index(Request $request)
    {
        $nombreRol = $this->obtenerRol($request);

        $datos = $request->validate([
            'categoria' => ['nullable', 'string', 'max:100'],
        ]);

        $categoriaSeleccionada = $datos['categoria'] ?? '';

        $categorias = DB::connection('mongodb')
            ->table('materiales_estudio')
            ->select('categoria')
            ->get()
            ->pluck('categoria')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $consulta = DB::connection('mongodb')
            ->table('materiales_estudio');

        if ($categoriaSeleccionada !== '') {
            $consulta->where('categoria', $categoriaSeleccionada);
        }

        $materiales = $consulta
            ->orderBy('fecha_subida', 'desc')
            ->orderBy('_id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $materiales->getCollection()->transform(function ($material) {
            $ruta = (string) ($material->url_archivo ?? '');

            return (object) [
                'id_material' => (int) $material->id,
                'titulo' => $material->titulo,
                'categoria' => $material->categoria,
                'fecha_subida' => $this->formatearFecha(
                    $material->fecha_subida ?? null
                ),
                'archivo_disponible' => $this->rutaValida($ruta)
                    && Storage::disk('local')->exists($ruta),
            ];
        });

        $esAdministrador = $nombreRol === 'Administrador';

        return view('materiales.index', compact(
            'materiales',
            'categorias',
            'categoriaSeleccionada',
            'esAdministrador'
        ));
    }

    public function create(Request $request)
    {
        $this->exigirAdministrador($request);

        $material = null;

        return view('materiales.formulario', compact('material'));
    }

    public function store(Request $request)
    {
        $this->exigirAdministrador($request);

        $datos = $request->validate($this->reglas(true));

        $ruta = $request->file('archivo')
            ->store('materiales_estudio', 'local');

        abort_if(
            $ruta === false,
            500,
            'No se pudo guardar el archivo.'
        );

        try {
            DB::connection('mongodb')
                ->table('materiales_estudio')
                ->insert([
                    '_id' => $this->siguienteIdMaterial(),
                    'titulo' => $datos['titulo'],
                    'categoria' => $datos['categoria'],
                    'url_archivo' => $ruta,
                    'fecha_subida' => new UTCDateTime(
                        new DateTimeImmutable(
                            'now',
                            new DateTimeZone('UTC')
                        )
                    ),
                    'id_admin' => (int) $request->user()
                        ->getAuthIdentifier(),
                ]);
        } catch (Throwable $error) {
            Storage::disk('local')->delete($ruta);

            throw $error;
        }

        return redirect('/materiales')
            ->with('exito', 'El material se subió correctamente.');
    }

    public function edit(Request $request, int $id)
    {
        $this->exigirAdministrador($request);

        $material = $this->obtenerMaterial($id);
        $material->id_material = (int) $material->id;

        return view('materiales.formulario', compact('material'));
    }

    public function update(Request $request, int $id)
    {
        $this->exigirAdministrador($request);


        $material = $this->obtenerMaterial($id);
        $datos = $request->validate($this->reglas(false));

        $cambios = [
            'titulo' => $datos['titulo'],
            'categoria' => $datos['categoria'],
        ];

        $rutaNueva = null;

        if ($request->hasFile('archivo')) {
            $rutaNueva = $request->file('archivo')
                ->store('materiales_estudio', 'local');

            abort_if(
                $rutaNueva === false,
                500,
                'No se pudo guardar el archivo.'
            );

            $cambios['url_archivo'] = $rutaNueva;
        }

        try {
            DB::connection('mongodb')
                ->table('materiales_estudio')
                ->where('_id', $id)
                ->update($cambios);
        } catch (Throwable $error) {
            if ($rutaNueva !== null) {
                Storage::disk('local')->delete($rutaNueva);
            }

            throw $error;
        }

        if ($rutaNueva !== null) {
            $rutaAnterior = (string) ($material->url_archivo ?? '');

            if ($this->rutaValida($rutaAnterior)) {
                Storage::disk('local')->delete($rutaAnterior);
            }
        }

        return redirect('/materiales')
            ->with('exito', 'El material se actualizó correctamente.');
    }

    public function download(Request $request, int $id)
    {
        $this->obtenerRol($request);

        $material = $this->obtenerMaterial($id);
        $ruta = (string) ($material->url_archivo ?? '');

        abort_unless(
            $this->rutaValida($ruta)
                && Storage::disk('local')->exists($ruta),
            404,
            'El archivo no está disponible. Solicita al administrador que lo vuelva a subir.'
        );

        $extension = pathinfo($ruta, PATHINFO_EXTENSION);
        $nombre = Str::slug($material->titulo) ?: 'material';

        return Storage::disk('local')->download(
            $ruta,
            $nombre . '.' . $extension,
            ['X-Content-Type-Options' => 'nosniff']
        );
    }

    public function destroy(Request $request, int $id)
    {
        $this->exigirAdministrador($request);

        $material = $this->obtenerMaterial($id);

        DB::connection('mongodb')
            ->table('materiales_estudio')
            ->where('_id', $id)
            ->delete();

        $ruta = (string) ($material->url_archivo ?? '');

        if ($this->rutaValida($ruta)) {
            Storage::disk('local')->delete($ruta);
        }

        return redirect('/materiales')
            ->with('exito', 'El material se eliminó correctamente.');
    }

    private function obtenerRol(Request $request): string
    {
        $usuario = $request->user();

        abort_if($usuario === null, 401, 'Debes iniciar sesión.');
        abort_unless($usuario->estado, 403, 'Tu cuenta está inactiva.');

        $rol = DB::connection('mongodb')
            ->table('roles')
            ->where('_id', (int) $usuario->id_rol)
            ->first();

        $nombreRol = $rol->nombre_rol ?? '';

        abort_unless(
            in_array($nombreRol, ['Administrador', 'Líder'], true),
            403,
            'No tienes permiso para acceder a los materiales de estudio.'
        );

        return $nombreRol;
    }

    private function exigirAdministrador(Request $request): void
    {
        abort_unless(
            $this->obtenerRol($request) === 'Administrador',
            403,
            'Solo el administrador puede gestionar materiales de estudio.'
        );
    }

    private function obtenerMaterial(int $id): object
    {
        $material = DB::connection('mongodb')
            ->table('materiales_estudio')
            ->where('_id', $id)
            ->first();

        abort_if($material === null, 404, 'El material no existe.');

        return $material;
    }

    private function reglas(bool $archivoObligatorio): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'categoria' => ['required', 'string', 'max:100'],
            'archivo' => [
                $archivoObligatorio ? 'required' : 'nullable',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt',
                'max:10240',
            ],
        ];
    }

    private function rutaValida(string $ruta): bool
    {
        return preg_match(
            '#\Amateriales_estudio/[A-Za-z0-9_-]+\.[A-Za-z0-9]+\z#',
            $ruta
        ) === 1;
    }

    private function formatearFecha(
        DateTimeInterface|UTCDateTime|null $fecha
    ): string {
        if ($fecha === null) {
            return 'Sin fecha';
        }

        $valor = $fecha instanceof UTCDateTime
            ? $fecha->toDateTime()
            : $fecha;

        return DateTimeImmutable::createFromInterface($valor)
            ->setTimezone(new DateTimeZone('America/Santiago'))
            ->format('d/m/Y H:i');
    }

    private function siguienteIdMaterial(): int
    {
        $maximoActual = (int) (
            DB::connection('mongodb')
                ->table('materiales_estudio')
                ->max('_id') ?? 0
        );

        return DB::connection('mongodb')
            ->table('contadores')
            ->raw(function (Collection $coleccion) use ($maximoActual) {
                $coleccion->updateOne(
                    ['_id' => 'materiales_estudio'],
                    ['$max' => ['secuencia' => $maximoActual]],
                    ['upsert' => true]
                );

                $contador = $coleccion->findOneAndUpdate(
                    ['_id' => 'materiales_estudio'],
                    ['$inc' => ['secuencia' => 1]],
                    [
                        'returnDocument' =>
                            FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                    ]
                );

                return (int) $contador['secuencia'];
            });
    }
}