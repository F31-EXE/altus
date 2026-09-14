<?php

namespace App\Core;

/**
 * Доступ к данным запроса.
 */
final class Request
{
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /** Значение из POST с обрезкой пробелов (для строк). */
    public static function post(string $key, mixed $default = null): mixed
    {
        $v = $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    /** Сырое значение из POST без обрезки (для HTML из редактора). */
    public static function raw(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function file(string $key): ?array
    {
        $f = $_FILES[$key] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $f;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }
}
