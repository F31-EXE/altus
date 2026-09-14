<?php

namespace App\Models;

final class Lead extends Model
{
    protected static string $table = 'leads';
    protected static array $fillable = [
        'name', 'phone', 'contact', 'message', 'ip', 'mailed', 'is_read',
    ];

    public static function paginateLatest(int $page = 1, int $perPage = 30): array
    {
        return self::paginate($page, $perPage, 'created_at DESC, id DESC');
    }

    /** Сколько непрочитанных. Показывается счётчиком в меню админки. */
    public static function unreadCount(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM leads WHERE is_read = 0')->fetchColumn();
    }

    /**
     * Сколько заявок пришло с этого адреса за последние минуты.
     * Нужно против простого спама формы: см. LeadController.
     */
    public static function recentFromIp(string $ip, int $minutes): int
    {
        $st = self::db()->prepare(
            'SELECT COUNT(*) FROM leads WHERE ip = ? AND created_at > (NOW() - INTERVAL ? MINUTE)'
        );
        $st->execute([$ip, $minutes]);
        return (int) $st->fetchColumn();
    }
}
