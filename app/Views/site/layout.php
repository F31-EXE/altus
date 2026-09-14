<?php
/** @var string $__template */
/** @var string $title */
$here = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$nav = [
    '/site'          => 'Главная',
    '/site/services' => 'Услуги',
    '/site/works'    => 'Работы',
    '/site/reviews'  => 'Отзывы',
    '/site/blog'     => 'Блог',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — демо</title>
    <link rel="stylesheet" href="<?= asset('assets/admin/css/site.css') ?>">
</head>
<body>
<header class="s-header">
    <div class="s-wrap s-header__in">
        <a href="<?= base_url('/site') ?>" class="s-logo">Демо-сайт</a>
        <nav class="s-nav">
            <?php foreach ($nav as $path => $label): ?>
                <a href="<?= base_url($path) ?>"
                   class="<?= ($here === $path || ($path === '/site/blog' && str_starts_with($here, '/site/blog'))) ? 'is-active' : '' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<main class="s-wrap s-main">
    <?php require $__template; ?>
</main>

<footer class="s-footer">
    <div class="s-wrap">
        Демо-вывод данных из админ-панели. <a href="<?= base_url('/') ?>">В админку →</a>
    </div>
</footer>
</body>
</html>
