<?php

use App\Core\Database;

/**
 * Инициализация приложения: константы, автозагрузка, конфиг, безопасность, БД, сессия.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');

if (!is_file(CONFIG_PATH . '/config.php')) {
    exit('Нет config/config.php. Скопируйте config/config.example.php.');
}

// --- PSR-4-подобный автозагрузчик для namespace App\ ---
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_PATH . '/Core/helpers.php';
require APP_PATH . '/Core/front_helpers.php';

// --- Настройки окружения ---
date_default_timezone_set((string) config('app.timezone', 'UTC'));

$debug = (bool) config('app.debug');

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');

    // Без утечки трейсов наружу — общая страница 500.
    set_exception_handler(static function (\Throwable $e): void {
        error_log('Uncaught: ' . $e);
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo 'Внутренняя ошибка сервера.';
    });
}

// --- Заголовки безопасности ---
// X-Frame-Options / X-Content-Type-Options / Referrer-Policy ставит public/.htaccess
// (покрывает и статику из /uploads). Здесь — то, что зависит от приложения.
if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('Cross-Origin-Opener-Policy: same-origin');
    // Базовый CSP для админки (TinyMCE тянется с jsDelivr, есть встроенные <script>).
    // Публичные страницы переопределяют его на строгий в Controller::viewPublic().
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "img-src 'self' data:; media-src 'self'; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        . "font-src 'self' https://cdn.jsdelivr.net; "
        . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        . "connect-src 'self' https://cdn.jsdelivr.net; "
        . "object-src 'none'; base-uri 'self'; frame-ancestors 'self'"
    );
}

// --- Сессия ---
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
    session_start();
}

// --- Таймаут сессии: 2 ч бездействия, максимум 12 ч жизни ---
if (isset($_SESSION['_user_id'])) {
    $now = time();
    $idleMax = 2 * 3600;
    $absMax  = 12 * 3600;

    $expired = (isset($_SESSION['_last_seen']) && $now - $_SESSION['_last_seen'] > $idleMax)
        || (isset($_SESSION['_login_at']) && $now - $_SESSION['_login_at'] > $absMax);

    if ($expired) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['_last_seen'] = $now;
        $_SESSION['_login_at'] ??= $now;
    }
}

// --- БД ---
Database::init(config('db'));
