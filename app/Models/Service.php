<?php

namespace App\Models;

final class Service extends Model
{
    protected static string $table = 'services';
    protected static array $fillable = ['title', 'price', 'description', 'image_path', 'sort_order'];

    /** Список для витрины/списка: по порядку сортировки, затем по дате. */
    public static function paginateOrdered(int $page = 1, int $perPage = 20): array
    {
        return self::paginate($page, $perPage, 'sort_order ASC, id DESC');
    }
}
