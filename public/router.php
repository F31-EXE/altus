<?php
/**
 * Роутер для встроенного сервера PHP. Только для локального просмотра:
 *
 *     php -S 127.0.0.1:8000 -t public public/router.php
 *
 * На боевом сервере он не нужен и не используется: там ту же работу делает
 * public/.htaccess у Apache или try_files у nginx. Смысл один - существующий
 * файл отдать как есть, всё остальное отправить на index.php.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;   // пусть сервер отдаст файл сам
}

require __DIR__ . '/index.php';
