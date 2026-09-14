<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\Review;
use App\Models\Service;
use App\Models\Work;

/**
 * Публичная (клиентская) часть — упрощённый демо-вывод данных из админки.
 * Без авторизации. Смонтирована под префиксом /site.
 */
final class SiteController extends Controller
{
    private const ORDER = 'sort_order ASC, id DESC';

    public function home(): void
    {
        $this->viewPublic('site/home', [
            'services' => Service::all(self::ORDER),
            'works'    => Work::all(self::ORDER),
            'reviews'  => Review::all(self::ORDER),
            'posts'    => Post::publishedLatest(6),
        ], 'Демо-сайт');
    }

    public function services(): void
    {
        $this->viewPublic('site/services', [
            'services' => Service::all(self::ORDER),
        ], 'Наши услуги');
    }

    public function works(): void
    {
        $this->viewPublic('site/works', [
            'works' => Work::all(self::ORDER),
        ], 'Наши работы');
    }

    public function reviews(): void
    {
        $this->viewPublic('site/reviews', [
            'reviews' => Review::all(self::ORDER),
        ], 'Отзывы');
    }

    public function blogIndex(): void
    {
        $this->viewPublic('site/blog', [
            'posts' => Post::publishedLatest(100),
        ], 'Блог');
    }

    public function blogShow(string $slug): void
    {
        $post = Post::findPublishedBySlug($slug);

        if (!$post) {
            http_response_code(404);
            $this->viewPublic('site/not-found', [], 'Новость не найдена');
            return;
        }

        $this->viewPublic('site/post', ['post' => $post], $post['title']);
    }
}
