<?php

namespace App\Models;

final class Review extends Model
{
    protected static string $table = 'reviews';
    protected static array $fillable = ['author', 'body', 'document_path', 'sort_order'];

    public static function paginateOrdered(int $page = 1, int $perPage = 20): array
    {
        return self::paginate($page, $perPage, 'sort_order ASC, id DESC');
    }
}
