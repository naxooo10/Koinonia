<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class NotificacionesWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);
        $this->registrarConfirmacion($request, $respuesta);

        return $this->decorar($request, $respuesta);
    }

    public function decorar(
        Request $request,
        Response $respuesta,
        ?Throwable $error = null
    ): Response {
        if (
            $respuesta instanceof RedirectResponse
            || $respuesta instanceof StreamedResponse
            || $respuesta instanceof BinaryFileResponse
            || ! str_contains(strtolower($respuesta->headers->get('Content-Type', '')), 'text/html')
        ) {
            return $respuesta;
        }

        $contenido = $respuesta->getContent();
        if (
            ! is_string($contenido)
            || stripos($contenido, '</head>') === false
            || str_contains($contenido, 'id="koinonia-toast-data"')
        ) {
            return $respuesta;
        }

        $avisos = [];
        if ($request->hasSession()) {
            foreach (['exito' => 'exito', 'success' => 'exito', 'error' => 'error'] as $clave => $tipo) {
                $mensaje = $request->session()->get($clave);
                if (is_string($mensaje) && trim($mensaje) !== '') {
                    $avisos[] = ['tipo' => $tipo, 'mensaje' => Str::limit($mensaje, 650)];
                }
            }

            $errores = $this->erroresDeSesion($request);
            if ($errores !== []) {
                $avisos[] = [
                    'tipo' => 'error',
                    'mensaje' => Str::limit(implode(' ', array_slice($errores, 0, 3)), 650),
                ];
            }
        }

        if ($respuesta->getStatusCode() >= 400) {
            $estado = $respuesta->getStatusCode();
            $mensaje = match ($estado) {
                401 => 'Tu sesión no está disponible. Inicia sesión nuevamente.',
                403 => 'No tienes permiso para realizar esta acción.',
                404 => 'El recurso solicitado no está disponible.',
                405 => 'Esta acción no está disponible para la dirección solicitada.',
                413 => 'El archivo o la solicitud supera el tamaño permitido.',
                419 => 'Tu sesión venció. Recarga la página antes de volver a intentarlo.',
                422 => 'Revisa los datos del formulario e inténtalo nuevamente.',
                429 => 'Has realizado demasiados intentos. Espera un momento.',
                default => 'No se pudo completar la acción. Inténtalo nuevamente.',
            };

            if (
                $estado < 500
                && $estado !== 419
                && $error instanceof HttpExceptionInterface
                && trim($error->getMessage()) !== ''
            ) {
                $mensaje = Str::limit($error->getMessage(), 650);
            }

            $avisos[] = ['tipo' => 'error', 'mensaje' => $mensaje];
        }

        $unicos = [];
        foreach ($avisos as $aviso) {
            $unicos[$aviso['tipo'] . ':' . $aviso['mensaje']] = $aviso;
        }

        $json = json_encode(
            array_values($unicos),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
        );
        $css = htmlspecialchars(asset('css/koinonia-toast.css') . '?v=1', ENT_QUOTES, 'UTF-8');
        $js = htmlspecialchars(asset('js/koinonia-toast.js') . '?v=1', ENT_QUOTES, 'UTF-8');
        $fragmento = '<script type="application/json" id="koinonia-toast-data">' . $json . '</script>'
            . '<link rel="stylesheet" href="' . $css . '">'
            . '<script src="' . $js . '"></script>';

        $posicion = strripos($contenido, '</head>');
        $respuesta->setContent(substr_replace($contenido, $fragmento, $posicion, 0));
        $respuesta->headers->remove('Content-Length');
        $respuesta->headers->remove('ETag');

        return $respuesta;
    }

    private function registrarConfirmacion(Request $request, Response $respuesta): void
    {
        if (
            ! $respuesta instanceof RedirectResponse
            || ! in_array($respuesta->getStatusCode(), [302, 303], true)
            || ! $request->hasSession()
            || $request->user() === null
            || $this->erroresDeSesion($request) !== []
            || $request->session()->has('error')
            || $request->session()->has('exito')
            || $request->session()->has('success')
        ) {
            return;
        }

        $destino = parse_url($respuesta->getTargetUrl(), PHP_URL_PATH);
        if ($destino === '/login') {
            return;
        }

        if (! preg_match('#\A(casas|usuarios|eventos|materiales|reuniones)(?:/(\d+))?(?:/(activar|desactivar))?\z#', $request->path(), $partes)) {
            return;
        }

        $recurso = $partes[1];
        $id = $partes[2] ?? '';
        $accion = $partes[3] ?? '';
        $metodo = $request->method();
        $nombre = match ($recurso) {
            'casas' => 'La Casa',
            'usuarios' => 'El usuario',
            'eventos' => 'El evento',
            'materiales' => 'El material',
            default => 'La reunión',
        };

        $mensaje = match (true) {
            $metodo === 'PATCH' && $accion === 'activar' => $nombre . ' se activó correctamente.',
            $metodo === 'PATCH' && $accion === 'desactivar' => $nombre . ' se desactivó correctamente.',
            $metodo === 'POST' && $id === '' => $nombre . ' se creó correctamente.',
            $metodo === 'PATCH' && $id !== '' => $nombre . ' se actualizó correctamente.',
            $metodo === 'DELETE' && $id !== '' => $nombre . ' se eliminó correctamente.',
            default => null,
        };

        if ($mensaje !== null) {
            $request->session()->flash('exito', $mensaje);
        }
    }

    private function erroresDeSesion(Request $request): array
    {
        if (! $request->hasSession()) {
            return [];
        }

        $errores = $request->session()->get('errors');
        if ($errores instanceof ViewErrorBag) {
            return $errores->getBag('default')->all();
        }
        if ($errores instanceof MessageBag) {
            return $errores->all();
        }

        return [];
    }
}
