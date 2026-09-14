<?php

namespace App\Core;

use App\Models\User;

final class Auth
{
    private const KEY = '_user_id';

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION[self::KEY] = (int) $user['id'];
        $_SESSION['_login_at'] = time();
        $_SESSION['_last_seen'] = time();
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::KEY]);
    }

    public static function id(): ?int
    {
        return $_SESSION[self::KEY] ?? null;
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        static $cached = null;
        if (!self::check()) {
            return null;
        }
        if ($cached === null) {
            $cached = User::find(self::id());
        }
        return $cached ?: null;
    }

    /** Требует авторизацию, иначе редирект на /login. */
    public static function require(): void
    {
        if (!self::check()) {
            Flash::error('Войдите в систему');
            header('Location: ' . admin_url('/login'));
            exit;
        }
    }
}
