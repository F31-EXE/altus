<?php

namespace App\Models;

final class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = ['name', 'email', 'password_hash'];

    public static function findByEmail(string $email): ?array
    {
        $st = self::db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = ?';
        $params = [$email];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $st = self::db()->prepare($sql);
        $st->execute($params);
        return (int) $st->fetchColumn() > 0;
    }

    public static function updatePassword(int $id, string $plain): bool
    {
        $st = self::db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        return $st->execute([password_hash($plain, PASSWORD_DEFAULT), $id]);
    }
}
