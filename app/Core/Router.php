<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method: string, path: string, handler: callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'path'    => '/' . trim($path, '/'),
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $allowed = [];

        foreach ($this->routes as $route) {
            $params = $this->match($route['path'], $path);

            if ($params === null) {
                continue;
            }

            $matchesMethod = $route['method'] === $method
                || ($method === 'HEAD' && $route['method'] === 'GET');

            if (!$matchesMethod) {
                $allowed[$route['method']] = true;
                continue;
            }

            call_user_func_array($route['handler'], array_values($params));

            return;
        }

        if ($allowed !== []) {
            $list = implode(', ', array_keys($allowed));
            throw new HttpException(405, "El método {$method} no está permitido para esta ruta. Métodos permitidos: {$list}.");
        }

        throw new HttpException(404, 'La página o recurso solicitado no existe.');
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = '#^' . preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
            $pattern
        ) . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
