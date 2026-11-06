<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function check(?string $token): void
    {
        $expected = $_SESSION['_csrf'] ?? '';

        if ($expected === '' || !is_string($token) || !hash_equals($expected, $token)) {
            throw new HttpException(419, 'El token de seguridad (CSRF) no es válido o expiró. Recarga la página e intenta de nuevo.');
        }
    }
}
