<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Session;

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);

/*
 * Autoloader PSR-4 simple: App\Core\Database -> app/Core/Database.php
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = APP_PATH . '/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

Config::load(require APP_PATH . '/config.php');

date_default_timezone_set((string) Config::get('app.timezone', 'America/Mexico_City'));

error_reporting(E_ALL);
ini_set('display_errors', Config::get('app.debug') ? '1' : '0');

if (PHP_SAPI !== 'cli') {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
    }
}

require APP_PATH . '/helpers.php';

if (PHP_SAPI !== 'cli') {
    Session::start();
}
