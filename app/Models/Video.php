<?php

namespace App\Models;

final class Video extends Model
{
    protected static string $table = 'videos';
    protected static array $fillable = ['video_path', 'title', 'subtitle', 'sort_order'];

    public static function paginateOrdered(int $page = 1, int $perPage = 24): array
    {
        return self::paginate($page, $perPage, 'sort_order ASC, id DESC');
    }
}
