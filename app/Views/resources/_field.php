<?php
declare(strict_types=1);

$type = $field['type'] ?? 'text';
$label = $field['label'] ?? $name;
$required = in_array('required', $field['rules'] ?? [], true);
$inputId = 'field_' . $name;
$wide = !empty($field['wide']);
$options = $field['options'] ?? [];
?>

<div class="field <?= $wide ? 'field--wide' : '' ?> <?= $error !== null ? 'field--has-error' : '' ?>">
    <label for="<?= e($inputId) ?>">
        <?= e($label) ?>
        <?php if ($required): ?><span class="required" aria-hidden="true">*</span><?php endif; ?>
    </label>

    <?php if ($type === 'textarea'): ?>
        <textarea id="<?= e($inputId) ?>" name="<?= e($name) ?>" rows="<?= e((string) ($field['rows'] ?? 3)) ?>"
                  placeholder="<?= e($field['placeholder'] ?? '') ?>"><?= e((string) ($current ?? '')) ?></textarea>

    <?php elseif ($type === 'select'): ?>
        <select id="<?= e($inputId) ?>" name="<?= e($name) ?>">
            <option value=""><?= e($field['empty'] ?? '— Selecciona —') ?></option>
            <?php foreach (($relations[$name]['rows'] ?? []) as $option): ?>
                <option value="<?= e((string) $option['value']) ?>"
                    <?= (string) $option['value'] === (string) $current ? 'selected' : '' ?>>
                    <?= e((string) $option['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php elseif ($type === 'enum'): ?>
        <select id="<?= e($inputId) ?>" name="<?= e($name) ?>">
            <?php if (!$required): ?><option value="">— Sin definir —</option><?php endif; ?>
            <?php foreach ($options as $optionValue => $optionLabel): ?>
                <option value="<?= e((string) $optionValue) ?>"
                    <?= (string) $optionValue === (string) $current ? 'selected' : '' ?>>
                    <?= e((string) $optionLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php elseif ($type === 'bool'): ?>
        <?php $isOn = (int) ($current ?? 0) === 1; ?>
        <label class="switch" for="<?= e($inputId) ?>">
            <input type="hidden" name="<?= e($name) ?>" value="0">
            <input type="checkbox" id="<?= e($inputId) ?>" name="<?= e($name) ?>" value="1" <?= $isOn ? 'checked' : '' ?>>
            <span class="switch__track" aria-hidden="true"><span></span></span>
            <span class="switch__text"><?= $isOn ? 'Sí' : 'No' ?></span>
        </label>

    <?php elseif ($type === 'date'): ?>
        <input type="date" id="<?= e($inputId) ?>" name="<?= e($name) ?>"
               value="<?= e((string) ($current ?? '')) ?>">

    <?php elseif ($type === 'number'): ?>
        <input type="number" id="<?= e($inputId) ?>" name="<?= e($name) ?>"
               value="<?= e((string) ($current ?? '')) ?>"
               step="<?= e((string) ($field['step'] ?? '1')) ?>"
               <?= isset($field['min']) ? 'min="' . e((string) $field['min']) . '"' : '' ?>
               <?= isset($field['max']) ? 'max="' . e((string) $field['max']) . '"' : '' ?>
               placeholder="<?= e($field['placeholder'] ?? '') ?>">

    <?php else: ?>
        <?php
        $inputType = match ($type) {
            'email' => 'email',
            'tel'   => 'tel',
            default => 'text',
        };
        ?>
        <input type="<?= e($inputType) ?>" id="<?= e($inputId) ?>" name="<?= e($name) ?>"
               value="<?= e((string) ($current ?? '')) ?>"
               placeholder="<?= e($field['placeholder'] ?? '') ?>"
               <?= $required ? 'required' : '' ?>>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <p class="field__error"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if (!empty($field['help'])): ?>
        <p class="field__help"><?= e($field['help']) ?></p>
    <?php endif; ?>
</div>
