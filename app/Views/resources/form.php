<?php
declare(strict_types=1);

use App\Core\View;

$basePath = '/' . $resource;
$generalError = $errors['_general'] ?? null;
$fieldErrorCount = count(array_filter(array_keys($errors), static fn (string $key): bool => $key !== '_general'));
$backUrl = $isEdit ? url($basePath . '/' . $row[$definition['primary_key']]) : url($basePath);
?>

<section class="page-head">
    <div>
        <p class="page-head__eyebrow">
            <a class="link" href="<?= e(url($basePath)) ?>"><?= e($definition['plural']) ?></a>
            <?php if ($isEdit && $recordName !== null): ?>
                · <?= e($recordName) ?>
            <?php endif; ?>
        </p>
        <h2><?= e($isEdit ? 'Editar ' : 'Nuevo ') . e(mb_strtolower($definition['singular'])) ?></h2>
    </div>
    <a class="btn btn-ghost" href="<?= e($backUrl) ?>">Cancelar</a>
</section>

<?php if ($generalError !== null): ?>
    <div class="alert alert--error" role="alert">
        <span class="alert__icon">!</span>
        <span><?= e($generalError) ?></span>
    </div>
<?php endif; ?>

<?php if ($fieldErrorCount > 0): ?>
    <div class="alert alert--warning" role="alert">
        <span class="alert__icon">!</span>
        <span>Revisa los campos marcados en rojo. Hay <?= e((string) $fieldErrorCount) ?> dato(s) por corregir.</span>
    </div>
<?php endif; ?>

<form class="card form-card" method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-grid">
        <?php foreach ($definition['fields'] as $name => $field): ?>
            <?php
            $current = $old[$name] ?? $row[$name] ?? ($field['default'] ?? null);
            if ($current === 'today') {
                $current = date('Y-m-d');
            }
            View::partial('resources/_field', [
                'name'      => $name,
                'field'     => $field,
                'current'   => $current,
                'error'     => $errors[$name] ?? null,
                'relations' => $relations,
            ]);
            ?>
        <?php endforeach; ?>
    </div>

    <footer class="form-actions">
        <a class="btn btn-ghost" href="<?= e($backUrl) ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit">
            <?= $isEdit ? 'Guardar cambios' : 'Crear ' . e(mb_strtolower($definition['singular'])) ?>
        </button>
    </footer>
</form>
