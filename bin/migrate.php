<?php
declare(strict_types=1);

/**
 * Ejecutor de migraciones y datos de ejemplo.
 *
 * Uso:
 *   php bin/migrate.php migrate [--seed]
 *   php bin/migrate.php status
 *   php bin/migrate.php rollback [--batch=N]
 *   php bin/migrate.php seed
 *   php bin/migrate.php fresh --force [--seed]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    echo 'No encontrado';
    exit;
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$command = strtolower($argv[1] ?? 'migrate');
$args    = array_slice($argv, 2);

$hasFlag = static fn (string $name): bool => in_array($name, $args, true);
$out     = static function (string $text = ''): void {
    fwrite(STDOUT, $text . PHP_EOL);
};
$err     = static function (string $text = ''): void {
    fwrite(STDERR, $text . PHP_EOL);
};

$usage = static function () use ($out): void {
    $out('Sistema de migraciones · ' . App\Core\Config::get('app.name', 'Sistema Escolar'));
    $out('');
    $out('Comandos disponibles:');
    $out('  migrate [--seed]        Aplica las migraciones pendientes.');
    $out('  status                  Muestra el estado de cada migración.');
    $out('  rollback [--batch=N]    Revierte el último lote (o el lote indicado).');
    $out('  seed                    Ejecuta los archivos de database/seeders.');
    $out('  fresh --force [--seed]  Elimina todo y reconstruye la base desde cero.');
    $out('');
    $out('Ejemplos:');
    $out('  C:\\xampp\\php\\php.exe bin\\migrate.php migrate');
    $out('  C:\\xampp\\php\\php.exe bin\\migrate.php status');
    $out('  C:\\xampp\\php\\php.exe bin\\migrate.php migrate --seed');
};

if (in_array($command, ['help', '--help', '-h'], true)) {
    $usage();
    exit(0);
}

$migrator = new App\Core\Migrator();

try {
    switch ($command) {
        case 'migrate':
            $executed = $migrator->migrate();

            if ($executed === []) {
                $out('No hay migraciones pendientes.');
            } else {
                $out('Migraciones aplicadas (' . count($executed) . '):');
                foreach ($executed as $name) {
                    $out('  [OK] ' . $name);
                }
                $out('Lote actual: ' . $migrator->lastBatch());
            }

            if ($hasFlag('--seed')) {
                $seeded = $migrator->seed();
                $out('');
                $out('Datos de ejemplo ejecutados (' . count($seeded) . '):');
                foreach ($seeded as $name) {
                    $out('  [OK] ' . $name);
                }
            }
            break;

        case 'status':
            $rows = $migrator->status();

            if ($rows === []) {
                $out('No se encontraron archivos de migración en ' . $migrator->path());
                break;
            }

            $out('Estado de las migraciones (' . $migrator->path() . '):');
            $out('');

            $pending = 0;
            foreach ($rows as $row) {
                $state = [
                    'aplicada'         => '[APLICADA] ',
                    'pendiente'        => '[PENDIENTE]',
                    'archivo faltante' => '[FALTANTE] ',
                ][$row['estado']] ?? '[?]';

                if ($row['estado'] === 'pendiente') {
                    $pending++;
                }

                $detail = '';
                if ($row['lote'] !== null) {
                    $detail = '  lote ' . $row['lote'] . ' · ' . $row['aplicada_en'];
                }

                $out('  ' . $state . ' ' . $row['migracion'] . $detail);
            }

            $out('');
            $out('Total: ' . count($rows) . ' · Pendientes: ' . $pending
                . ' · Último lote: ' . $migrator->lastBatch());
            break;

        case 'rollback':
            $batch = null;
            foreach ($args as $arg) {
                if (preg_match('/^--batch=(\d+)$/', $arg, $matches) === 1) {
                    $batch = (int) $matches[1];
                }
            }

            $reverted = $migrator->rollback($batch);

            if ($reverted === []) {
                $out('No hay migraciones para revertir.');
                break;
            }

            $out('Migraciones revertidas (' . count($reverted) . '):');
            foreach ($reverted as $name) {
                $out('  [OK] ' . $name);
            }
            break;

        case 'seed':
            $seeded = $migrator->seed();

            if ($seeded === []) {
                $out('No se encontraron archivos de datos en ' . $migrator->seedersPath());
                break;
            }

            $out('Datos de ejemplo ejecutados (' . count($seeded) . '):');
            foreach ($seeded as $name) {
                $out('  [OK] ' . $name);
            }
            break;

        case 'fresh':
            if (!$hasFlag('--force')) {
                $err('ATENCIÓN: "fresh" elimina TODAS las tablas y vistas de la base de datos.');
                $err('Confirma la operación con --force:');
                $err('  php bin/migrate.php fresh --force [--seed]');
                exit(1);
            }

            $executed = $migrator->fresh($hasFlag('--seed'));

            $out('Base de datos reconstruida (' . count($executed) . ' migraciones):');
            foreach ($executed as $name) {
                $out('  [OK] ' . $name);
            }

            if ($hasFlag('--seed')) {
                $out('  [OK] datos de ejemplo');
            }
            break;

        default:
            $err('Comando desconocido: ' . $command);
            $err('');
            $usage();
            exit(1);
    }
} catch (Throwable $e) {
    $err('');
    $err('ERROR: ' . $e->getMessage());

    if (App\Core\Config::get('app.debug')) {
        $err('Archivo: ' . $e->getFile() . ':' . $e->getLine());
    }

    exit(1);
}
