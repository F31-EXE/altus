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
 * Публичный сайт. Занимает корень: /, /blog, /blog/{slug},
 * плюс /robots.txt и /sitemap.xml для поисковиков.
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

    public function privacy(): void
    {
        $this->viewFront('front/privacy', [
            'nav'         => $this->navForInnerPage(),
            'description' => 'Как сайт «Альтус» собирает, использует и хранит '
                . 'персональные данные посетителей и какие файлы cookie ставит.',
        ], 'Политика конфиденциальности. Альтус');
    }

    public function terms(): void
    {
        $this->viewFront('front/terms', [
            'nav'         => $this->navForInnerPage(),
            'description' => 'Пользовательское соглашение сайта «Альтус» '
                . 'и согласие на обработку персональных данных.',
        ], 'Пользовательское соглашение. Альтус');
    }

    /**
     * robots.txt собирается кодом, а не лежит файлом: адрес карты сайта
     * должен быть абсолютным и браться из base_url, а путь админки - из
     * admin_prefix. Файлом в public/ пришлось бы править его руками
     * при каждой смене домена или префикса.
     *
     * Закрыты админка, приём заявок и демо-витрина /site: последняя
     * повторяет контент сайта, и поисковик счёл бы её дублем.
     */
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        /* Правила - префиксы, поэтому голое «Disallow: /site» закрыло бы
           и сам /sitemap.xml. Каталог закрыт со слэшем, корень отдельно
           через $, который понимают и Яндекс, и Google. */
        echo "User-agent: *\n"
            . 'Disallow: ' . admin_prefix() . "$\n"
            . 'Disallow: ' . admin_prefix() . "/\n"
            . "Disallow: /api/\n"
            . "Disallow: /site$\n"
            . "Disallow: /site/\n"
            . "\n"
            . 'Sitemap: ' . base_url('/sitemap.xml') . "\n";
    }

    /**
     * Карта сайта: главная, правовые страницы и опубликованные записи блога.
     * Черновики сюда не попадают, список блога - только если в нём что-то есть,
     * как и пункт меню. Дата правки записи берётся из updated_at.
     */
    public function sitemap(): void
    {
        $posts = Post::publishedLatest(1000);

        $urls = [['loc' => base_url('/'), 'lastmod' => null]];
        if ($posts) {
            $urls[] = ['loc' => base_url('/blog'), 'lastmod' => $posts[0]['updated_at']];
        }
        foreach ($posts as $p) {
            $urls[] = ['loc' => base_url('/blog/' . $p['slug']), 'lastmod' => $p['updated_at']];
        }
        $urls[] = ['loc' => base_url('/privacy'), 'lastmod' => null];
        $urls[] = ['loc' => base_url('/terms'),   'lastmod' => null];

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            if ($u['lastmod']) {
                echo '<lastmod>' . date('Y-m-d', strtotime($u['lastmod'])) . '</lastmod>';
            }
            echo "</url>\n";
        }
        echo "</urlset>\n";
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
