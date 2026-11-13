<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth;

final class ApiAuthController
{
    /**
     * POST /api/login
     * Autentica al administrador y entrega un token nuevo para el dispositivo.
     */
    public function login(): void
    {
        $payload = Request::body();

        $login = trim((string) (
            $payload['usuario']
            ?? $payload['correo']
            ?? $payload['login']
            ?? ''
        ));
        $password = (string) ($payload['password'] ?? $payload['contrasena'] ?? '');
        $device = $payload['dispositivo'] ?? $payload['device'] ?? null;

        $errors = [];
        if ($login === '') {
            $errors['usuario'] = 'El usuario o correo es obligatorio.';
        }
        if ($password === '') {
            $errors['password'] = 'La contraseña es obligatoria.';
        }

        if ($errors !== []) {
            Response::json([
                'error'   => true,
                'status'  => 422,
                'message' => 'Los datos enviados no son válidos.',
                'errors'  => $errors,
            ], 422);
        }

        $admin = Auth::attempt($login, $password);

        if ($admin === null) {
            Response::json([
                'error'   => true,
                'status'  => 401,
                'message' => 'Usuario o contraseña incorrectos.',
            ], 401);
        }

        $created = Auth::createToken(
            (int) $admin['id'],
            is_string($device) ? $device : null,
            Auth::userAgent(),
            Auth::clientIp()
        );

        Response::json([
            'message'    => 'Sesión iniciada correctamente.',
            'token'      => $created['token'],
            'token_type' => 'Bearer',
            'expires_at' => $created['row']['expira_en'] ?? null,
            'admin'      => Auth::publicUser($admin),
        ]);
    }

    /**
     * POST /api/logout
     * Revoca el token con el que se autenticó la petición.
     */
    public function logout(): void
    {
        $adminId = Auth::id();
        $tokenId = Auth::tokenId();

        if ($adminId === null || $tokenId === null) {
            Response::json([
                'error'   => true,
                'status'  => 401,
                'message' => 'Se requiere un token de acceso válido.',
            ], 401);
        }

        Auth::revokeToken($tokenId, $adminId);

        Response::json([
            'message' => 'Sesión cerrada. El token del dispositivo fue revocado.',
        ]);
    }

    /**
     * GET /api/me
     * Datos del administrador y del token actual.
     */
    public function me(): void
    {
        $admin = Auth::user();
        $token = Auth::tokenInfo();

        if ($admin === null || $token === null) {
            Response::json([
                'error'   => true,
                'status'  => 401,
                'message' => 'Se requiere un token de acceso válido.',
            ], 401);
        }

        Response::json([
            'data' => [
                'admin' => Auth::publicUser($admin),
                'token' => [
                    'id'          => (int) $token['id'],
                    'dispositivo' => $token['dispositivo'],
                    'ip'          => $token['ip'],
                    'creado_en'   => $token['creado_en'],
                    'ultimo_uso'  => $token['ultimo_uso'],
                    'expira_en'   => $token['expira_en'],
                ],
            ],
        ]);
    }
}
