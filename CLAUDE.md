# CLAUDE.md — контекст проекта для разработчика (и Claude Code)

Админ-панель лендинга. Хранит контент (услуги, отзывы, блог, работы, видео, «о нас»),
даёт CRUD в закрытой части и упрощённый публичный вывод под `/site`.

> **Фронтенд подключён.** Задача «подключить фронтенд» выполнена, подробности
> в `README.md`. Коротко, что изменилось в этом документе по факту:
>
> * публичный сайт занял корень: `/`, `/blog`, `/blog/{slug}`
>   (`App\Controllers\PublicController`, шаблоны в `app/Views/front/`);
> * **вся админка уехала под префикс** `/admin`. Он задаётся одной строкой
>   `app.admin_prefix` в конфиге; маршруты, меню, редиректы и `action` форм
>   берут его из `admin_path()` / `admin_url()` / `Controller::redirectAdmin()`,
>   руками путь нигде не прописан. Все адреса ниже по тексту читайте
>   с этим префиксом: `/services` это `/admin/services`;
> * JSON API из §6 **не делался**, и это осознанно. Там он предлагался
>   в расчёте на отдельное приложение на своём домене (Next, Vite). Здесь сайт
>   и админка — один проект на одном домене, поэтому страницы собираются
>   на сервере из моделей напрямую: ни CORS, ни пустых секций до ответа,
>   ни потери контента для поисковика, и страница работает без JavaScript;
> * демо-витрина `/site` не тронута, осталась справочником;
> * добавлены `Model::count()`, `Post::publishedCount()`, `database/seed.php`
>   со стартовым контентом сайта и `public/router.php` для `php -S`;
> * приём заявок с формы сделан: таблица `leads`, `App\Models\Lead`,
>   `App\Controllers\LeadController` (публичный `POST /api/lead`) и
>   раздел `/admin/leads` только на чтение, отметку и удаление. Заявка
>   и уходит письмом, и пишется в базу: `mail()` с виртуального хостинга
>   доходит не всегда, а терять обращения нельзя. Адрес получателя —
>   `mail.to` в конфиге;
> * добавлены правовые страницы `/privacy` и `/terms` и уведомление
>   о cookie. Реквизиты оператора — `legal` в конфиге; **ИНН и ОГРН там
>   намеренно пусты**, их не было ни в договоре, ни на старом сайте,
>   и пока они не заполнены, на страницах видна предупреждающая врезка.

---

## 1. Стек и требования

| | |
|---|---|
| Язык | PHP **8.1+** (разрабатывалось на 8.3, использованы `never`, `match`, enum-подобные конструкции) |
| БД | MySQL 8.x / MariaDB 10.4+ (InnoDB, utf8mb4) |
| Веб-сервер | Apache 2.4 c `mod_rewrite` + `mod_headers` (или nginx — см. §8) |
| Зависимости | **нет Composer**, нет npm. Всё на ванильном PHP. TinyMCE грузится с CDN jsDelivr в админке. |
| Расширения PHP | `pdo_mysql`, `fileinfo`, `mbstring`, `dom` (для санитайзера блога) |

Своя микро-архитектура: front controller + роутер + базовая модель (PDO) + PHP-шаблоны. Фреймворка нет.

---

## 2. Запуск за 5 шагов

```bash
git clone -b admin-panel https://github.com/MiStre13/Altus.git AdminPanel
cd AdminPanel
cp config/config.example.php config/config.php        # заполнить доступы к БД
mysql -u root -e "CREATE DATABASE adminpanel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root adminpanel < database/schema.sql
php database/create_admin.php "Имя" admin@example.com пароль-минимум-10-символов
```

- **Docroot = `public/`** (строго). Всё остальное (`app/`, `config/`, `database/`) вне вебрута.
- `config/config.php` в git **не хранится** — создаётся из `config.example.php`.
- Локально удобно через Laragon: положить в `C:\laragon\www\AdminPanel`, хост `adminpanel.test` поднимется сам.
- Открыть: админка — `/` (редирект на `/login`), демо витрины — `/site`.

### config/config.php — что менять

```php
'app' => [
    'base_url' => 'https://example.com',  // без слэша в конце; используется для абсолютных URL картинок
    'debug'    => false,                  // true только локально
    'timezone' => 'Europe/Moscow',
],
'db' => [ 'host', 'port', 'name', 'user', 'pass', 'charset' => 'utf8mb4' ],
'uploads' => [
    'dir'            => __DIR__.'/../public/uploads',
    'max_size'       => 8*1024*1024,       // картинки/документы
    'video_max_size' => 512*1024*1024,     // видео
    'image_ext' => ['jpg','jpeg','png','webp','gif'],
    'doc_ext'   => ['pdf','jpg','jpeg','png'],
    'video_ext' => ['mp4','webm','ogv','mov'],
],
```

---

## 3. Структура

```
public/
  index.php            front controller
  .htaccess            pretty-URL rewrite + security-заголовки
  assets/{css,js}      стили/скрипты админки и демо-витрины
  uploads/<section>/   загруженные файлы (в git не идут, кроме .gitkeep/.htaccess)
app/
  bootstrap.php        константы, автозагрузка, сессия, security-заголовки, таймаут сессии
  routes.php           ВСЕ маршруты (public + admin)
  Core/                Router, Database, Controller, Auth, Csrf, Flash, Request,
                       Validator, Upload, HtmlSanitizer, LoginThrottle, helpers.php
  Controllers/         Auth, Dashboard, User, Service, Review, Blog, Work, Video, About, Site
  Models/              Model (база), User, Service, Review, Post, Work, Video, About
  Views/               layout.php (админка), site/layout.php (витрина), <section>/{index,form}.php
config/                config.php (локально) + config.example.php
database/              schema.sql, create_admin.php (CLI)
```

---

## 4. Модель данных (то, что нужно фронтенду)

Все таблицы: `id` (PK, auto), `created_at`, `updated_at` (TIMESTAMP). Порядок вывода в списках —
`sort_order ASC, id DESC` (меньше `sort_order` — выше), у блога — `created_at DESC, id DESC`.

### `services` — «Наши услуги»
| поле | тип | null | примечание |
|---|---|---|---|
| `title` | VARCHAR(200) | нет | |
| `price` | DECIMAL(12,2) | нет | число, форматируй на фронте |
| `description` | TEXT | нет | plain-text (выводить с `nl2br`/`white-space: pre-line`) |
| `image_path` | VARCHAR(255) | нет | относительный путь, см. §5 |
| `sort_order` | INT | нет | default 0 |

### `reviews` — «Отзывы»
| `author` | VARCHAR(200) | нет | ФИО или компания |
| `body` | TEXT | нет | plain-text |
| `document_path` | VARCHAR(255) | **да** | скан письма (pdf/jpg/png) или `null` |
| `sort_order` | INT | нет | |

### `posts` — «Блог»
| `title` | VARCHAR(200) | нет | |
| `slug` | VARCHAR(200) | нет | **уникальный**, для URL страницы новости: `/blog/<slug>` |
| `excerpt` | VARCHAR(500) | **да** | краткое описание для карточки |
| `preview_image_path` | VARCHAR(255) | нет | картинка превью |
| `body` | LONGTEXT | нет | **готовый HTML**, уже прогнан через `HtmlSanitizer` (белый список тегов). Рендерить как есть (`v-html` / `dangerouslySetInnerHTML`). |
| `is_published` | TINYINT(1) | нет | **фронт показывает только `= 1`** |

### `works` — «Наши работы»
| `image_path` | VARCHAR(255) | нет | |
| `description` | TEXT | нет | plain-text |
| `sort_order` | INT | нет | |

### `videos` — «Видео»
| `video_path` | VARCHAR(255) | нет | mp4/webm/ogv/mov, отдаётся с поддержкой Range (перемотка работает) |
| `title` | VARCHAR(200) | **да** | опционально |
| `subtitle` | VARCHAR(255) | **да** | опционально |
| `sort_order` | INT | нет | |

### `about` — «О нас»
| `image_path` | VARCHAR(255) | нет | |
| `title` | VARCHAR(200) | **да** | опционально |
| `description` | TEXT | **да** | plain-text, опционально |
| `sort_order` | INT | нет | |

### `users`, `login_throttle` — служебные, фронтенду не нужны.

---

## 5. Пути к файлам → URL

В БД лежит **относительный** путь: `uploads/<section>/<random>_<ts>.<ext>`
(например `uploads/services/9f3a1c2b4d5e6f70_1789012345.jpg`).

Полный URL = `<base_url> + '/' + <path>` → `https://example.com/uploads/services/9f3a1c2b….jpg`.
Файлы отдаёт веб-сервер напрямую из `public/uploads/`. Исполнение PHP там отключено, листинг каталогов запрещён.

---

## 6. Как подключить фронтенд

Сейчас клиентская часть — это **server-side демо** под `/site` (HTML, лежит в `app/Views/site/`).
Оно не предназначено для продакшн-верстки, это референс вывода данных.

Маршруты витрины (GET, без авторизации):

| URL | отдаёт |
|---|---|
| `/site` | всё вместе (услуги, работы, блог×6, отзывы) |
| `/site/services` `/site/works` `/site/reviews` `/site/blog` | списки |
| `/site/blog/{slug}` | одна новость (404 если черновик/нет) |

### Рекомендуемый путь: добавить JSON API

Модели уже дают всё нужное. Нужен тонкий слой. Пример (положить в `app/Controllers/ApiController.php`):

```php
<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\{Service, Review, Post, Work, Video, About};

final class ApiController extends Controller
{
    private const ORDER = 'sort_order ASC, id DESC';

    private function out(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');          // сузить до домена фронта на проде
        header('Content-Security-Policy: default-src \'none\''); // перебить админский CSP из bootstrap
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function url(?string $p): ?string
    {
        return $p ? base_url($p) : null;
    }

    public function services(): void
    {
        $this->out(array_map(fn($r) => [
            'id'          => (int) $r['id'],
            'title'       => $r['title'],
            'price'       => (float) $r['price'],
            'description' => $r['description'],
            'image'       => $this->url($r['image_path']),
            'sort'        => (int) $r['sort_order'],
        ], Service::all(self::ORDER)));
    }

    public function reviews(): void
    {
        $this->out(array_map(fn($r) => [
            'id'       => (int) $r['id'],
            'author'   => $r['author'],
            'body'     => $r['body'],
            'document' => $this->url($r['document_path']),
            'sort'     => (int) $r['sort_order'],
        ], Review::all(self::ORDER)));
    }

    public function works(): void
    {
        $this->out(array_map(fn($r) => [
            'id'          => (int) $r['id'],
            'image'       => $this->url($r['image_path']),
            'description' => $r['description'],
            'sort'        => (int) $r['sort_order'],
        ], Work::all(self::ORDER)));
    }

    public function videos(): void
    {
        $this->out(array_map(fn($r) => [
            'id'       => (int) $r['id'],
            'src'      => $this->url($r['video_path']),
            'title'    => $r['title'],
            'subtitle' => $r['subtitle'],
            'sort'     => (int) $r['sort_order'],
        ], Video::all(self::ORDER)));
    }

    public function about(): void
    {
        $this->out(array_map(fn($r) => [
            'id'          => (int) $r['id'],
            'image'       => $this->url($r['image_path']),
            'title'       => $r['title'],
            'description' => $r['description'],
            'sort'        => (int) $r['sort_order'],
        ], About::all(self::ORDER)));
    }

    public function blog(): void
    {
        $this->out(array_map(fn($r) => [
            'id'      => (int) $r['id'],
            'title'   => $r['title'],
            'slug'    => $r['slug'],
            'excerpt' => $r['excerpt'],
            'preview' => $this->url($r['preview_image_path']),
            'date'    => $r['created_at'],
        ], Post::publishedLatest(100)));
    }

    public function post(string $slug): void
    {
        $p = Post::findPublishedBySlug($slug);
        if (!$p) { $this->out(['error' => 'not_found']); }
        $this->out([
            'id'      => (int) $p['id'],
            'title'   => $p['title'],
            'slug'    => $p['slug'],
            'excerpt' => $p['excerpt'],
            'preview' => $this->url($p['preview_image_path']),
            'date'    => $p['created_at'],
            'html'    => $p['body'],   // уже санитизированный HTML
        ]);
    }
}
```

Маршруты — дописать в `app/routes.php`:

```php
use App\Controllers\ApiController;

$router->get('/api/services',     [ApiController::class, 'services']);
$router->get('/api/reviews',      [ApiController::class, 'reviews']);
$router->get('/api/works',        [ApiController::class, 'works']);
$router->get('/api/videos',       [ApiController::class, 'videos']);
$router->get('/api/about',        [ApiController::class, 'about']);
$router->get('/api/blog',         [ApiController::class, 'blog']);
$router->get('/api/blog/{slug}',  [ApiController::class, 'post']);   // до других /api/blog/*, если появятся
```

Готово: `GET https://example.com/api/services` и т.д. отдают JSON.

### Важные нюансы для фронта

- **CORS.** Если фронт на другом origin (Next/Vite dev на `:3000`, отдельный домен) — API должен слать
  `Access-Control-Allow-Origin` (в примере `*`, на проде сузить). Для не-GET добавить preflight-обработку.
- **CSP.** `bootstrap.php` ставит CSP для админки на *каждый* ответ. В примере `out()` перебивает его на
  `default-src 'none'` (для JSON неважно, но чтобы не мешал). Витрина `/site` имеет строгий CSP
  (`default-src 'self'`) — если рендеришь её как есть, помни про это.
- **Блог = HTML.** `posts.body` — уже безопасный HTML (теги из белого списка: p, h1-6, ul/ol/li,
  strong/em, a, img, table, blockquote, pre/code, figure). Вставлять напрямую. `<script>`, `on*`,
  `javascript:`, `style=`, `<iframe>` уже вырезаны на сохранении.
- **Картинки/видео** — абсолютные URL через `base_url()`. Меняется одним `app.base_url` в конфиге.
- **Пусто = поле `null`** для опциональных (`excerpt`, `document_path`, `videos.title/subtitle`,
  `about.title/description`).
- **Только опубликованное:** блог фильтруется по `is_published=1` (в `publishedLatest` /
  `findPublishedBySlug` уже учтено). Остальные разделы показывают всё.

---

## 7. Конвенции кода (если правишь бэкенд)

- **Маршрут:** одна строка в `app/routes.php`: `$router->get('/path/{id}', [Ctrl::class, 'method'])`.
  `{param}` → `([^/]+)`, приходит в метод строкой. Более специфичные маршруты — выше общих.
- **Контроллер:** наследует `App\Core\Controller`. `Auth::require()` в конструкторе = раздел закрыт.
  Рендер: `$this->view('section/tpl', [...], 'Заголовок')` (админ-лейаут),
  `$this->viewPublic(...)` (витрина), `$this->json([...])` / `$this->jsonError(msg, code)`.
- **Модель:** наследует `App\Core\Model`, задаёт `$table` и `$fillable`. Даёт
  `find/all/paginate/create/update/delete`. Значения — только через bind (PDO, без эмуляции).
  `ORDER BY` валидируется `safeOrderBy()` — передавать «колонка ASC/DESC», не пользовательский ввод.
- **Загрузка файла:** `(new Upload(config('uploads')))->store($_FILES['x'], 'section', 'image'|'doc'|'video')`
  → вернёт относительный путь либо кинет `RuntimeException` с текстом для пользователя.
  Проверяет расширение + реальный MIME (`finfo`), имя файла генерит сервер. `->delete($path)` — удалить.
- **Формы:** POST + `<?= csrf_field() ?>`; в контроллере `Csrf::check()` первым делом.
  Валидация — `App\Core\Validator` (`required|string|email|numeric|min|max|minlen|maxlen|in:a,b`).
- **helpers:** `e()` (экранирование), `base_url()`, `asset()`, `config('a.b')`, `old()`, `slugify()`.

---

## 8. Безопасность (уже сделано — не ломать при доработке)

- CSRF на всех POST; сессии `HttpOnly`+`SameSite=Lax`+`Secure`(по HTTPS); таймаут 2 ч / 12 ч.
- Логин: блок IP на 15 мин после 5 неудач (`login_throttle`). Пароли — bcrypt, минимум 10 символов.
- Тело блога чистится `App\Core\HtmlSanitizer` при сохранении.
- Заголовки (`public/.htaccess` + `bootstrap.php`): `X-Frame-Options`, `X-Content-Type-Options: nosniff`,
  `Referrer-Policy`, `Content-Security-Policy`, `Cross-Origin-Opener-Policy`; `X-Powered-By` скрыт.
- В `public/uploads/` PHP выключен, листинг запрещён.
- **Прод-чеклист:** `config.php` → `debug=false`; docroot = `public/`; HTTPS; сменить дефолтный
  пароль админа; для API — сузить `Access-Control-Allow-Origin`.

### nginx (если не Apache)

`.htaccess` не работает. Нужно: docroot `public/`, фронт-контроллер и заголовки:

```nginx
root /var/www/AdminPanel/public;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.3-fpm.sock;
                    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
location ^~ /uploads/ { location ~ \.php$ { return 403; } }   # не исполнять PHP в загрузках
add_header X-Frame-Options SAMEORIGIN always;
add_header X-Content-Type-Options nosniff always;
add_header Referrer-Policy strict-origin-when-cross-origin always;
```

---

## 9. Git

Ветка: **`admin-panel`** (`https://github.com/MiStre13/Altus.git`). В репозитории только она.
`config/config.php` и загруженные медиа в git не попадают (см. `.gitignore`).
Атрибуция коммитов от Claude: `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`.
