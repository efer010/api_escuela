<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use DateTimeImmutable;

/**
 * Autenticación de administradores mediante tokens por dispositivo.
 *
 * - La web usa una cookie HttpOnly con el token.
 * - La API usa la cabecera `Authorization: Bearer <token>`.
 * - En la base de datos solo se guarda el hash SHA-256 del token.
 */
final class Auth
{
    private const TOKEN_BYTES = 32;

    /** Hash de relleno para igualar el tiempo de respuesta cuando el usuario no existe. */
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    private static bool $resolved = false;

    private static ?array $admin = null;

    private static ?array $token = null;

    private static ?string $source = null;

    /**
     * Administrador autenticado en la petición actual (o null).
     */
    public static function user(): ?array
    {
        self::resolve();

        return self::$admin;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::$admin === null ? null : (int) self::$admin['id'];
    }

    public static function tokenId(): ?int
    {
        return self::$token === null ? null : (int) self::$token['id'];
    }

    public static function tokenInfo(): ?array
    {
        self::resolve();

        return self::$token;
    }

    public static function source(): ?string
    {
        self::resolve();

        return self::$source;
    }

    /**
     * Resuelve el token de la petición y carga el administrador asociado.
     */
    public static function resolve(): void
    {
        if (self::$resolved) {
            return;
        }

        self::$resolved = true;
        [$raw, $source] = self::rawToken();

        if ($raw === null) {
            return;
        }

        $row = Database::one(
            "SELECT t.`id`            AS token_id,
                    t.`dispositivo`   AS dispositivo,
                    t.`user_agent`    AS user_agent,
                    t.`ip`            AS ip,
                    t.`ultimo_uso`    AS ultimo_uso,
                    t.`expira_en`     AS expira_en,
                    t.`revocado_en`   AS revocado_en,
                    t.`creado_en`     AS token_creado_en,
                    a.`id`            AS admin_id,
                    a.`usuario`       AS usuario,
                    a.`nombre`        AS nombre,
                    a.`correo`         AS correo,
                    a.`activo`         AS activo,
                    a.`ultimo_acceso` AS ultimo_acceso
             FROM `admin_tokens` t
             INNER JOIN `admins` a ON a.`id` = t.`admin_id`
             WHERE t.`token_hash` = ?
               AND t.`revocado_en` IS NULL
               AND t.`expira_en` > NOW()
               AND a.`activo` = 1
             LIMIT 1",
            [hash('sha256', $raw)]
        );

        if ($row === null) {
            // Un token de cookie inválido se elimina para no repetir la consulta.
            if ($source === 'cookie') {
                self::clearCookie();
            }

            return;
        }

        self::$source = $source;
        self::$token = [
            'id'             => (int) $row['token_id'],
            'dispositivo'    => (string) $row['dispositivo'],
            'user_agent'     => $row['user_agent'],
            'ip'             => $row['ip'],
            'ultimo_uso'     => $row['ultimo_uso'],
            'expira_en'      => $row['expira_en'],
            'revocado_en'    => $row['revocado_en'],
            'creado_en'      => $row['token_creado_en'],
        ];
        self::$admin = [
            'id'            => (int) $row['admin_id'],
            'usuario'       => (string) $row['usuario'],
            'nombre'        => (string) $row['nombre'],
            'correo'        => $row['correo'],
            'activo'        => (int) $row['activo'],
            'ultimo_acceso' => $row['ultimo_acceso'],
        ];

        // Actualiza "último uso" como máximo una vez por minuto.
        Database::execute(
            "UPDATE `admin_tokens`
             SET `ultimo_uso` = NOW()
             WHERE `id` = ?
               AND (`ultimo_uso` IS NULL OR `ultimo_uso` < DATE_SUB(NOW(), INTERVAL 60 SECOND))",
            [self::$token['id']]
        );
    }

    /**
     * Verifica usuario/correo y contraseña. Devuelve la fila completa del
     * administrador (incluido el hash) o null.
     */
    public static function attempt(string $login, string $password): ?array
    {
        $login = trim($login);

        if ($login === '' || $password === '') {
            return null;
        }

        $admin = Database::one(
            'SELECT * FROM `admins` WHERE `usuario` = ? OR `correo` = ? LIMIT 1',
            [$login, $login]
        );

        if ($admin === null) {
            // Comparación de relleno para reducir diferencias de tiempo.
            password_verify($password, self::DUMMY_HASH);

            return null;
        }

        if (!password_verify($password, (string) $admin['password_hash'])) {
            return null;
        }

        if ((int) $admin['activo'] !== 1) {
            return null;
        }

        if (password_needs_rehash((string) $admin['password_hash'], PASSWORD_DEFAULT)) {
            $admin['password_hash'] = self::hashPassword($password);
            Database::execute(
                'UPDATE `admins` SET `password_hash` = ? WHERE `id` = ?',
                [$admin['password_hash'], $admin['id']]
            );
        }

        Database::execute(
            'UPDATE `admins` SET `ultimo_acceso` = NOW() WHERE `id` = ?',
            [$admin['id']]
        );
        $admin['ultimo_acceso'] = date('Y-m-d H:i:s');

        return $admin;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Crea un token nuevo para un dispositivo y devuelve el valor en claro
     * (solo se entrega una vez) junto con la fila almacenada.
     *
     * @return array{token: string, row: array<string, mixed>}
     */
    public static function createToken(
        int $adminId,
        ?string $deviceLabel = null,
        ?string $userAgent = null,
        ?string $ip = null
    ): array {
        $raw = bin2hex(random_bytes(self::TOKEN_BYTES));
        $days = max(1, (int) Config::get('auth.token_days', 30));
        $device = self::deviceName($deviceLabel, $userAgent);
        $userAgent = $userAgent === null ? null : mb_substr($userAgent, 0, 255);
        $ip = $ip === null ? null : mb_substr($ip, 0, 45);

        Database::execute(
            "INSERT INTO `admin_tokens`
                (`admin_id`, `token_hash`, `dispositivo`, `user_agent`, `ip`, `expira_en`)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))",
            [$adminId, hash('sha256', $raw), $device, $userAgent, $ip, $days]
        );

        $id = (int) Database::pdo()->lastInsertId();
        $row = Database::one('SELECT * FROM `admin_tokens` WHERE `id` = ?', [$id]) ?? [];

        return ['token' => $raw, 'row' => $row];
    }

    /**
     * Tokens de un administrador, del más reciente al más antiguo.
     *
     * @return list<array<string, mixed>>
     */
    public static function tokens(int $adminId): array
    {
        return Database::all(
            "SELECT `id`, `dispositivo`, `user_agent`, `ip`, `ultimo_uso`,
                    `expira_en`, `revocado_en`, `creado_en`,
                    CASE
                        WHEN `revocado_en` IS NOT NULL THEN 'revocada'
                        WHEN `expira_en` <= NOW() THEN 'expirada'
                        ELSE 'activa'
                    END AS `estado`
             FROM `admin_tokens`
             WHERE `admin_id` = ?
             ORDER BY (`revocado_en` IS NULL AND `expira_en` > NOW()) DESC,
                      `creado_en` DESC, `id` DESC",
            [$adminId]
        );
    }

    public static function revokeToken(int $tokenId, int $adminId): bool
    {
        $statement = Database::execute(
            "UPDATE `admin_tokens`
             SET `revocado_en` = NOW()
             WHERE `id` = ? AND `admin_id` = ? AND `revocado_en` IS NULL",
            [$tokenId, $adminId]
        );

        return $statement->rowCount() > 0;
    }

    public static function revokeOtherTokens(int $adminId, ?int $keepTokenId = null): int
    {
        if ($keepTokenId === null) {
            $statement = Database::execute(
                'UPDATE `admin_tokens` SET `revocado_en` = NOW()
                 WHERE `admin_id` = ? AND `revocado_en` IS NULL',
                [$adminId]
            );
        } else {
            $statement = Database::execute(
                'UPDATE `admin_tokens` SET `revocado_en` = NOW()
                 WHERE `admin_id` = ? AND `id` <> ? AND `revocado_en` IS NULL',
                [$adminId, $keepTokenId]
            );
        }

        return $statement->rowCount();
    }

    public static function revokeAllForAdmin(int $adminId): int
    {
        return self::revokeOtherTokens($adminId, null);
    }

    public static function findAdmin(int $id): ?array
    {
        return Database::one('SELECT * FROM `admins` WHERE `id` = ? LIMIT 1', [$id]);
    }

    public static function updatePassword(int $adminId, string $password): void
    {
        Database::execute(
            'UPDATE `admins` SET `password_hash` = ? WHERE `id` = ?',
            [self::hashPassword($password), $adminId]
        );
    }

    public static function setActive(int $adminId, bool $active): void
    {
        Database::execute(
            'UPDATE `admins` SET `activo` = ? WHERE `id` = ?',
            [$active ? 1 : 0, $adminId]
        );
    }

    /**
     * Datos públicos del administrador (sin hash de contraseña).
     */
    public static function publicUser(array $admin): array
    {
        return [
            'id'            => (int) $admin['id'],
            'usuario'       => (string) $admin['usuario'],
            'nombre'        => (string) $admin['nombre'],
            'correo'        => $admin['correo'] ?? null,
            'activo'        => (int) ($admin['activo'] ?? 1),
            'ultimo_acceso' => $admin['ultimo_acceso'] ?? null,
        ];
    }

    /**
     * Nombre legible del dispositivo a partir de la etiqueta o del User-Agent.
     */
    public static function deviceName(?string $label, ?string $userAgent): string
    {
        $label = trim((string) $label);
        if ($label !== '') {
            return mb_substr($label, 0, 120);
        }

        $ua = trim((string) $userAgent);
        if ($ua === '') {
            return 'Cliente API';
        }

        $browser = 'Navegador';
        foreach ([
            'PostmanRuntime' => 'Postman',
            'curl/'          => 'cURL',
            'Edg/'           => 'Edge',
            'OPR/'           => 'Opera',
            'Chrome/'        => 'Chrome',
            'Firefox/'       => 'Firefox',
            'Safari/'        => 'Safari',
        ] as $needle => $name) {
            if (stripos($ua, $needle) !== false) {
                $browser = $name;
                break;
            }
        }

        $os = '';
        foreach ([
            'Windows NT' => 'Windows',
            'Android'    => 'Android',
            'iPhone'     => 'iPhone',
            'iPad'       => 'iPad',
            'Mac OS X'   => 'macOS',
            'Linux'      => 'Linux',
        ] as $needle => $name) {
            if (stripos($ua, $needle) !== false) {
                $os = $name;
                break;
            }
        }

        $description = $os === '' ? $browser : $browser . ' en ' . $os;

        return mb_substr($description, 0, 120);
    }

    public static function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        return is_string($ip) && $ip !== '' ? $ip : null;
    }

    public static function userAgent(): ?string
    {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return is_string($agent) && $agent !== '' ? $agent : null;
    }

    public static function setCookie(string $rawToken): void
    {
        $options = self::cookieOptions();
        $options['expires'] = time() + max(1, (int) Config::get('auth.token_days', 30)) * 86400;

        setcookie(self::cookieName(), $rawToken, $options);
    }

    public static function clearCookie(): void
    {
        $options = self::cookieOptions();
        $options['expires'] = time() - 3600;

        setcookie(self::cookieName(), '', $options);
        unset($_COOKIE[self::cookieName()]);
    }

    private static function cookieName(): string
    {
        return (string) Config::get('auth.cookie', 'escuela_admin_token');
    }

    /**
     * @return array{expires: int, path: string, secure: bool, httponly: bool, samesite: string}
     */
    private static function cookieOptions(): array
    {
        $path = Request::basePath();
        $path = $path === '' ? '/' : $path;

        return [
            'expires'  => 0,
            'path'     => $path,
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string} [token, origen]
     */
    private static function rawToken(): array
    {
        $isApi = str_starts_with(Request::path(), '/api');
        $header = self::authorizationHeader();

        if ($header !== ''
            && preg_match('/^Bearer\s+([a-f0-9]{64})$/i', $header, $matches) === 1) {
            return [$matches[1], 'bearer'];
        }

        /*
         * La API usa exclusivamente tokens Bearer: la cookie del navegador
         * no autentica peticiones /api/*. Así se separa la sesión web de la API.
         */
        if ($isApi) {
            return [null, null];
        }

        $cookie = $_COOKIE[self::cookieName()] ?? null;
        if (is_string($cookie) && preg_match('/^[a-f0-9]{64}$/i', $cookie) === 1) {
            return [$cookie, 'cookie'];
        }

        return [null, null];
    }

    /**
     * Cabecera Authorization, con compatibilidad para Apache CGI/FastCGI.
     */
    private static function authorizationHeader(): string
    {
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
            $value = $_SERVER[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (is_string($value)
                        && strcasecmp((string) $name, 'Authorization') === 0
                        && trim($value) !== '') {
                        return trim($value);
                    }
                }
            }
        }

        return '';
    }

    /**
     * Fecha legible de expiración del token actual.
     */
    public static function expiresAt(): ?string
    {
        $token = self::tokenInfo();

        return $token['expira_en'] ?? null;
    }

    public static function tokenCreatedAt(): ?DateTimeImmutable
    {
        $token = self::tokenInfo();
        if (($token['creado_en'] ?? null) === null) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $token['creado_en']);

        return $date === false ? null : $date;
    }
}
