<?php

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="' . self::KEY . '" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function check(): void
    {
        $sent = $_POST[self::KEY] ?? '';
        if (!self::verify(is_string($sent) ? $sent : null)) {
            http_response_code(403);
            exit('CSRF-токен недействителен. Обновите страницу и попробуйте снова.');
        }
    }

    /** Сравнение произвольного токена (например, из заголовка X-CSRF-Token). */
    public static function verify(?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals(self::token(), $token);
    }
}
