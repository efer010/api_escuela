<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\View;

$basePath = '/' . $resource;
$sortable = $definition['sortable'] ?? [];
$needsRouteField = !Config::get('app.pretty_urls', true);

$columnLabel = static function (string $column) use ($definition): string {
    if (isset($definition['virtual'][$column]['label'])) {
        return $definition['virtual'][$column]['label'];
    }
    return $definition['fields'][$column]['label'] ?? $column;
};

$sortUrl = static function (string $column) use ($definition, $sort, $direction, $basePath): string {
    $nextDirection = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
    return url_with($basePath, current_query(['sort' => $column, 'dir' => $nextDirection, 'page' => null]));
};
?>

<section class="page-head">
    <div>
        <p class="page-head__eyebrow"><?= e($definition['description'] ?? '') ?></p>
        <h2><?= e($definition['plural']) ?></h2>
    </div>
    <a class="btn btn-primary" href="<?= e(url($basePath . '/nuevo')) ?>">
        <span aria-hidden="true">+</span> Nuevo <?= e(mb_strtolower($definition['singular'])) ?>
    </a>
</section>

<section class="card">
    <form class="toolbar" method="get" action="<?= e(url($basePath)) ?>">
        <?php if ($needsRouteField): ?>
            <input type="hidden" name="r" value="<?= e($resource) ?>">
        <?php endif; ?>
        <?php if ($sort !== null): ?>
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($direction) ?>">
        <?php endif; ?>

        <label class="search">
            <span class="search__icon" aria-hidden="true">🔍</span>
            <input type="search" name="q" value="<?= e($search) ?>"
                   placeholder="Buscar en <?= e(mb_strtolower($definition['plural'])) ?>…"
                   aria-label="Buscar">
        </label>

        <?php foreach ($definition['filters'] ?? [] as $filterField): ?>
            <?php
            $fieldDefinition = $definition['fields'][$filterField] ?? [];
            $fieldType = $fieldDefinition['type'] ?? 'text';
            $currentFilter = $filters[$filterField] ?? '';
            $inputName = 'f_' . $filterField;
            ?>
            <label class="filter">
                <span><?= e($fieldDefinition['label'] ?? $filterField) ?></span>
                <?php if ($fieldType === 'select'): ?>
                    <select name="<?= e($inputName) ?>">
                        <option value="">Todos</option>
                        <?php foreach (($relations[$filterField]['rows'] ?? []) as $option): ?>
                            <option value="<?= e((string) $option['value']) ?>"
                                <?= (string) $option['value'] === (string) $currentFilter ? 'selected' : '' ?>>
                                <?= e((string) $option['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($fieldType === 'enum'): ?>
                    <select name="<?= e($inputName) ?>">
                        <option value="">Todos</option>
                        <?php foreach (($fieldDefinition['options'] ?? []) as $optionValue => $optionLabel): ?>
                            <option value="<?= e((string) $optionValue) ?>"
                                <?= (string) $optionValue === (string) $currentFilter ? 'selected' : '' ?>>
                                <?= e((string) $optionLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($fieldType === 'bool'): ?>
                    <select name="<?= e($inputName) ?>">
                        <option value="">Todos</option>
                        <option value="1" <?= (string) $currentFilter === '1' ? 'selected' : '' ?>>Activos</option>
                        <option value="0" <?= (string) $currentFilter === '0' ? 'selected' : '' ?>>Inactivos</option>
                    </select>
                <?php else: ?>
                    <input type="text" name="<?= e($inputName) ?>" value="<?= e((string) $currentFilter) ?>"
                           placeholder="<?= e($fieldDefinition['label'] ?? $filterField) ?>">
                <?php endif; ?>
            </label>
        <?php endforeach; ?>

        <div class="toolbar__actions">
            <button class="btn btn-secondary btn-sm" type="submit">Filtrar</button>
            <a class="btn btn-ghost btn-sm" href="<?= e(url($basePath)) ?>">Limpiar</a>
        </div>
    </form>

    <?php if ($result['items'] === []): ?>
        <div class="empty-state">
            <span class="empty-state__icon"><?= e($definition['icon'] ?? '📭') ?></span>
            <h3>No hay registros</h3>
            <p>
                <?= $search !== '' || $filters !== []
                    ? 'No se encontraron coincidencias con los filtros aplicados.'
                    : 'Todavía no se ha registrado ningún ' . e(mb_strtolower($definition['singular'])) . '.' ?>
            </p>
            <a class="btn btn-primary btn-sm" href="<?= e(url($basePath . '/nuevo')) ?>">Crear el primero</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <?php foreach ($definition['list'] as $column): ?>
                            <th>
                                <?php if (in_array($column, $sortable, true)): ?>
                                    <a class="sort-link <?= $sort === $column ? 'is-active' : '' ?>"
                                       href="<?= e($sortUrl($column)) ?>">
                                        <?= e($columnLabel($column)) ?>
                                        <?php if ($sort === $column): ?>
                                            <span aria-hidden="true"><?= $direction === 'asc' ? '↑' : '↓' ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <?= e($columnLabel($column)) ?>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                        <th class="table__actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['items'] as $row): ?>
                        <?php $id = (string) $row[$definition['primary_key']]; ?>
                        <tr>
                            <?php foreach ($definition['list'] as $column): ?>
                                <td><?= cell_html($definition, $row, $column, $relations) ?></td>
                            <?php endforeach; ?>
                            <td class="table__actions">
                                <div class="row-actions">
                                    <a class="btn btn-ghost btn-xs" href="<?= e(url($basePath . '/' . $id)) ?>">Ver</a>
                                    <a class="btn btn-secondary btn-xs" href="<?= e(url($basePath . '/' . $id . '/editar')) ?>">Editar</a>
                                    <form method="post" action="<?= e(url($basePath . '/' . $id . '/eliminar')) ?>"
                                          data-confirm="¿Eliminar este <?= e(mb_strtolower($definition['singular'])) ?>? Esta acción no se puede deshacer.">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-danger btn-xs" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php View::partial('resources/_pagination', ['result' => $result, 'resource' => $resource]); ?>
    <?php endif; ?>
</section>
