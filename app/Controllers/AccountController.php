<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\Auth;

final class AccountController
{
    public function index(): void
    {
        $this->render();
    }

    public function updatePassword(): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        $admin = Auth::user();
        if ($admin === null) {
            throw new HttpException(401, 'Debes iniciar sesión.');
        }

        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');
        $min = max(6, (int) Config::get('auth.password_min', 8));
        $errors = [];

        $fresh = Auth::findAdmin((int) $admin['id']);
        if ($fresh === null || !password_verify($current, (string) $fresh['password_hash'])) {
            $errors['current_password'] = 'La contraseña actual no es correcta.';
        }

        if (mb_strlen($new) < $min) {
            $errors['new_password'] = 'La nueva contraseña debe tener al menos ' . $min . ' caracteres.';
        }

        if ($new !== $confirm) {
            $errors['new_password_confirm'] = 'La confirmación no coincide con la nueva contraseña.';
        }

        if ($new !== '' && $current !== '' && hash_equals($current, $new)) {
            $errors['new_password'] = 'La nueva contraseña debe ser diferente de la actual.';
        }

        if ($errors !== []) {
            $this->render($errors, 422);

            return;
        }

        Auth::updatePassword((int) $admin['id'], $new);
        $revoked = Auth::revokeOtherTokens((int) $admin['id'], Auth::tokenId());

        Session::flash(
            'success',
            'Contraseña actualizada correctamente.'
            . ($revoked > 0 ? ' Se cerraron ' . $revoked . ' sesión(es) en otros dispositivos.' : '')
        );

        Response::redirect(url('/cuenta'));
    }

    public function revokeDevice(string $id): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        $admin = Auth::user();
        if ($admin === null) {
            throw new HttpException(401, 'Debes iniciar sesión.');
        }

        $tokenId = ctype_digit($id) ? (int) $id : 0;
        $currentId = Auth::tokenId();

        if ($tokenId <= 0) {
            Session::flash('error', 'El dispositivo indicado no es válido.');
            Response::redirect(url('/cuenta'));
        }

        if ($tokenId === $currentId) {
            Auth::revokeToken($tokenId, (int) $admin['id']);
            Auth::clearCookie();
            Session::destroy();

            Response::redirect(url_with('/login', ['logout' => 'revocado']));
        }

        if (Auth::revokeToken($tokenId, (int) $admin['id'])) {
            Session::flash('success', 'El acceso del dispositivo fue revocado.');
        } else {
            Session::flash('error', 'El dispositivo no existe o ya estaba revocado.');
        }

        Response::redirect(url('/cuenta'));
    }

    public function revokeOtherDevices(): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        $admin = Auth::user();
        if ($admin === null) {
            throw new HttpException(401, 'Debes iniciar sesión.');
        }

        $revoked = Auth::revokeOtherTokens((int) $admin['id'], Auth::tokenId());

        if ($revoked > 0) {
            Session::flash('success', 'Se revocaron ' . $revoked . ' dispositivo(s) adicional(es).');
        } else {
            Session::flash('info', 'No había otros dispositivos con sesión activa.');
        }

        Response::redirect(url('/cuenta'));
    }

    private function render(array $passwordErrors = [], int $status = 200): void
    {
        $admin = Auth::user();
        if ($admin === null) {
            throw new HttpException(401, 'Debes iniciar sesión.');
        }

        if ($status !== 200) {
            http_response_code($status);
        }

        $tokens = Auth::tokens((int) $admin['id']);
        $active = 0;
        foreach ($tokens as $token) {
            if (($token['estado'] ?? '') === 'activa') {
                $active++;
            }
        }

        View::render('account/index', [
            'title'           => 'Mi cuenta',
            'active'          => 'cuenta',
            'admin'           => $admin,
            'tokens'          => $tokens,
            'currentTokenId'  => Auth::tokenId(),
            'activeTokens'    => $active,
            'passwordErrors'  => $passwordErrors,
            'passwordMin'     => max(6, (int) Config::get('auth.password_min', 8)),
        ]);
    }
}
