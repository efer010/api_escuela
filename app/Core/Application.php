<?php
declare(strict_types=1);

namespace App\Core;

use App\Services\Auth;
use Throwable;

final class Application
{
    /**
     * Rutas accesibles sin autenticación.
     *
     * @var list<string>
     */
    private const PUBLIC_PATHS = [
        '/login',
        '/api/login',
        '/api/docs',
        '/api/openapi.json',
    ];

    public function run(): void
    {
        $path = Request::path();
        $isApi = str_starts_with($path, '/api');

        try {
            if (!$this->isPublicPath($path) && !Auth::check()) {
                if ($isApi) {
                    if (!headers_sent()) {
                        header('WWW-Authenticate: Bearer');
                    }
                    Response::json([
                        'error'   => true,
                        'status'  => 401,
                        'message' => 'Se requiere un token Bearer válido. Usa POST /api/login para obtener uno.',
                    ], 401);
                }

                Response::redirect(url_with('/login', ['next' => $this->nextPath($path)]));
            }

            $router = new Router();
            $registerRoutes = require BASE_PATH . '/app/routes.php';
            $registerRoutes($router);

            $router->dispatch(Request::method(), $path);
        } catch (HttpException $exception) {
            $this->handleException($exception, $exception->statusCode(), $isApi);
        } catch (Throwable $exception) {
            $this->handleException($exception, 500, $isApi);
        }
    }

    private function isPublicPath(string $path): bool
    {
        return in_array($path, self::PUBLIC_PATHS, true);
    }

    /**
     * Ruta interna a la que volver después de iniciar sesión.
     */
    private function nextPath(string $path): string
    {
        $query = $_GET;
        unset($query['r']);

        if ($query === []) {
            return $path;
        }

        return $path . '?' . http_build_query($query);
    }

    private function handleException(Throwable $exception, int $status, bool $isApi): void
    {
        $debug = (bool) Config::get('app.debug');
        $message = $exception->getMessage();

        if ($status === 500 && !$debug) {
            $message = 'Ocurrió un error inesperado en el servidor.';
        }

        if ($isApi) {
            Response::json([
                'error'   => true,
                'status'  => $status,
                'message' => $message,
            ], $status);
        }

        http_response_code($status);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        $title = match ($status) {
            401 => 'Acceso no autorizado',
            404 => 'Página no encontrada',
            405 => 'Método no permitido',
            419 => 'Sesión expirada',
            429 => 'Demasiados intentos',
            default => 'Algo salió mal',
        };

        $trace = $debug ? $exception->getTraceAsString() : '';

        require APP_PATH . '/Views/errors/error.php';
        exit;
    }
}
