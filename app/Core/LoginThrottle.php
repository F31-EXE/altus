<?php

namespace App\Core;

/**
 * Ограничение попыток входа по IP-адресу.
 * После MAX_FAILS неудач подряд — блокировка на LOCK_MINUTES минут.
 */
final class LoginThrottle
{
    private const MAX_FAILS     = 5;
    private const LOCK_MINUTES  = 15;

    public static function ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return substr($ip, 0, 45);
    }

    /** Сколько секунд осталось до конца блокировки (0 — не заблокирован). */
    public static function lockedForSeconds(): int
    {
        $st = Database::pdo()->prepare('SELECT locked_until FROM login_throttle WHERE ip = ?');
        $st->execute([self::ip()]);
        $until = $st->fetchColumn();

        if (!$until) {
            return 0;
        }
        $left = strtotime((string) $until) - time();
        return $left > 0 ? $left : 0;
    }

    /** Зафиксировать неудачную попытку; при достижении лимита — поставить блокировку. */
    public static function registerFailure(): void
    {
        $pdo = Database::pdo();
        $ip = self::ip();

        $st = $pdo->prepare('SELECT fails FROM login_throttle WHERE ip = ?');
        $st->execute([$ip]);
        $fails = (int) ($st->fetchColumn() ?: 0) + 1;

        if ($fails >= self::MAX_FAILS) {
            $lock = date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60);
            $pdo->prepare(
                'INSERT INTO login_throttle (ip, fails, locked_until) VALUES (?, 0, ?)
                 ON DUPLICATE KEY UPDATE fails = 0, locked_until = VALUES(locked_until)'
            )->execute([$ip, $lock]);
        } else {
            $pdo->prepare(
                'INSERT INTO login_throttle (ip, fails, locked_until) VALUES (?, ?, NULL)
                 ON DUPLICATE KEY UPDATE fails = VALUES(fails), locked_until = NULL'
            )->execute([$ip, $fails]);
        }
    }

    /** Успешный вход — снять счётчик. */
    public static function clear(): void
    {
        Database::pdo()->prepare('DELETE FROM login_throttle WHERE ip = ?')->execute([self::ip()]);
    }
}
