<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\Auth;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Response::redirect(url('/'));
        }

        $this->renderLogin(null, (string) ($_GET['next'] ?? ''));
    }

    public function login(): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $next = $this->safeNext((string) ($_POST['next'] ?? ''));

        if ($this->isLockedOut()) {
            http_response_code(429);
            $this->renderLogin(
                'Demasiados intentos fallidos. Espera unos minutos antes de volver a intentarlo.',
                $next,
                $login
            );

            return;
        }

        $admin = Auth::attempt($login, $password);

        if ($admin === null) {
            $this->recordFailedAttempt();
            http_response_code(401);
            $this->renderLogin('Usuario o contraseña incorrectos.', $next, $login);

            return;
        }

        $this->clearAttempts();
        session_regenerate_id(true);

        $created = Auth::createToken(
            (int) $admin['id'],
            null,
            Auth::userAgent(),
            Auth::clientIp()
        );

        Auth::setCookie($created['token']);
        Session::flash('success', 'Bienvenido, ' . $admin['nombre'] . '.');

        $this->redirectTo($next);
    }

    public function logout(): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        $adminId = Auth::id();
        $tokenId = Auth::tokenId();

        if ($adminId !== null && $tokenId !== null) {
            Auth::revokeToken($tokenId, $adminId);
        }

        Auth::clearCookie();
        Session::destroy();

        Response::redirect(url_with('/login', ['logout' => '1']));
    }

    private function renderLogin(?string $error, string $next, string $login = ''): void
    {
        View::render('auth/login', [
            'title' => 'Iniciar sesión',
            'error' => $error,
            'next'  => $this->safeNext($next),
            'login' => $login,
            'notice' => $this->notice(),
            'standalone' => true,
        ]);
    }

    private function notice(): ?string
    {
        return match ((string) ($_GET['logout'] ?? '')) {
            '1'        => 'Tu sesión se cerró correctamente.',
            'revocado' => 'El acceso de este dispositivo fue revocado.',
            default    => null,
        };
    }

    /**
     * Solo permite rutas internas para evitar redirecciones abiertas.
     */
    private function safeNext(string $next): string
    {
        $next = trim($next);

        if ($next === ''
            || !str_starts_with($next, '/')
            || str_starts_with($next, '//')
            || str_contains($next, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $next) === 1) {
            return '/';
        }

        return $next;
    }

    private function redirectTo(string $next): void
    {
        $parts = parse_url($next);
        $path = is_array($parts) && isset($parts['path']) ? (string) $parts['path'] : '/';
        $query = [];

        if (is_array($parts) && isset($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        $target = url($path === '' ? '/' : $path);

        if ($query !== []) {
            $target .= (str_contains($target, '?') ? '&' : '?') . http_build_query($query);
        }

        Response::redirect($target);
    }

    private function isLockedOut(): bool
    {
        $attempts = $this->recentAttempts();

        return count($attempts) >= max(1, (int) Config::get('auth.max_attempts', 5));
    }

    private function recordFailedAttempt(): void
    {
        $attempts = $this->recentAttempts();
        $attempts[] = time();
        Session::set('_login_attempts', $attempts);
    }

    private function clearAttempts(): void
    {
        Session::forget('_login_attempts');
    }

    /**
     * @return list<int>
     */
    private function recentAttempts(): array
    {
        $window = max(1, (int) Config::get('auth.lockout_minutes', 15)) * 60;
        $since = time() - $window;
        $stored = Session::get('_login_attempts', []);

        if (!is_array($stored)) {
            return [];
        }

        $recent = [];
        foreach ($stored as $timestamp) {
            if (is_int($timestamp) && $timestamp >= $since) {
                $recent[] = $timestamp;
            }
        }

        if ($recent !== $stored) {
            Session::set('_login_attempts', $recent);
        }

        return $recent;
    }
}
