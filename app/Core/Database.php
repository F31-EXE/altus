<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Тонкая обёртка над PDO (singleton).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function init(array $cfg): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['name'],
            $cfg['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('DB connection failed: ' . $e->getMessage());
            exit(config('app.debug') ? 'Ошибка подключения к БД: ' . $e->getMessage() : 'Сервис временно недоступен.');
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Database::init() не вызван');
        }
        return self::$pdo;
    }
}
