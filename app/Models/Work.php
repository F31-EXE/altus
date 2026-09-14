<?php

namespace App\Models;

final class Work extends Model
{
    protected static string $table = 'works';
    protected static array $fillable = ['image_path', 'description', 'sort_order'];

    public static function paginateOrdered(int $page = 1, int $perPage = 24): array
    {
        return self::paginate($page, $perPage, 'sort_order ASC, id DESC');
    }
}
