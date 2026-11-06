<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Ejecutor de migraciones SQL sin dependencias externas.
 *
 * Cada archivo .sql dentro de database/migrations es una migración.
 * Su reverso opcional se guarda junto a él con el sufijo .down.sql.
 * Las migraciones aplicadas se registran en la tabla `migraciones`.
 */
final class Migrator
{
    private const TABLE = 'migraciones';

    private ?PDO $pdo = null;

    private string $path;

    private string $seedersPath;

    public function __construct(?string $path = null, ?string $seedersPath = null)
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);

        $this->path = rtrim($path ?? $base . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations', '/\\');
        $this->seedersPath = rtrim($seedersPath ?? $base . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders', '/\\');
    }

    public function path(): string
    {
        return $this->path;
    }

    public function seedersPath(): string
    {
        return $this->seedersPath;
    }

    /**
     * Devuelve la conexión, creando antes la base de datos y la tabla de control
     * si todavía no existen.
     */
    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $this->createDatabaseIfMissing();
        $this->pdo = Database::pdo();
        $this->ensureTable();

        return $this->pdo;
    }

    /**
     * Aplica todas las migraciones pendientes en un lote nuevo.
     *
     * @return list<string> Nombres de las migraciones aplicadas.
     */
    public function migrate(): array
    {
        $applied = $this->applied();
        $pending = array_diff_key($this->migrationFiles(), $applied);

        if ($pending === []) {
            return [];
        }

        $batch = 1;
        foreach ($applied as $row) {
            $batch = max($batch, (int) $row['lote'] + 1);
        }

        $insert = $this->pdo()->prepare(
            'INSERT INTO `' . self::TABLE . '` (`migracion`, `lote`) VALUES (?, ?)'
        );

        $executed = [];
        foreach ($pending as $name => $file) {
            $this->runFile($file);
            $insert->execute([$name, $batch]);
            $executed[] = $name;
        }

        return $executed;
    }

    /**
     * Revierte un lote completo usando los archivos .down.sql.
     * Sin argumentos revierte el último lote aplicado.
     *
     * @return list<string> Nombres de las migraciones revertidas.
     */
    public function rollback(?int $batch = null): array
    {
        $applied = $this->applied();

        if ($applied === []) {
            return [];
        }

        if ($batch === null) {
            $batch = 0;
            foreach ($applied as $row) {
                $batch = max($batch, (int) $row['lote']);
            }
        }

        $statement = $this->pdo()->prepare(
            'SELECT `migracion` FROM `' . self::TABLE . '` WHERE `lote` = ? ORDER BY `id` DESC'
        );
        $statement->execute([$batch]);
        $names = $statement->fetchAll(PDO::FETCH_COLUMN);

        if ($names === []) {
            throw new RuntimeException('No existe el lote ' . $batch . ' en la tabla de migraciones.');
        }

        $delete = $this->pdo()->prepare(
            'DELETE FROM `' . self::TABLE . '` WHERE `migracion` = ?'
        );

        $executed = [];
        foreach ($names as $name) {
            $down = $this->downFileFor((string) $name);
            if (!is_file($down)) {
                throw new RuntimeException(
                    'No existe el archivo de reverso para "' . $name . '": ' . basename($down)
                );
            }

            $this->runFile($down);
            $delete->execute([$name]);
            $executed[] = (string) $name;
        }

        return $executed;
    }

    /**
     * Elimina todas las tablas y vistas de la base configurada y vuelve a
     * ejecutar las migraciones desde cero. Operación destructiva.
     *
     * @return list<string> Nombres de las migraciones aplicadas.
     */
    public function fresh(bool $seed = false): array
    {
        $pdo = $this->pdo();

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $views = $pdo->query(
                "SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()"
            )->fetchAll(PDO::FETCH_COLUMN);

            foreach ($views as $view) {
                $pdo->exec('DROP VIEW IF EXISTS `' . $this->quoteIdentifier((string) $view) . '`');
            }

            $tables = $pdo->query(
                "SELECT TABLE_NAME FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
            )->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $pdo->exec('DROP TABLE IF EXISTS `' . $this->quoteIdentifier((string) $table) . '`');
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        $this->ensureTable();
        $executed = $this->migrate();

        if ($seed) {
            $this->seed();
        }

        return $executed;
    }

    /**
     * Ejecuta los archivos de database/seeders. Deben ser idempotentes.
     *
     * @return list<string> Archivos ejecutados.
     */
    public function seed(): array
    {
        $files = $this->sqlFiles($this->seedersPath, false);

        $executed = [];
        foreach ($files as $name => $file) {
            $this->runFile($file);
            $executed[] = $name;
        }

        return $executed;
    }

    /**
     * Estado de cada migración, incluyendo pendientes y archivos faltantes.
     *
     * @return list<array{migracion: string, estado: string, lote: int|null, aplicada_en: string|null}>
     */
    public function status(): array
    {
        $files = $this->migrationFiles();
        $applied = $this->applied();

        $rows = [];
        foreach ($files as $name => $file) {
            $row = $applied[$name] ?? null;
            $rows[] = [
                'migracion'   => $name,
                'estado'      => $row === null ? 'pendiente' : 'aplicada',
                'lote'        => $row === null ? null : (int) $row['lote'],
                'aplicada_en' => $row === null ? null : (string) $row['aplicada_en'],
            ];
        }

        foreach (array_diff_key($applied, $files) as $name => $row) {
            $rows[] = [
                'migracion'   => (string) $name,
                'estado'      => 'archivo faltante',
                'lote'        => (int) $row['lote'],
                'aplicada_en' => (string) $row['aplicada_en'],
            ];
        }

        return $rows;
    }

    /**
     * Número del último lote aplicado (0 si no hay migraciones).
     */
    public function lastBatch(): int
    {
        $applied = $this->applied();

        $batch = 0;
        foreach ($applied as $row) {
            $batch = max($batch, (int) $row['lote']);
        }

        return $batch;
    }

    /**
     * Migraciones registradas, indexadas por nombre de archivo.
     *
     * @return array<string, array{migracion: string, lote: string|int, aplicada_en: string}>
     */
    private function applied(): array
    {
        $rows = $this->pdo()
            ->query('SELECT `migracion`, `lote`, `aplicada_en` FROM `' . self::TABLE . '` ORDER BY `id`')
            ->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['migracion']] = $row;
        }

        return $map;
    }

    /**
     * Archivos de migración disponibles (sin los .down.sql), ordenados.
     *
     * @return array<string, string>
     */
    private function migrationFiles(): array
    {
        return $this->sqlFiles($this->path, true);
    }

    /**
     * @return array<string, string>
     */
    private function sqlFiles(string $directory, bool $excludeDown): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*.sql') ?: [];

        if ($excludeDown) {
            $files = array_filter(
                $files,
                static fn (string $file): bool => !str_ends_with(strtolower($file), '.down.sql')
            );
        }

        sort($files, SORT_STRING);

        $map = [];
        foreach ($files as $file) {
            $map[basename($file)] = $file;
        }

        return $map;
    }

    private function downFileFor(string $name): string
    {
        $base = preg_replace('/\.sql$/i', '', $name) ?? $name;

        return $this->path . DIRECTORY_SEPARATOR . $base . '.down.sql';
    }

    /**
     * Ejecuta todas las sentencias de un archivo SQL.
     */
    private function runFile(string $file): void
    {
        $sql = file_get_contents($file);

        if ($sql === false) {
            throw new RuntimeException('No se pudo leer el archivo SQL: ' . $file);
        }

        foreach ($this->splitStatements($sql) as $statement) {
            try {
                $this->pdo()->exec($statement);
            } catch (PDOException $e) {
                throw new RuntimeException(
                    'Error al ejecutar ' . basename($file) . ': ' . $e->getMessage()
                    . "\nSentencia: " . $this->shorten($statement),
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Divide un script en sentencias respetando cadenas, identificadores y
     * comentarios (--, # y /* ... *\/). No soporta DELIMITER.
     *
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $quote = null;
        $lineComment = false;
        $blockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($lineComment) {
                if ($char === "\n") {
                    $lineComment = false;
                    $buffer .= "\n";
                }
                continue;
            }

            if ($blockComment) {
                if ($char === '*' && $next === '/') {
                    $blockComment = false;
                    $i++;
                }
                continue;
            }

            if ($quote !== null) {
                $buffer .= $char;

                if ($char === '\\' && $quote !== '`') {
                    if ($next !== '') {
                        $buffer .= $next;
                        $i++;
                    }
                    continue;
                }

                if ($char === $quote) {
                    if ($next === $quote) {
                        $buffer .= $next;
                        $i++;
                        continue;
                    }
                    $quote = null;
                }
                continue;
            }

            if ($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) {
                $lineComment = true;
                $i++;
                continue;
            }

            if ($char === '#') {
                $lineComment = true;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $blockComment = true;
                $i++;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    private function ensureTable(): void
    {
        $this->pdo()->exec(
            'CREATE TABLE IF NOT EXISTS `' . self::TABLE . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `migracion` VARCHAR(255) NOT NULL,
                `lote` INT UNSIGNED NOT NULL,
                `aplicada_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_migracion` (`migracion`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci'
        );
    }

    private function createDatabaseIfMissing(): void
    {
        $config = Config::get('db');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['charset'] ?? 'utf8mb4'
        );

        try {
            $server = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $server->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci',
                $this->quoteIdentifier((string) $config['name'])
            ));
        } catch (PDOException $e) {
            throw new RuntimeException(
                'No se pudo crear o verificar la base de datos "' . $config['name'] . '". '
                . 'Revisa las credenciales en app/config.php. Detalle: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }

    private function shorten(string $sql, int $max = 200): string
    {
        $sql = preg_replace('/\s+/', ' ', $sql) ?? $sql;

        return strlen($sql) > $max ? substr($sql, 0, $max) . '…' : $sql;
    }
}
