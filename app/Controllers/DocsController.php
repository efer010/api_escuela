<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

final class DocsController
{
    /**
     * Interfaz de Swagger UI.
     */
    public function ui(): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        require APP_PATH . '/Views/swagger.php';
    }

    /**
     * Especificación OpenAPI en JSON.
     * Reemplaza el marcador __BASE_URL__ por la ruta base real de la app.
     */
    public function spec(): void
    {
        $file = BASE_PATH . '/docs/openapi.json';

        if (!is_file($file)) {
            Response::json([
                'error'   => true,
                'status'  => 404,
                'message' => 'La especificación OpenAPI no está disponible.',
            ], 404);
        }

        $spec = (string) file_get_contents($file);
        $base = Request::basePath();
        $base = $base === '' ? '/' : rtrim($base, '/') . '/';

        $spec = str_replace('__BASE_URL__', $base, $spec);

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache');
        }

        echo $spec;
    }
}
