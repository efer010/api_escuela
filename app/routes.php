<?php
declare(strict_types=1);

use App\Controllers\ApiController;
use App\Controllers\BoletaController;
use App\Controllers\DashboardController;
use App\Controllers\ResourceController;
use App\Core\Resources;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', static fn () => (new DashboardController())->index());
    $router->get('/inicio', static fn () => (new DashboardController())->index());
    $router->get('/boleta', static fn () => (new BoletaController())->index());

    // Rutas fijas de la API (deben registrarse antes de /api/{resource}).
    $router->get('/api', static fn () => (new ApiController())->indexRoot());
    $router->get('/api/stats', static fn () => (new ApiController())->stats());
    $router->get('/api/boleta', static fn () => (new ApiController())->boleta());

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
