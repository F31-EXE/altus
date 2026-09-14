<?php

use App\Core\Csrf;

/**
 * Глобальные функции-хелперы. Подключаются в bootstrap.
 */

/** Значение из конфига по «точечному» пути: config('db.host'). */
function config(string $path, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require CONFIG_PATH . '/config.php';
    }
    $segments = explode('.', $path);
    $value = $cfg;
    foreach ($segments as $seg) {
        if (!is_array($value) || !array_key_exists($seg, $value)) {
            return $default;
        }
        $value = $value[$seg];
    }
    return $value;
}

/** Абсолютный URL внутри приложения. */
function base_url(string $path = '/'): string
{
    return rtrim((string) config('app.base_url'), '/') . '/' . ltrim($path, '/');
}

/**
 * Префикс закрытой части. Корень сайта занимает публичная витрина,
 * поэтому админка живёт под ним. Менять здесь и больше нигде.
 */
function admin_prefix(): string
{
    return rtrim((string) config('app.admin_prefix', '/admin'), '/');
}

/**
 * Путь внутри админки: admin_path('/services') === '/admin/services'.
 * Корень админки - это сам префикс, без хвостового слэша: роутер сравнивает
 * пути уже без него, и '/admin/' не совпало бы ни с одним маршрутом.
 */
function admin_path(string $path = '/'): string
{
    $path = trim($path, '/');
    return $path === '' ? admin_prefix() : admin_prefix() . '/' . $path;
}

/** Абсолютный URL внутри админки. */
function admin_url(string $path = '/'): string
{
    return base_url(admin_path($path));
}

/** URL до файла в public (в т.ч. загруженного). */
function asset(string $relative): string
{
    $rel = ltrim($relative, '/');

    /* К адресу приклеивается время правки файла. Без этого после выкладки
       посетитель, у которого стили уже в кеше, получает новую разметку
       со старым CSS: заголовки браузер не шлёт, явного Cache-Control
       на статику нет, и он вправе держать файл свежим по эвристике -
       десятую часть возраста файла, то есть часами. С меткой адрес меняется
       вместе с файлом, и кеш промахивается ровно тогда, когда нужно. */
    $stamp = @filemtime(PUBLIC_PATH . '/' . $rel);

    return base_url($rel) . ($stamp ? '?v=' . $stamp : '');
}

/** Экранирование для вывода в HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Старое значение поля формы после ошибки валидации. */
function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

/** Ошибки валидации для поля. @return string[] */
function errors(string $key): array
{
    return $_SESSION['_errors'][$key] ?? [];
}

/** Есть ли ошибка по полю. */
function hasError(string $key): bool
{
    return !empty($_SESSION['_errors'][$key]);
}

/** Очистить сохранённый ввод/ошибки (после успешного показа формы). */
function clear_old(): void
{
    unset($_SESSION['_old'], $_SESSION['_errors']);
}

function csrf_field(): string
{
    return Csrf::field();
}

/** Активен ли пункт меню админки. Принимает путь без префикса: '/services'. */
function nav_active(string $prefix): string
{
    $path = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
    return str_starts_with($path, admin_path($prefix)) ? 'active' : '';
}

/** Форматирование цены. */
function money(mixed $value): string
{
    return number_format((float) $value, 2, ',', ' ') . ' ₽';
}

/** Транслитерация + очистка строки в URL-slug. */
function slugify(string $text): string
{
    static $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, $map);
    $text = preg_replace('~[^a-z0-9]+~', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'post';
}
