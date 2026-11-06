<?php
declare(strict_types=1);

/**
 * Router para el servidor embebido de PHP:
 *   php -S localhost:8000 router.php
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Nunca exponer código interno, migraciones, scripts de consola ni archivos ocultos.
if (preg_match('#^/(app|bin|database)(/|$)#', $path) || preg_match('#(^|/)\.#', $path)) {
    http_response_code(403);
    echo 'Acceso denegado';
    return true;
}

// Servir archivos estáticos existentes (assets).
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
