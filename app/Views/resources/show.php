<?php
declare(strict_types=1);

$basePath = '/' . $resource;
$id = (string) $row[$definition['primary_key']];

$detailFields = [];

foreach ($definition['virtual'] ?? [] as $name => $virtual) {
    if (isset($virtual['relation_field'])) {
        $detailFields[$name] = ['virtual' => true, 'definition' => $virtual];
    }
}

foreach ($definition['fields'] as $name => $field) {
    if (($field['type'] ?? 'text') === 'select') {
        continue;
    }
    $detailFields[$name] = ['virtual' => false, 'definition' => $field];
}
?>

<section class="page-head">
    <div>
        <p class="page-head__eyebrow">
            <a class="link" href="<?= e(url($basePath)) ?>"><?= e($definition['plural']) ?></a>
            · registro #<?= e($id) ?>
        </p>
        <h2><?= e($definition['singular']) ?></h2>
    </div>
    <div class="page-head__actions">
        <?php if ($boletaUrl !== null): ?>
            <a class="btn btn-ghost" href="<?= e($boletaUrl) ?>">🧾 Ver boleta</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?= e(url($basePath . '/' . $id . '/editar')) ?>">Editar</a>
        <form method="post" action="<?= e(url($basePath . '/' . $id . '/eliminar')) ?>"
              data-confirm="¿Eliminar este <?= e(mb_strtolower($definition['singular'])) ?>? Esta acción no se puede deshacer.">
            <?= csrf_field() ?>
            <button class="btn btn-danger" type="submit">Eliminar</button>
        </form>
    </div>
</section>

<section class="card detail-card">
    <dl class="detail-grid">
        <?php foreach ($detailFields as $name => $meta): ?>
            <?php
            $fieldDefinition = $meta['definition'];
            $label = $fieldDefinition['label'] ?? $name;
            $isBool = ($fieldDefinition['type'] ?? '') === 'bool';
            $value = value_for($definition, $row, $name, $relations);
            ?>
            <div class="detail-item">
                <dt><?= e($label) ?></dt>
                <dd>
                    <?php if ($isBool): ?>
                        <?= cell_html($definition, $row, $name, $relations) ?>
                    <?php else: ?>
                        <?= $value === '—' ? '<span class="text-muted">—</span>' : e($value) ?>
                    <?php endif; ?>
                </dd>
            </div>
        <?php endforeach; ?>

        <?php if (isset($row['creado_en'])): ?>
            <div class="detail-item">
                <dt>Creado</dt>
                <dd><?= e(format_datetime((string) $row['creado_en'])) ?></dd>
            </div>
        <?php endif; ?>

        <?php if (isset($row['actualizado_en'])): ?>
            <div class="detail-item">
                <dt>Última actualización</dt>
                <dd><?= e(format_datetime((string) $row['actualizado_en'])) ?></dd>
            </div>
        <?php endif; ?>
    </dl>

    <footer class="detail-actions">
        <a class="btn btn-ghost" href="<?= e(url($basePath)) ?>">← Volver a <?= e(mb_strtolower($definition['plural'])) ?></a>
    </footer>
</section>
