<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Repository;
use App\Core\Resources;
use App\Core\Response;
use App\Core\Validator;
use App\Services\BoletaService;

final class ApiController
{
    public function indexRoot(): void
    {
        $resources = [];

        foreach (Resources::all() as $name => $definition) {
            $resources[$name] = [
                'singular' => $definition['singular'],
                'plural'   => $definition['plural'],
                'fields'   => array_keys($definition['fields']),
                'endpoints' => [
                    'list'   => 'GET /api/' . $name,
                    'create' => 'POST /api/' . $name,
                    'show'   => 'GET /api/' . $name . '/{id}',
                    'update' => 'PUT|PATCH /api/' . $name . '/{id}',
                    'delete' => 'DELETE /api/' . $name . '/{id}',
                ],
            ];
        }

        Response::json([
            'name'      => 'API Sistema Escolar',
            'version'   => '1.0.0',
            'resources' => $resources,
            'extras'    => [
                'stats'  => 'GET /api/stats',
                'boleta' => 'GET /api/boleta?matricula=A-2025-001&ciclo=2025-2026',
            ],
        ]);
    }

    public function index(string $resource): void
    {
        $definition = Resources::get($resource);
        $repository = new Repository($definition);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? \App\Core\Config::get('app.api_per_page', 25));
        $search = trim((string) ($_GET['q'] ?? ''));

        $filters = [];
        foreach ($definition['filters'] ?? [] as $field) {
            if (array_key_exists($field, $_GET) && is_string($_GET[$field]) && trim($_GET[$field]) !== '') {
                $filters[$field] = trim($_GET[$field]);
            }
        }

        $sort = isset($_GET['sort']) && in_array($_GET['sort'], $definition['sortable'] ?? [], true)
            ? (string) $_GET['sort']
            : null;
        $direction = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $result = $repository->paginate($page, $perPage, $search, $filters, $sort, $direction);

        Response::json([
            'data' => $result['items'],
            'meta' => [
                'page'      => $result['page'],
                'per_page'  => $result['per_page'],
                'total'     => $result['total'],
                'last_page' => $result['last_page'],
                'from'      => $result['from'],
                'to'        => $result['to'],
            ],
        ]);
    }

    public function show(string $resource, string $id): void
    {
        $definition = Resources::get($resource);
        $repository = new Repository($definition);

        $row = ctype_digit($id) ? $repository->find((int) $id) : null;

        if ($row === null) {
            Response::json(['error' => true, 'status' => 404, 'message' => 'Registro no encontrado.'], 404);
        }

        Response::json(['data' => $row]);
    }

    public function store(string $resource): void
    {
        $definition = Resources::get($resource);
        $repository = new Repository($definition);
        $payload = \App\Core\Request::body();

        [$data, $errors] = Validator::validate($payload, $definition);

        if ($errors !== []) {
            Response::json([
                'error'   => true,
                'status'  => 422,
                'message' => 'Los datos enviados no son válidos.',
                'errors'  => $errors,
            ], 422);
        }

        $id = $repository->create($data);
        $row = $repository->find($id);

        if (!headers_sent()) {
            header('Location: ' . url('/api/' . $resource . '/' . $id));
        }

        Response::json([
            'message' => $definition['singular'] . ' creado correctamente.',
            'data'    => $row,
        ], 201);
    }

    public function update(string $resource, string $id): void
    {
        $definition = Resources::get($resource);
        $repository = new Repository($definition);
        $existing = ctype_digit($id) ? $repository->find((int) $id) : null;

        if ($existing === null) {
            Response::json(['error' => true, 'status' => 404, 'message' => 'Registro no encontrado.'], 404);
        }

        $payload = array_merge($existing, \App\Core\Request::body());

        [$data, $errors] = Validator::validate($payload, $definition, $existing[$definition['primary_key']]);

        if ($errors !== []) {
            Response::json([
                'error'   => true,
                'status'  => 422,
                'message' => 'Los datos enviados no son válidos.',
                'errors'  => $errors,
            ], 422);
        }

        $repository->update($existing[$definition['primary_key']], $data);

        Response::json([
            'message' => $definition['singular'] . ' actualizado correctamente.',
            'data'    => $repository->find($existing[$definition['primary_key']]),
        ]);
    }

    public function destroy(string $resource, string $id): void
    {
        $definition = Resources::get($resource);
        $repository = new Repository($definition);
        $existing = ctype_digit($id) ? $repository->find((int) $id) : null;

        if ($existing === null) {
            Response::json(['error' => true, 'status' => 404, 'message' => 'Registro no encontrado.'], 404);
        }

        $repository->delete($existing[$definition['primary_key']]);

        Response::json([
            'message' => $definition['singular'] . ' eliminado correctamente.',
            'id'      => (int) $existing[$definition['primary_key']],
        ]);
    }

    public function stats(): void
    {
        Response::json([
            'data' => [
                'alumnos'       => (int) Database::value('SELECT COUNT(*) FROM `alumnos`'),
                'alumnos_activos' => (int) Database::value('SELECT COUNT(*) FROM `alumnos` WHERE `activo` = 1'),
                'profesores'    => (int) Database::value('SELECT COUNT(*) FROM `profesores`'),
                'grupos'        => (int) Database::value('SELECT COUNT(*) FROM `grupos`'),
                'materias'      => (int) Database::value('SELECT COUNT(*) FROM `materias`'),
                'inscripciones' => (int) Database::value('SELECT COUNT(*) FROM `inscripciones`'),
                'promedio_general' => Database::value('SELECT ROUND(AVG(`calificacion`), 2) FROM `inscripciones` WHERE `calificacion` IS NOT NULL'),
            ],
        ]);
    }

    public function boleta(): void
    {
        $matricula = isset($_GET['matricula']) && is_string($_GET['matricula']) ? trim($_GET['matricula']) : null;
        $ciclo = isset($_GET['ciclo']) && is_string($_GET['ciclo']) ? trim($_GET['ciclo']) : null;

        $matricula = $matricula === '' ? null : $matricula;
        $ciclo = $ciclo === '' ? null : $ciclo;

        $rows = BoletaService::rows($matricula, $ciclo);

        Response::json([
            'data'    => $rows,
            'resumen' => BoletaService::resumen($rows),
        ]);
    }
}
