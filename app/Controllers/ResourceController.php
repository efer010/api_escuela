<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Repository;
use App\Core\Resources;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use PDOException;

final class ResourceController
{
    private array $definition;
    private Repository $repository;

    public function __construct(private string $resource)
    {
        $this->definition = Resources::get($resource);
        $this->repository = new Repository($this->definition);
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim((string) ($_GET['q'] ?? ''));
        $sort = isset($_GET['sort']) && in_array($_GET['sort'], $this->definition['sortable'] ?? [], true)
            ? (string) $_GET['sort']
            : null;
        $direction = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $filters = [];
        foreach ($this->definition['filters'] ?? [] as $field) {
            $key = 'f_' . $field;
            if (array_key_exists($key, $_GET) && is_string($_GET[$key]) && trim($_GET[$key]) !== '') {
                $filters[$field] = trim($_GET[$key]);
            }
        }

        $result = $this->repository->paginate(
            $page,
            (int) \App\Core\Config::get('app.per_page', 10),
            $search,
            $filters,
            $sort,
            $direction
        );

        View::render('resources/index', [
            'title'      => $this->definition['plural'],
            'active'     => $this->resource,
            'resource'   => $this->resource,
            'definition' => $this->definition,
            'result'     => $result,
            'relations'  => $this->repository->relationMaps(),
            'search'     => $search,
            'filters'    => $filters,
            'sort'       => $sort,
            'direction'  => $direction,
        ]);
    }

    public function create(): void
    {
        $this->renderForm($this->defaults(), [], [], false, url('/' . $this->resource));
    }

    public function store(): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        [$data, $errors] = Validator::validate($_POST, $this->definition);

        if ($errors !== []) {
            http_response_code(422);
            $this->renderForm(array_merge($this->defaults(), $_POST), $errors, $_POST, false, url('/' . $this->resource));
            return;
        }

        try {
            $id = $this->repository->create($data);
        } catch (PDOException $exception) {
            http_response_code(422);
            $errors['_general'] = 'No se pudo guardar el registro: ' . $exception->getMessage();
            $this->renderForm(array_merge($this->defaults(), $_POST), $errors, $_POST, false, url('/' . $this->resource));
            return;
        }

        Session::flash('success', $this->definition['singular'] . ' creado correctamente.');
        Response::redirect(url('/' . $this->resource . '/' . $id));
    }

    public function show(string $id): void
    {
        $row = $this->findOrFail($id);
        $boletaUrl = null;

        if ($this->resource === 'alumnos' && !empty($row['matricula'])) {
            $boletaUrl = url_with('/boleta', ['matricula' => $row['matricula']]);
        }

        View::render('resources/show', [
            'title'      => $this->definition['singular'] . ' · ' . $this->recordName($row),
            'active'     => $this->resource,
            'resource'   => $this->resource,
            'definition' => $this->definition,
            'row'        => $row,
            'relations'  => $this->repository->relationMaps(),
            'boletaUrl'  => $boletaUrl,
        ]);
    }

    public function edit(string $id): void
    {
        $row = $this->findOrFail($id);

        $this->renderForm($row, [], [], true, url('/' . $this->resource . '/' . $id));
    }

    public function update(string $id): void
    {
        $row = $this->findOrFail($id);
        Csrf::check($_POST['_csrf'] ?? null);

        [$data, $errors] = Validator::validate($_POST, $this->definition, $row[$this->definition['primary_key']]);

        if ($errors !== []) {
            http_response_code(422);
            $this->renderForm(array_merge($row, $_POST), $errors, $_POST, true, url('/' . $this->resource . '/' . $id));
            return;
        }

        try {
            $this->repository->update($row[$this->definition['primary_key']], $data);
        } catch (PDOException $exception) {
            http_response_code(422);
            $errors['_general'] = 'No se pudo actualizar el registro: ' . $exception->getMessage();
            $this->renderForm(array_merge($row, $_POST), $errors, $_POST, true, url('/' . $this->resource . '/' . $id));
            return;
        }

        Session::flash('success', $this->definition['singular'] . ' actualizado correctamente.');
        Response::redirect(url('/' . $this->resource . '/' . $id));
    }

    public function destroy(string $id): void
    {
        $row = $this->findOrFail($id);
        Csrf::check($_POST['_csrf'] ?? null);

        try {
            $this->repository->delete($row[$this->definition['primary_key']]);
            Session::flash('success', $this->definition['singular'] . ' eliminado correctamente.');
        } catch (PDOException $exception) {
            Session::flash('error', 'No se pudo eliminar el registro: ' . $exception->getMessage());
        }

        Response::redirect(url('/' . $this->resource));
    }

    private function findOrFail(string $id): array
    {
        $row = ctype_digit($id) ? $this->repository->find((int) $id) : null;

        if ($row === null) {
            throw new HttpException(404, 'No se encontró el registro solicitado.');
        }

        return $row;
    }

    private function renderForm(array $row, array $errors, array $old, bool $isEdit, string $action): void
    {
        View::render('resources/form', [
            'title'      => ($isEdit ? 'Editar ' : 'Nuevo ') . mb_strtolower($this->definition['singular']),
            'active'     => $this->resource,
            'resource'   => $this->resource,
            'definition' => $this->definition,
            'row'        => $row,
            'errors'     => $errors,
            'old'        => $old,
            'relations'  => $this->repository->relationMaps(),
            'isEdit'     => $isEdit,
            'action'     => $action,
            'recordName' => $isEdit ? $this->recordName($row) : null,
        ]);
    }

    private function defaults(): array
    {
        $row = [];

        foreach ($this->definition['fields'] as $name => $field) {
            if (array_key_exists('default', $field)) {
                $row[$name] = $field['default'] === 'today' ? date('Y-m-d') : $field['default'];
            }
        }

        return $row;
    }

    private function recordName(array $row): string
    {
        if (isset($row['nombre'], $row['apellido_paterno'])) {
            return nombre_completo($row);
        }

        foreach (['nombre', 'clave', 'matricula'] as $column) {
            if (!empty($row[$column])) {
                return (string) $row[$column];
            }
        }

        return '#' . ($row[$this->definition['primary_key']] ?? '');
    }
}
