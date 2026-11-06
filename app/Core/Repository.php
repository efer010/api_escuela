<?php
declare(strict_types=1);

namespace App\Core;

final class Repository
{
    private string $table;
    private string $primaryKey;

    public function __construct(private array $definition)
    {
        $this->table = (string) $definition['table'];
        $this->primaryKey = (string) ($definition['primary_key'] ?? 'id');
    }

    public function find(int|string $id): ?array
    {
        return Database::one(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1",
            [$id]
        );
    }

    public function paginate(
        int $page = 1,
        int $perPage = 10,
        string $search = '',
        array $filters = [],
        ?string $sort = null,
        string $direction = 'asc'
    ): array {
        $perPage = max(1, min(100, $perPage));
        [$where, $params] = $this->buildWhere($search, $filters);

        $total = (int) Database::value("SELECT COUNT(*) FROM `{$this->table}`{$where}", $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $order = $this->buildOrder($sort, $direction);
        $items = Database::all(
            "SELECT * FROM `{$this->table}`{$where}{$order} LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items'     => $items,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => $lastPage,
            'from'      => $total > 0 ? $offset + 1 : 0,
            'to'        => min($offset + $perPage, $total),
        ];
    }

    public function create(array $data): int
    {
        return Database::insert($this->table, $data);
    }

    public function update(int|string $id, array $data): bool
    {
        return Database::update($this->table, $this->primaryKey, $id, $data);
    }

    public function delete(int|string $id): bool
    {
        return Database::delete($this->table, $this->primaryKey, $id);
    }

    public function count(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM `{$this->table}`");
    }

    /**
     * Opciones de un campo select (relación).
     *
     * @return array<int, array{value: mixed, label: string}>
     */
    public function options(string $field): array
    {
        $relation = $this->definition['fields'][$field]['relation'] ?? null;

        if ($relation === null) {
            return [];
        }

        $sql = "SELECT `{$relation['value']}` AS value, {$relation['label']} AS label "
            . "FROM `{$relation['table']}` ORDER BY label ASC";

        return Database::all($sql);
    }

    /**
     * Opciones y mapa de etiquetas para todos los campos de relación.
     *
     * @return array<string, array{rows: array<int, array{value: mixed, label: string}>, map: array<string, string>}>
     */
    public function relationMaps(): array
    {
        $result = [];

        foreach ($this->definition['fields'] as $name => $field) {
            if (($field['type'] ?? '') !== 'select' || empty($field['relation'])) {
                continue;
            }

            $rows = $this->options($name);
            $map = [];
            foreach ($rows as $row) {
                $map[(string) $row['value']] = (string) $row['label'];
            }

            $result[$name] = ['rows' => $rows, 'map' => $map];
        }

        return $result;
    }

    public function sortableColumns(): array
    {
        return $this->definition['sortable'] ?? [];
    }

    private function buildWhere(string $search, array $filters): array
    {
        $conditions = [];
        $params = [];

        if ($search !== '' && !empty($this->definition['search'])) {
            $parts = [];
            foreach ($this->definition['search'] as $column) {
                $parts[] = "`{$column}` LIKE ?";
                $params[] = '%' . $search . '%';
            }
            if ($parts !== []) {
                $conditions[] = '(' . implode(' OR ', $parts) . ')';
            }
        }

        foreach ($this->definition['filters'] ?? [] as $filter) {
            if (!array_key_exists($filter, $filters)) {
                continue;
            }

            $value = $filters[$filter];
            if ($value === null || $value === '') {
                continue;
            }

            $type = $this->definition['fields'][$filter]['type'] ?? 'text';

            if (in_array($type, ['text', 'textarea', 'email', 'tel'], true)) {
                $conditions[] = "`{$filter}` LIKE ?";
                $params[] = '%' . $value . '%';
            } else {
                $conditions[] = "`{$filter}` = ?";
                $params[] = $value;
            }
        }

        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function buildOrder(?string $sort, string $direction): string
    {
        $sortable = $this->sortableColumns();
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        if ($sort !== null && in_array($sort, $sortable, true)) {
            return " ORDER BY `{$sort}` {$direction}, `{$this->primaryKey}` ASC";
        }

        $defaultOrder = $this->definition['default_order'] ?? [$this->primaryKey => 'ASC'];
        $parts = [];

        foreach ($defaultOrder as $column => $order) {
            $order = strtoupper((string) $order) === 'DESC' ? 'DESC' : 'ASC';
            $parts[] = "`{$column}` {$order}";
        }

        return $parts === [] ? '' : ' ORDER BY ' . implode(', ', $parts);
    }
}
