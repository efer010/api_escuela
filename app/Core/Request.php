<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private static ?array $body = null;
    private static ?string $basePath = null;

    public static function method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_POST['_method'] ?? null);
            if (is_string($override) && $override !== '') {
                $method = strtoupper($override);
            }
        }

        return $method;
    }

    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = self::basePath();

        if ($base !== '' && ($uri === $base || str_starts_with($uri, $base . '/'))) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . trim($uri, '/');

        if ($uri === '/index.php') {
            $uri = '/';
        }

        if ($uri === '/') {
            $rewrite = $_GET['r'] ?? null;
            if (is_string($rewrite) && trim($rewrite, '/') !== '') {
                $uri = '/' . trim($rewrite, '/');
            }
        }

        return $uri;
    }

    public static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');

        self::$basePath = ($directory === '' || $directory === '.' || $directory === '/') ? '' : $directory;

        return self::$basePath;
    }

    public static function body(): array
    {
        if (self::$body !== null) {
            return self::$body;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $raw = '';

        if (!in_array($method, ['GET', 'HEAD'], true)) {
            $raw = (string) file_get_contents('php://input');
        }

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($raw, true);
            self::$body = is_array($decoded) ? $decoded : [];
        } elseif ($raw !== '' && !str_contains($contentType, 'multipart/form-data')) {
            parse_str($raw, $parsed);
            self::$body = is_array($parsed) ? $parsed : [];
        } else {
            self::$body = $_POST;
        }

        return self::$body;
    }

    public static function input(): array
    {
        return array_merge($_GET, self::body());
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}
