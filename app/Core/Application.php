<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Application
{
    public function run(): void
    {
        $path = Request::path();
        $isApi = str_starts_with($path, '/api');

        try {
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
            404 => 'Página no encontrada',
            405 => 'Método no permitido',
            419 => 'Sesión expirada',
            default => 'Algo salió mal',
        };

        $trace = $debug ? $exception->getTraceAsString() : '';

        require APP_PATH . '/Views/errors/error.php';
        exit;
    }
}
