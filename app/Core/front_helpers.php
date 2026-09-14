<?php

/**
 * Хелперы публичного сайта.
 *
 * Здесь живёт только то, что нужно витрине: превращение строк из админки
 * в то, что ждёт вёрстка. Админку эти функции не трогают.
 */

/** Абсолютный URL до файла из админки. null остаётся null. */
function media_url(?string $relative): ?string
{
    $relative = trim((string) $relative);
    return $relative !== '' ? base_url($relative) : null;
}

/**
 * Размеры картинки для атрибутов width/height.
 *
 * Без них браузер не знает пропорцию до загрузки и страница дёргается.
 * В базе размеров нет, поэтому читаем их с диска: getimagesize() берёт
 * только заголовок файла, не изображение целиком. Результат кешируется
 * на время запроса, одна картинка не читается дважды.
 *
 * @return array{0:int,1:int}|null
 */
function img_size(?string $relative): ?array
{
    static $cache = [];

    $relative = ltrim((string) $relative, '/');
    if ($relative === '') {
        return null;
    }
    if (array_key_exists($relative, $cache)) {
        return $cache[$relative];
    }

    $file = PUBLIC_PATH . '/' . $relative;
    $size = null;
    if (is_file($file)) {
        $info = @getimagesize($file);
        if ($info && $info[0] > 0 && $info[1] > 0) {
            $size = [(int) $info[0], (int) $info[1]];
        }
    }

    return $cache[$relative] = $size;
}

/** Готовая пара атрибутов width/height, либо пустая строка. */
function img_dims(?string $relative): string
{
    $size = img_size($relative);
    return $size ? ' width="' . $size[0] . '" height="' . $size[1] . '"' : '';
}

/**
 * Делит текст на первый абзац и остаток.
 *
 * В базе у услуги и у работы одно текстовое поле, а вёрстке нужно два
 * куска: короткий на виду и длинный под «Показать полностью» (у работы -
 * подпись и описание в лайтбоксе). Договорённость простая и видимая
 * редактору: первый абзац - короткий текст, всё после пустой строки -
 * длинный. Если пустой строки нет, длинной части просто не будет,
 * и блок это переживает.
 *
 * @return array{0:string,1:string}
 */
function split_lead(?string $text): array
{
    $text = trim((string) $text);
    if ($text === '') {
        return ['', ''];
    }

    $parts = preg_split('/\R\s*\R/u', $text, 2);

    return [trim($parts[0]), isset($parts[1]) ? trim($parts[1]) : ''];
}

/** Первая строка текста. Для подписи к работе. */
function first_line(?string $text): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    $parts = preg_split('/\R/u', $text, 2);
    return trim($parts[0]);
}

/** Всё, кроме первой строки. */
function rest_lines(?string $text): string
{
    $text = trim((string) $text);
    $parts = preg_split('/\R/u', $text, 2);
    return isset($parts[1]) ? trim($parts[1]) : '';
}

/** Дата по-русски: «21 августа 2026». */
function ru_date(?string $timestamp): string
{
    static $months = [
        1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
    ];

    $ts = strtotime((string) $timestamp);
    if ($ts === false) {
        return '';
    }

    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

/** Дата для атрибута datetime. */
function iso_date(?string $timestamp): string
{
    $ts = strtotime((string) $timestamp);
    return $ts === false ? '' : date('Y-m-d', $ts);
}

/** Расширение файла в нижнем регистре. */
function file_ext(?string $path): string
{
    return strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
}

/**
 * Можно ли показать файл в лайтбоксе картинкой.
 * PDF нельзя: его открывает сам браузер, отдельной вкладкой.
 */
function is_image_file(?string $path): bool
{
    return in_array(file_ext($path), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
}

/** Цена услуги. Ноль в базе значит «не указана», а не «бесплатно». */
function service_price(mixed $price): string
{
    return (float) $price > 0 ? money($price) : 'Цена по запросу';
}

/**
 * Реквизит для правовой страницы.
 *
 * Пустое поле не молчит: вместо него печатается заметная пометка. Пустой ИНН
 * в политике конфиденциальности - это дыра, которую надо увидеть, а не
 * пропущенная строка, которую никто не заметит.
 */
function legal(string $key): string
{
    $value = trim((string) config('legal.' . $key, ''));

    return $value !== ''
        ? e($value)
        : '<mark class="legal-todo">впишите ' . e($key) . ' в config/config.php</mark>';
}

/** Заполнены ли регистрационные реквизиты. */
function legal_complete(): bool
{
    return trim((string) config('legal.inn', '')) !== ''
        && trim((string) config('legal.ogrn', '')) !== '';
}

/**
 * Дописывает width и height картинкам внутри статьи.
 *
 * Редактор админки вставляет голый <img src="...">, без размеров. Браузер
 * тогда не знает пропорцию до загрузки, и текст статьи подпрыгивает, когда
 * картинка приходит. Размеры берём с диска по тому же пути, что в атрибуте.
 *
 * Работаем регулярным выражением по одному тегу <img>, не разбирая документ:
 * тело статьи уже прошло HtmlSanitizer, содержимое атрибутов доверенное,
 * а полный разбор и сборка DOM переписали бы остальную разметку редактора.
 * Тег, у которого размеры уже стоят или файл не найден, остаётся как был.
 */
function with_img_dims(string $html): string
{
    $base = rtrim((string) config('app.base_url'), '/');

    return (string) preg_replace_callback('/<img\b[^>]*>/i', static function (array $m) use ($base): string {
        $tag = $m[0];

        if (preg_match('/\bwidth\s*=/i', $tag) || preg_match('/\bheight\s*=/i', $tag)) {
            return $tag;
        }
        if (!preg_match('/\bsrc\s*=\s*"([^"]+)"/i', $tag, $src)) {
            return $tag;
        }

        /* Редактор кладёт абсолютный URL, поэтому отрезаем свой домен и
           получаем путь внутри public. Чужие адреса пропускаем. */
        $url = html_entity_decode($src[1], ENT_QUOTES, 'UTF-8');
        $path = str_starts_with($url, $base) ? substr($url, strlen($base)) : $url;
        if (preg_match('#^[a-z]+://#i', $path)) {
            return $tag;
        }

        $size = img_size(ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/'));
        if (!$size) {
            return $tag;
        }

        return rtrim($tag, '>/ ') . ' width="' . $size[0] . '" height="' . $size[1] . '">';
    }, $html);
}
