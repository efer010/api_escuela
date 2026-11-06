<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

final class Validator
{
    /**
     * Valida y normaliza la entrada de un recurso.
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>} [datos, errores]
     */
    public static function validate(array $input, array $definition, int|string|null $exceptId = null): array
    {
        $data = [];
        $errors = [];
        $table = (string) $definition['table'];
        $primaryKey = (string) ($definition['primary_key'] ?? 'id');

        foreach ($definition['fields'] as $name => $field) {
            $type = (string) ($field['type'] ?? 'text');
            $rules = $field['rules'] ?? [];
            $required = in_array('required', $rules, true);

            if ($type === 'bool') {
                if (!array_key_exists($name, $input) && array_key_exists('default', $field)) {
                    $data[$name] = (int) $field['default'];
                } else {
                    $data[$name] = !empty($input[$name]) ? 1 : 0;
                }
                continue;
            }

            $raw = $input[$name] ?? null;
            if (is_string($raw)) {
                $raw = trim($raw);
            }
            $value = $raw === '' ? null : $raw;

            if ($value === null && array_key_exists('default', $field)) {
                $value = $field['default'] === 'today' ? date('Y-m-d') : $field['default'];
            }

            if ($required && ($value === null || $value === '')) {
                $errors[$name] = 'Este campo es obligatorio.';
                $data[$name] = null;
                continue;
            }

            if ($value === null) {
                $data[$name] = null;
                continue;
            }

            $error = self::checkRules($value, $rules);
            if ($error !== null) {
                $errors[$name] = $error;
                $data[$name] = $value;
                continue;
            }

            $data[$name] = self::cast($value, $field);
        }

        self::checkUniques($data, $errors, $definition, $exceptId, $table, $primaryKey);

        return [$data, $errors];
    }

    private static function checkRules(mixed $value, array $rules): ?string
    {
        foreach ($rules as $rule) {
            if (!is_string($rule) || $rule === 'required') {
                continue;
            }

            [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);

            switch ($name) {
                case 'max':
                    if (mb_strlen((string) $value) > (int) $argument) {
                        return 'No debe superar los ' . (int) $argument . ' caracteres.';
                    }
                    break;

                case 'min':
                    if (mb_strlen((string) $value) < (int) $argument) {
                        return 'Debe tener al menos ' . (int) $argument . ' caracteres.';
                    }
                    break;

                case 'int':
                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        return 'Debe ser un número entero.';
                    }
                    break;

                case 'numeric':
                    if (!is_numeric((string) $value)) {
                        return 'Debe ser un valor numérico.';
                    }
                    break;

                case 'between':
                    [$min, $max] = array_pad(explode(',', (string) $argument), 2, '0');
                    if (!is_numeric((string) $value) || (float) $value < (float) $min || (float) $value > (float) $max) {
                        return 'Debe estar entre ' . $min . ' y ' . $max . '.';
                    }
                    break;

                case 'date':
                    if (!self::isDate((string) $value)) {
                        return 'Debe ser una fecha válida (AAAA-MM-DD).';
                    }
                    break;

                case 'email':
                    if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                        return 'Debe ser un correo electrónico válido.';
                    }
                    break;

                case 'in':
                    if (!in_array((string) $value, explode(',', (string) $argument), true)) {
                        return 'Selecciona una opción válida.';
                    }
                    break;

                case 'regex':
                    if (@preg_match((string) $argument, (string) $value) !== 1) {
                        return 'El formato no es válido.';
                    }
                    break;

                case 'exists':
                    [$relatedTable, $relatedColumn] = array_pad(explode(',', (string) $argument), 2, 'id');
                    $exists = Database::value(
                        "SELECT 1 FROM `{$relatedTable}` WHERE `{$relatedColumn}` = ? LIMIT 1",
                        [$value]
                    );
                    if ($exists === null) {
                        return 'El valor seleccionado no existe.';
                    }
                    break;
            }
        }

        return null;
    }

    private static function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function cast(mixed $value, array $field): mixed
    {
        return match ($field['cast'] ?? 'string') {
            'int'   => (int) $value,
            'float' => is_numeric((string) $value) ? (float) $value : $value,
            default => (string) $value,
        };
    }

    private static function checkUniques(
        array $data,
        array &$errors,
        array $definition,
        int|string|null $exceptId,
        string $table,
        string $primaryKey
    ): void {
        foreach ($definition['fields'] as $name => $field) {
            if (empty($field['unique'])) {
                continue;
            }
            if (isset($errors[$name]) || !array_key_exists($name, $data) || $data[$name] === null) {
                continue;
            }

            $sql = "SELECT `{$primaryKey}` FROM `{$table}` WHERE `{$name}` = ?";
            $params = [$data[$name]];

            if ($exceptId !== null && $exceptId !== '') {
                $sql .= " AND `{$primaryKey}` <> ?";
                $params[] = $exceptId;
            }
            $sql .= ' LIMIT 1';

            if (Database::value($sql, $params) !== null) {
                $errors[$name] = 'Este valor ya está registrado.';
            }
        }

        foreach ($definition['unique_groups'] ?? [] as $group) {
            $conditions = [];
            $params = [];
            $valid = true;

            foreach ($group as $column) {
                if (!array_key_exists($column, $data) || $data[$column] === null || $data[$column] === '') {
                    $valid = false;
                    break;
                }
                if (isset($errors[$column])) {
                    $valid = false;
                    break;
                }
                $conditions[] = "`{$column}` = ?";
                $params[] = $data[$column];
            }

            if (!$valid) {
                continue;
            }

            $sql = "SELECT `{$primaryKey}` FROM `{$table}` WHERE " . implode(' AND ', $conditions);

            if ($exceptId !== null && $exceptId !== '') {
                $sql .= " AND `{$primaryKey}` <> ?";
                $params[] = $exceptId;
            }
            $sql .= ' LIMIT 1';

            if (Database::value($sql, $params) !== null) {
                foreach ($group as $column) {
                    if (!isset($errors[$column])) {
                        $errors[$column] = 'Ya existe un registro con esta combinación.';
                        break;
                    }
                }
            }
        }
    }
}
