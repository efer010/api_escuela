<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = Config::get('db');
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'No se pudo conectar a la base de datos "' . $config['name'] . '". '
                . 'Verifica que MariaDB/MySQL esté en ejecución y revisa app/config.php. '
                . 'Detalle: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    public static function execute(string $sql, array $params = []): PDOStatement
    {
        return self::query($sql, $params);
    }

    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $columnSql = implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        self::query("INSERT INTO `{$table}` ({$columnSql}) VALUES ({$placeholders})", array_values($data));

        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, string $primaryKey, int|string $id, array $data): bool
    {
        $sets = implode(', ', array_map(static fn (string $column): string => '`' . $column . '` = ?', array_keys($data)));
        $params = array_values($data);
        $params[] = $id;

        self::query("UPDATE `{$table}` SET {$sets} WHERE `{$primaryKey}` = ?", $params);

        return true;
    }

    public static function delete(string $table, string $primaryKey, int|string $id): bool
    {
        self::query("DELETE FROM `{$table}` WHERE `{$primaryKey}` = ?", [$id]);

        return true;
    }
}
