<?php

namespace App\Core;

/**
 * Одноразовые сообщения между редиректами.
 */
final class Flash
{
    private const KEY = '_flash';

    public static function add(string $type, string $message): void
    {
        $_SESSION[self::KEY][] = ['type' => $type, 'message' => $message];
    }

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    /** @return array<int, array{type:string, message:string}> */
    public static function pull(): array
    {
        $items = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);
        return $items;
    }
}
