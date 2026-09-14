<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\About;
use App\Models\Post;
use App\Models\Review;
use App\Models\Service;
use App\Models\Video;
use App\Models\Work;

/**
 * Публичный сайт. Занимает корень: /, /blog, /blog/{slug}.
 *
 * Данные берутся напрямую из моделей и подставляются в шаблоны на сервере.
 * JSON-слоя между бэкендом и фронтендом нет намеренно: фронтенд тут не
 * отдельное приложение на своём домене, а те же самые страницы этого же
 * сайта. Server-side отдаёт готовый HTML, поэтому нет ни CORS, ни пустых
 * секций до ответа, ни потери контента для поисковика, и страница живёт
 * без JavaScript. Скрипты на сайте только улучшают готовую страницу.
 *
 * Раздел без записей в базе не показывается вовсе, вместе со своим пунктом
 * меню. Так свежепоставленная админка не даёт посетителю пустых заголовков,
 * а заполнение раздела само возвращает его на сайт.
 */
final class PublicController extends Controller
{
    private const ORDER = 'sort_order ASC, id DESC';

    /** Сколько новостей показывать блоком на главной. */
    private const POSTS_ON_HOME = 3;

    public function home(): void
    {
        $services = Service::all(self::ORDER);
        $works    = Work::all(self::ORDER);
        $reviews  = Review::all(self::ORDER);
        $videos   = Video::all(self::ORDER);
        $about    = About::all(self::ORDER);
        $posts    = Post::publishedLatest(self::POSTS_ON_HOME);

        $this->viewFront('front/home', [
            'services' => $services,
            'works'    => $works,
            'reviews'  => $reviews,
            'videos'   => $videos,
            'about'    => $about,
            'posts'    => $posts,
            'nav'      => $this->nav($services, $works, $reviews, $videos, $about, $posts),
        ], 'Альтус. Наружная реклама и световые вывески в Екатеринбурге');
    }

    public function blogIndex(): void
    {
        $this->viewFront('front/blog', [
            'posts'       => Post::publishedLatest(100),
            'nav'         => $this->navForInnerPage(),
            'description' => 'Разбираем, из чего складывается цена вывески, '
                . 'чем отличаются материалы и что стоит проверить до монтажа.',
        ], 'Блог. Альтус');
    }

    public function blogShow(string $slug): void
    {
        $post = Post::findPublishedBySlug($slug);

        if (!$post) {
            http_response_code(404);
            $this->viewFront('front/not-found', [
                'nav' => $this->navForInnerPage(),
            ], 'Страница не найдена. Альтус');
            return;
        }

        /* Описание и картинка для выдачи и для ссылки в мессенджере берутся
           из самой записи, а не из общего шаблона. */
        $this->viewFront('front/post', [
            'post'        => $post,
            'nav'         => $this->navForInnerPage(),
            'description' => trim((string) $post['excerpt']) !== ''
                ? $post['excerpt']
                : $post['title'],
            'ogType'      => 'article',
            'ogImage'     => media_url($post['preview_image_path']),
        ], $post['title'] . '. Альтус');
    }

    /**
     * Меню главной: пункт показывается, только если его раздел не пуст.
     * «Ход работы» и «Контакты» свёрстаны в шаблоне и есть всегда.
     */
    private function nav(
        array $services,
        array $works,
        array $reviews,
        array $videos,
        array $about,
        array $posts
    ): array {
        $items = [];
        if ($services) { $items['#services'] = 'Услуги'; }
        $items['#process'] = 'Ход работы';
        if ($about)    { $items['#about']    = 'О нас'; }
        if ($works)    { $items['#works']    = 'Работы'; }
        if ($videos)   { $items['#video']    = 'Видео'; }
        if ($reviews)  { $items['#reviews']  = 'Отзывы'; }
        if ($posts)    { $items['#blog']     = 'Блог'; }
        $items['#contacts'] = 'Контакты';

        return $items;
    }

    /**
     * На внутренних страницах якоря главной не работают, поэтому пункты
     * ведут на главную. Список разделов там же считается заново.
     */
    private function navForInnerPage(): array
    {
        $items = [];
        if (Service::count()) { $items['/#services'] = 'Услуги'; }
        $items['/#process'] = 'Ход работы';
        if (About::count())   { $items['/#about']   = 'О нас'; }
        if (Work::count())    { $items['/#works']   = 'Работы'; }
        if (Video::count())   { $items['/#video']   = 'Видео'; }
        if (Review::count())  { $items['/#reviews'] = 'Отзывы'; }
        if (Post::publishedCount()) { $items['/blog'] = 'Блог'; }
        $items['/#contacts'] = 'Контакты';

        return $items;
    }
}
