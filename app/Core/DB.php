<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'] ?? 3306, $c['name'], $c['charset'] ?? 'utf8mb4');
        self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function all(string $sql, array $p = []): array { return self::run($sql, $p)->fetchAll(); }
    public static function one(string $sql, array $p = []): ?array { $r = self::run($sql, $p)->fetch(); return $r ?: null; }
    public static function value(string $sql, array $p = []): mixed { return self::run($sql, $p)->fetchColumn(); }
    public static function pairs(string $sql, array $p = []): array { return self::run($sql, $p)->fetchAll(PDO::FETCH_KEY_PAIR); }

    /** Column names are always whitelisted by the caller (Resources schema). */
    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table,
            implode(',', array_map(fn($c) => "`$c`", $cols)),
            implode(',', array_fill(0, count($cols), '?')));
        self::run($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $pk, mixed $id): int
    {
        if (!$data) return 0;
        $set = implode(',', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        return self::run("UPDATE `$table` SET $set WHERE `$pk` = ?", [...array_values($data), $id])->rowCount();
    }
}
