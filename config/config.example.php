<?php
/**
 * Пример конфигурации. Скопируй в config/config.php и подставь свои значения.
 * config/config.php в git не коммитится.
 */
return [
    'app' => [
        'name'     => 'Альтус. Панель управления',
        'base_url' => 'http://altus.test', // без слэша в конце
        // Корень сайта занимает публичная витрина, поэтому админка живёт
        // под префиксом. Меняется здесь и больше нигде: маршруты, меню,
        // редиректы и action форм берут его из admin_path().
        'admin_prefix' => '/admin',
        'debug'    => false, // true только на локальной разработке
        'timezone' => 'Asia/Yekaterinburg',
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'altus',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'uploads' => [
        // абсолютный путь к каталогу public/uploads
        'dir'            => __DIR__ . '/../public/uploads',
        // публичный префикс URL
        'url'            => '/uploads',
        'max_size'       => 8 * 1024 * 1024,         // 8 МБ — картинки, документы
        'video_max_size' => 512 * 1024 * 1024,       // 512 МБ — видео
        'image_ext'      => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'doc_ext'        => ['pdf', 'jpg', 'jpeg', 'png'],
        'video_ext'      => ['mp4', 'webm', 'ogv', 'mov'],
    ],
];
