<?php
declare(strict_types=1);

/**
 * Configuración general del sistema.
 * Puedes sobrescribir los valores con variables de entorno.
 */
return [
    'app' => [
        'name'         => getenv('APP_NAME') ?: 'Sistema Escolar',
        'timezone'     => getenv('APP_TIMEZONE') ?: 'America/Mexico_City',
        'debug'        => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOL),
        /*
         * Si tu servidor no tiene mod_rewrite (o no permite .htaccess),
         * cambia este valor a false y las URLs usarán index.php?r=...
         */
        'pretty_urls'  => filter_var(getenv('APP_PRETTY_URLS') ?: 'true', FILTER_VALIDATE_BOOL),
        'per_page'     => 10,
        'api_per_page' => 25,
    ],
    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('DB_PORT') ?: '3306',
        'name'    => getenv('DB_NAME') ?: 'escuela',
        'user'    => getenv('DB_USER') ?: 'root',
        'pass'    => getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
        'charset' => 'utf8mb4',
    ],
];
