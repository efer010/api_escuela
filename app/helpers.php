<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Request;
use App\Core\Session;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    $base = Request::basePath();
    $path = '/' . ltrim($path, '/');

    if (Config::get('app.pretty_urls', true)) {
        return $base . ($path === '/' ? '/' : $path);
    }

    $route = ltrim($path, '/');

    return $base . '/index.php' . ($route === '' ? '' : '?r=' . $route);
}

function url_with(string $path, array $query = []): string
{
    $base = url($path);
    $query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '');

    if ($query === []) {
        return $base;
    }

    return $base . (str_contains($base, '?') ? '&' : '?') . http_build_query($query);
}

function asset(string $path): string
{
    return Request::basePath() . '/assets/' . ltrim($path, '/');
}

/**
 * Parámetros actuales de la URL (sin la ruta interna "r").
 */
function current_query(array $overrides = []): array
{
    $query = $_GET;
    unset($query['r']);

    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(App\Core\Csrf::token()) . '">';
}

function flash_messages(): string
{
    $flashes = Session::pullFlashes();
    if ($flashes === []) {
        return '';
    }

    $html = '';
    foreach ($flashes as $flash) {
        $type = in_array($flash['type'] ?? 'info', ['success', 'error', 'warning', 'info'], true)
            ? $flash['type']
            : 'info';
        $html .= '<div class="alert alert--' . e($type) . '" role="status">'
            . '<span class="alert__icon">' . ($type === 'success' ? '✓' : ($type === 'error' ? '!' : 'i')) . '</span>'
            . '<span>' . e($flash['message'] ?? '') . '</span>'
            . '<button type="button" class="alert__close" aria-label="Cerrar">&times;</button>'
            . '</div>';
    }

    return $html;
}

function format_date(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));

    return $date !== false ? $date->format('d/m/Y') : $value;
}

function format_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $value;
}

function nombre_completo(array $row): string
{
    $apellidos = trim(($row['apellido_paterno'] ?? '') . ' ' . ($row['apellido_materno'] ?? ''));
    $nombre = trim((string) ($row['nombre'] ?? ''));

    if ($apellidos === '') {
        return $nombre;
    }

    return $apellidos . ($nombre !== '' ? ', ' . $nombre : '');
}

/**
 * Valor legible de un campo (incluye campos virtuales y relaciones).
 */
function value_for(array $definition, array $row, string $field, array $relations = []): string
{
    if (isset($definition['virtual'][$field])) {
        $virtual = $definition['virtual'][$field];

        if (isset($virtual['relation_field'])) {
            $id = $row[$virtual['relation_field']] ?? null;
            if ($id === null || $id === '') {
                return (string) ($virtual['empty'] ?? '—');
            }
            $map = $relations[$virtual['relation_field']]['map'] ?? [];

            return $map[(string) $id] ?? (string) ($virtual['empty'] ?? '—');
        }

        $compute = $virtual['compute'] ?? null;
        if (is_string($compute) && function_exists($compute)) {
            return (string) $compute($row);
        }
        if (is_callable($compute)) {
            return (string) $compute($row);
        }

        return '';
    }

    $fieldDefinition = $definition['fields'][$field] ?? [];
    $value = $row[$field] ?? null;

    if ($value === null || $value === '') {
        return '—';
    }

    return match ($fieldDefinition['type'] ?? 'text') {
        'select' => ($relations[$field]['map'] ?? [])[(string) $value] ?? (string) $value,
        'enum'   => $fieldDefinition['options'][$value] ?? (string) $value,
        'date'   => format_date((string) $value),
        'bool'   => ((int) $value === 1) ? 'Sí' : 'No',
        default  => (string) $value,
    };
}

/**
 * Celda HTML para las tablas (badges, formato, etc.).
 */
function cell_html(array $definition, array $row, string $column, array $relations = []): string
{
    $fieldDefinition = $definition['fields'][$column] ?? [];
    $type = $fieldDefinition['type'] ?? '';

    if ($type === 'bool' || in_array($column, ['activo', 'activa'], true)) {
        $on = (int) ($row[$column] ?? 0) === 1;
        $label = $column === 'activa' ? ($on ? 'Activa' : 'Inactiva') : ($on ? 'Activo' : 'Inactivo');

        return $on
            ? '<span class="badge badge-success">' . $label . '</span>'
            : '<span class="badge badge-muted">' . $label . '</span>';
    }

    if ($column === 'estado') {
        $raw = (string) ($row[$column] ?? '');
        $classes = ['inscrito' => 'info', 'aprobado' => 'success', 'reprobado' => 'danger', 'baja' => 'muted'];
        $class = $classes[$raw] ?? 'muted';

        return '<span class="badge badge-' . $class . '">' . e(value_for($definition, $row, $column, $relations)) . '</span>';
    }

    if ($column === 'calificacion') {
        $value = $row[$column] ?? null;
        if ($value === null || $value === '') {
            return '<span class="text-muted">Sin calificar</span>';
        }

        return '<strong>' . e(number_format((float) $value, 2)) . '</strong>';
    }

    return e(value_for($definition, $row, $column, $relations));
}
