<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * База для моделей: простой CRUD над одной таблицей.
 */
abstract class Model
{
    /** Имя таблицы. Задаётся в наследнике. */
    protected static string $table = '';

    /** Разрешённые для массового заполнения поля. */
    protected static array $fillable = [];

    protected static function db(): PDO
    {
        return Database::pdo();
    }

    public static function find(int $id): ?array
    {
        $st = self::db()->prepare('SELECT * FROM `' . static::$table . '` WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /**
     * Список с пагинацией.
     * @return array{items: array, total: int, page: int, pages: int, per_page: int}
     */
    public static function paginate(int $page = 1, int $perPage = 20, string $orderBy = 'id DESC'): array
    {
        $orderBy = self::safeOrderBy($orderBy);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = (int) self::db()->query('SELECT COUNT(*) FROM `' . static::$table . '`')->fetchColumn();

        $st = self::db()->prepare(
            'SELECT * FROM `' . static::$table . '` ORDER BY ' . $orderBy . ' LIMIT :lim OFFSET :off'
        );
        $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();

        return [
            'items'    => $st->fetchAll(),
            'total'    => $total,
            'page'     => $page,
            'pages'    => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
        ];
    }

    /**
     * Сколько всего строк. Нужно там, где важно только «пусто или нет»:
     * витрина так решает, показывать раздел или спрятать. Через all() это
     * тянуло бы из базы все строки целиком, включая тела статей.
     */
    public static function count(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM `' . static::$table . '`')->fetchColumn();
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        $orderBy = self::safeOrderBy($orderBy);
        return self::db()->query('SELECT * FROM `' . static::$table . '` ORDER BY ' . $orderBy)->fetchAll();
    }

    /**
     * Пропускает только «безопасные» ORDER BY: список «колонка [ASC|DESC]» через запятую.
     * Значение задаётся кодом (не пользователем) — при несовпадении это ошибка разработчика.
     */
    private static function safeOrderBy(string $orderBy): string
    {
        $orderBy = trim($orderBy);
        if (!preg_match('/^[a-z_][a-z0-9_]*(\s+(ASC|DESC))?(\s*,\s*[a-z_][a-z0-9_]*(\s+(ASC|DESC))?)*$/i', $orderBy)) {
            throw new \InvalidArgumentException('Недопустимое выражение ORDER BY: ' . $orderBy);
        }
        return $orderBy;
    }

    public static function create(array $data): int
    {
        $data = static::onlyFillable($data);
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);

        $sql = 'INSERT INTO `' . static::$table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $placeholders) . ')';
        $st = self::db()->prepare($sql);
        $st->execute($data);

        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $data = static::onlyFillable($data);
        if ($data === []) {
            return false;
        }
        $set = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($data)));
        $data['id'] = $id;

        $st = self::db()->prepare('UPDATE `' . static::$table . '` SET ' . $set . ' WHERE id = :id');
        return $st->execute($data);
    }

    public static function delete(int $id): bool
    {
        $st = self::db()->prepare('DELETE FROM `' . static::$table . '` WHERE id = ?');
        return $st->execute([$id]);
    }

    protected static function onlyFillable(array $data): array
    {
        if (static::$fillable === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip(static::$fillable));
    }
}
