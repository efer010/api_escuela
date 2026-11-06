<?php
declare(strict_types=1);

namespace App\Core;

final class Resources
{
    private static ?array $items = null;

    public static function all(): array
    {
        if (self::$items === null) {
            self::$items = require APP_PATH . '/resources.php';
        }

        return self::$items;
    }

    public static function names(): array
    {
        return array_keys(self::all());
    }

    public static function has(string $name): bool
    {
        return array_key_exists($name, self::all());
    }

    public static function get(string $name): array
    {
        $items = self::all();

        if (!array_key_exists($name, $items)) {
            throw new HttpException(404, 'El recurso "' . $name . '" no existe.');
        }

        return $items[$name];
    }
}
