<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $file = APP_PATH . '/Views/' . $view . '.php';

        if (!is_file($file)) {
            throw new RuntimeException('Vista no encontrada: ' . $view);
        }

        $data['title'] = $data['title'] ?? Config::get('app.name');

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        $content = ob_get_clean();

        // Las vistas con documento HTML completo (por ejemplo, el login)
        // se imprimen sin envolverlas en el layout de la aplicación.
        if (($data['standalone'] ?? false) === true) {
            echo $content;

            return;
        }

        require APP_PATH . '/Views/layout.php';
    }

    public static function partial(string $view, array $data = []): void
    {
        $file = APP_PATH . '/Views/' . $view . '.php';

        if (!is_file($file)) {
            throw new RuntimeException('Parcial no encontrado: ' . $view);
        }

        extract($data, EXTR_SKIP);
        require $file;
    }
}
