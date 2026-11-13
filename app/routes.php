<?php
declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\ApiAuthController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\BoletaController;
use App\Controllers\DashboardController;
use App\Controllers\DocsController;
use App\Controllers\ResourceController;
use App\Core\Resources;
use App\Core\Router;

return static function (Router $router): void {
    // Autenticación de administradores.
    $router->get('/login', static fn () => (new AuthController())->showLogin());
    $router->post('/login', static fn () => (new AuthController())->login());
    $router->post('/logout', static fn () => (new AuthController())->logout());

    // Cuenta y dispositivos del administrador.
    $router->get('/cuenta', static fn () => (new AccountController())->index());
    $router->post('/cuenta/password', static fn () => (new AccountController())->updatePassword());
    $router->post('/cuenta/dispositivos/revocar-otras', static fn () => (new AccountController())->revokeOtherDevices());
    $router->post('/cuenta/dispositivos/{id}/revocar', static fn (string $id) => (new AccountController())->revokeDevice($id));

    $router->get('/', static fn () => (new DashboardController())->index());
    $router->get('/inicio', static fn () => (new DashboardController())->index());
    $router->get('/boleta', static fn () => (new BoletaController())->index());

    // Rutas fijas de la API (deben registrarse antes de /api/{resource}).
    $router->post('/api/login', static fn () => (new ApiAuthController())->login());
    $router->post('/api/logout', static fn () => (new ApiAuthController())->logout());
    $router->get('/api/me', static fn () => (new ApiAuthController())->me());
    $router->get('/api', static fn () => (new ApiController())->indexRoot());
    $router->get('/api/stats', static fn () => (new ApiController())->stats());
    $router->get('/api/boleta', static fn () => (new ApiController())->boleta());
    $router->get('/api/docs', static fn () => (new DocsController())->ui());
    $router->get('/api/openapi.json', static fn () => (new DocsController())->spec());

    foreach (Resources::names() as $resource) {
        // Vistas HTML
        $router->get('/' . $resource, static fn () => (new ResourceController($resource))->index());
        $router->get('/' . $resource . '/nuevo', static fn () => (new ResourceController($resource))->create());
        $router->post('/' . $resource, static fn () => (new ResourceController($resource))->store());
        $router->get('/' . $resource . '/{id}', static fn (string $id) => (new ResourceController($resource))->show($id));
        $router->get('/' . $resource . '/{id}/editar', static fn (string $id) => (new ResourceController($resource))->edit($id));
        $router->post('/' . $resource . '/{id}', static fn (string $id) => (new ResourceController($resource))->update($id));
        $router->post('/' . $resource . '/{id}/eliminar', static fn (string $id) => (new ResourceController($resource))->destroy($id));

        // API JSON
        $router->get('/api/' . $resource, static fn () => (new ApiController())->index($resource));
        $router->post('/api/' . $resource, static fn () => (new ApiController())->store($resource));
        $router->get('/api/' . $resource . '/{id}', static fn (string $id) => (new ApiController())->show($resource, $id));
        $router->put('/api/' . $resource . '/{id}', static fn (string $id) => (new ApiController())->update($resource, $id));
        $router->patch('/api/' . $resource . '/{id}', static fn (string $id) => (new ApiController())->update($resource, $id));
        $router->delete('/api/' . $resource . '/{id}', static fn (string $id) => (new ApiController())->destroy($resource, $id));
    }
};
