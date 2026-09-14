<?php

namespace App\Models;

final class Post extends Model
{
    protected static string $table = 'posts';
    protected static array $fillable = [
        'title', 'slug', 'excerpt', 'preview_image_path', 'body', 'is_published',
    ];

    public static function paginateLatest(int $page = 1, int $perPage = 20): array
    {
        return self::paginate($page, $perPage, 'created_at DESC, id DESC');
    }

    /** Опубликованные новости, свежие сверху (для клиентской части). */
    public static function publishedLatest(int $limit = 100): array
    {
        $st = self::db()->prepare(
            'SELECT * FROM posts WHERE is_published = 1 ORDER BY created_at DESC, id DESC LIMIT :lim'
        );
        $st->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Сколько опубликованных записей. Витрина прячет раздел, если ноль. */
    public static function publishedCount(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM posts WHERE is_published = 1')->fetchColumn();
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        $st = self::db()->prepare('SELECT * FROM posts WHERE slug = ? AND is_published = 1 LIMIT 1');
        $st->execute([$slug]);
        return $st->fetch() ?: null;
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE slug = ?';
        $params = [$slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $st = self::db()->prepare($sql);
        $st->execute($params);
        return (int) $st->fetchColumn() > 0;
    }

    /**
     * Используется ли файл картинки в другой новости (в теле или как превью).
     * $path — относительный путь вида "uploads/blog/xxx.png".
     */
    public static function imageReferencedElsewhere(string $path, int $exceptId): bool
    {
        $st = self::db()->prepare(
            'SELECT COUNT(*) FROM posts
             WHERE id <> ?
               AND (preview_image_path = ? OR body LIKE ?)'
        );
        $st->execute([$exceptId, $path, '%' . $path . '%']);
        return (int) $st->fetchColumn() > 0;
    }

    /** Подбирает уникальный slug: base, base-2, base-3, ... */
    public static function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $base = slugify($base);
        $slug = $base;
        $i = 2;
        while (self::slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
