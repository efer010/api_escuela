<?php
declare(strict_types=1);

/**
 * Gestión de administradores desde la consola.
 *
 * Uso:
 *   php bin/admin.php list
 *   php bin/admin.php create <usuario> [--nombre="Nombre"] [--correo=correo] [--password=...]
 *   php bin/admin.php password <usuario> [--password=...]
 *   php bin/admin.php disable <usuario>
 *   php bin/admin.php enable <usuario>
 *   php bin/admin.php tokens <usuario>
 *   php bin/admin.php revoke <id_token>
 *   php bin/admin.php revoke-all <usuario>
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    echo 'No encontrado';
    exit;
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Config;
use App\Core\Database;
use App\Services\Auth;

$command = strtolower((string) ($argv[1] ?? 'help'));
$args = array_slice($argv, 2);
$positional = [];
$options = [];

foreach ($args as $arg) {
    if (str_starts_with($arg, '--')) {
        $pair = explode('=', substr($arg, 2), 2);
        $options[$pair[0]] = $pair[1] ?? true;
    } else {
        $positional[] = $arg;
    }
}

$out = static function (string $text = ''): void {
    fwrite(STDOUT, $text . PHP_EOL);
};
$err = static function (string $text = ''): void {
    fwrite(STDERR, $text . PHP_EOL);
};

$usage = static function () use ($out): void {
    $out('Administradores · ' . Config::get('app.name', 'Sistema Escolar'));
    $out('');
    $out('Comandos:');
    $out('  list                          Lista las cuentas de administrador.');
    $out('  create <usuario>              Crea una cuenta. Si omites --password se genera una.');
    $out('  password <usuario>            Cambia la contraseña y revoca todos sus tokens.');
    $out('  disable <usuario>             Desactiva la cuenta y revoca sus tokens.');
    $out('  enable <usuario>              Reactiva la cuenta.');
    $out('  tokens <usuario>              Lista los tokens por dispositivo.');
    $out('  revoke <id_token>             Revoca un token concreto.');
    $out('  revoke-all <usuario>          Revoca todos los tokens de una cuenta.');
    $out('');
    $out('Opciones de create/password:');
    $out('  --password=...                Contraseña (mínimo ' . Config::get('auth.password_min', 8) . ' caracteres).');
    $out('  --nombre="Nombre completo"    Nombre visible (solo create).');
    $out('  --correo=correo@dominio       Correo único (solo create).');
    $out('');
    $out('Ejemplos:');
    $out('  C:\\xampp\\php\\php.exe bin\\admin.php create admin --nombre="Administrador"');
    $out('  C:\\xampp\\php\\php.exe bin\\admin.php list');
};

$findAdmin = static function (string $login): ?array {
    $login = trim($login);

    return Database::one(
        'SELECT * FROM `admins` WHERE `usuario` = ? OR `correo` = ? LIMIT 1',
        [$login, $login]
    );
};

$generatePassword = static function (): string {
    return bin2hex(random_bytes(9));
};

$validatePassword = static function (string $password) use ($err): bool {
    $min = max(6, (int) Config::get('auth.password_min', 8));

    if (mb_strlen($password) < $min) {
        $err('La contraseña debe tener al menos ' . $min . ' caracteres.');

        return false;
    }

    return true;
};

try {
    switch ($command) {
        case 'help':
        case '--help':
        case '-h':
            $usage();
            break;

        case 'list':
            $rows = Database::all(
                "SELECT a.`id`, a.`usuario`, a.`nombre`, a.`correo`, a.`activo`, a.`ultimo_acceso`,
                        (SELECT COUNT(*) FROM `admin_tokens` t
                         WHERE t.`admin_id` = a.`id`
                           AND t.`revocado_en` IS NULL
                           AND t.`expira_en` > NOW()) AS `tokens_activos`
                 FROM `admins` a
                 ORDER BY a.`usuario`"
            );

            if ($rows === []) {
                $out('No hay administradores registrados. Crea uno con:');
                $out('  php bin/admin.php create admin');
                break;
            }

            $out(sprintf(
                '%-4s %-20s %-28s %-30s %-8s %-19s %s',
                'ID', 'USUARIO', 'NOMBRE', 'CORREO', 'ACTIVO', 'ÚLTIMO ACCESO', 'TOKENS'
            ));
            $out(str_repeat('-', 120));

            foreach ($rows as $row) {
                $out(sprintf(
                    '%-4s %-20s %-28s %-30s %-8s %-19s %s',
                    (string) $row['id'],
                    mb_strimwidth((string) $row['usuario'], 0, 20, '…'),
                    mb_strimwidth((string) $row['nombre'], 0, 28, '…'),
                    mb_strimwidth((string) ($row['correo'] ?? '—'), 0, 30, '…'),
                    ((int) $row['activo'] === 1 ? 'sí' : 'no'),
                    (string) ($row['ultimo_acceso'] ?? '—'),
                    (string) $row['tokens_activos']
                ));
            }
            break;

        case 'create':
            $usuario = trim((string) ($positional[0] ?? ''));
            if ($usuario === '') {
                $err('Indica el usuario: php bin/admin.php create <usuario>');
                exit(1);
            }

            if (preg_match('/^[A-Za-z0-9._-]{3,60}$/', $usuario) !== 1) {
                $err('El usuario debe tener entre 3 y 60 caracteres: letras, números, punto, guion o guion bajo.');
                exit(1);
            }

            $nombre = trim((string) ($options['nombre'] ?? $usuario));
            if ($nombre === '') {
                $nombre = $usuario;
            }

            $correo = trim((string) ($options['correo'] ?? ''));
            if ($correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
                $err('El correo indicado no es válido.');
                exit(1);
            }

            $existing = $findAdmin($usuario);
            if ($existing !== null) {
                $err('Ya existe un administrador con ese usuario o correo.');
                exit(1);
            }

            if ($correo !== '') {
                $existingMail = Database::one('SELECT `id` FROM `admins` WHERE `correo` = ? LIMIT 1', [$correo]);
                if ($existingMail !== null) {
                    $err('Ya existe un administrador con ese correo.');
                    exit(1);
                }
            }

            $generated = !isset($options['password']);
            $password = $generated ? $generatePassword() : (string) $options['password'];

            if (!$validatePassword($password)) {
                exit(1);
            }

            $id = Database::insert('admins', [
                'usuario'       => $usuario,
                'nombre'        => mb_substr($nombre, 0, 120),
                'correo'        => $correo === '' ? null : $correo,
                'password_hash' => Auth::hashPassword($password),
                'activo'        => 1,
            ]);

            $out('Administrador creado correctamente.');
            $out('  ID:      ' . $id);
            $out('  Usuario: ' . $usuario);
            $out('  Nombre:  ' . $nombre);
            if ($correo !== '') {
                $out('  Correo:  ' . $correo);
            }

            if ($generated) {
                $out('');
                $out('  Contraseña generada (guárdala ahora, no se volverá a mostrar):');
                $out('    ' . $password);
                $out('');
                $out('  Cambia la contraseña después del primer acceso desde Mi cuenta.');
            }
            break;

        case 'password':
            $login = trim((string) ($positional[0] ?? ''));
            $admin = $login === '' ? null : $findAdmin($login);

            if ($admin === null) {
                $err('No se encontró el administrador indicado.');
                exit(1);
            }

            $generated = !isset($options['password']);
            $password = $generated ? $generatePassword() : (string) $options['password'];

            if (!$validatePassword($password)) {
                exit(1);
            }

            Auth::updatePassword((int) $admin['id'], $password);
            $revoked = Auth::revokeAllForAdmin((int) $admin['id']);

            $out('Contraseña actualizada para "' . $admin['usuario'] . '".');
            $out('Tokens revocados: ' . $revoked);

            if ($generated) {
                $out('');
                $out('Nueva contraseña generada (guárdala ahora, no se volverá a mostrar):');
                $out('  ' . $password);
            }
            break;

        case 'disable':
        case 'enable':
            $login = trim((string) ($positional[0] ?? ''));
            $admin = $login === '' ? null : $findAdmin($login);

            if ($admin === null) {
                $err('No se encontró el administrador indicado.');
                exit(1);
            }

            $active = $command === 'enable';
            Auth::setActive((int) $admin['id'], $active);

            if (!$active) {
                $revoked = Auth::revokeAllForAdmin((int) $admin['id']);
                $out('Cuenta desactivada. Tokens revocados: ' . $revoked);
            } else {
                $out('Cuenta reactivada. El administrador ya puede iniciar sesión.');
            }
            break;

        case 'tokens':
            $login = trim((string) ($positional[0] ?? ''));
            $admin = $login === '' ? null : $findAdmin($login);

            if ($admin === null) {
                $err('No se encontró el administrador indicado.');
                exit(1);
            }

            $tokens = Auth::tokens((int) $admin['id']);

            if ($tokens === []) {
                $out('El administrador "' . $admin['usuario'] . '" no tiene tokens registrados.');
                break;
            }

            $out('Tokens de "' . $admin['usuario'] . '":');
            $out(sprintf(
                '%-6s %-30s %-16s %-8s %-19s %-19s %s',
                'ID', 'DISPOSITIVO', 'IP', 'ESTADO', 'CREADO', 'ÚLTIMO USO', 'EXPIRA'
            ));
            $out(str_repeat('-', 130));

            foreach ($tokens as $token) {
                $out(sprintf(
                    '%-6s %-30s %-16s %-8s %-19s %-19s %s',
                    (string) $token['id'],
                    mb_strimwidth((string) $token['dispositivo'], 0, 30, '…'),
                    mb_strimwidth((string) ($token['ip'] ?? '—'), 0, 16, '…'),
                    (string) $token['estado'],
                    (string) ($token['creado_en'] ?? '—'),
                    (string) ($token['ultimo_uso'] ?? '—'),
                    (string) ($token['expira_en'] ?? '—')
                ));
            }
            break;

        case 'revoke':
            $id = (int) ($positional[0] ?? 0);
            if ($id <= 0) {
                $err('Indica el ID del token: php bin/admin.php revoke <id_token>');
                exit(1);
            }

            $statement = Database::execute(
                'UPDATE `admin_tokens` SET `revocado_en` = NOW() WHERE `id` = ? AND `revocado_en` IS NULL',
                [$id]
            );

            if ($statement->rowCount() === 0) {
                $err('El token no existe o ya estaba revocado.');
                exit(1);
            }

            $out('Token ' . $id . ' revocado correctamente.');
            break;

        case 'revoke-all':
            $login = trim((string) ($positional[0] ?? ''));
            $admin = $login === '' ? null : $findAdmin($login);

            if ($admin === null) {
                $err('No se encontró el administrador indicado.');
                exit(1);
            }

            $revoked = Auth::revokeAllForAdmin((int) $admin['id']);
            $out('Tokens revocados para "' . $admin['usuario'] . '": ' . $revoked);
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

    if (Config::get('app.debug')) {
        $err('Archivo: ' . $e->getFile() . ':' . $e->getLine());
    }

    exit(1);
}
