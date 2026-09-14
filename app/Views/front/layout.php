<?php
/**
 * Оболочка публичного сайта.
 *
 * Шапка, подвал и спрайт иконок лежат здесь в одном экземпляре. В статической
 * версии они были продублированы в пяти HTML-файлах, и это стояло в README
 * как известное ограничение; с переходом на PHP ограничение снято.
 *
 * @var string $__template путь к шаблону страницы
 * @var string $title
 * @var array<string,string> $nav ссылка => подпись
 */
$nav = $nav ?? ['/#contacts' => 'Контакты'];

/** Ссылка на раздел: на главной это якорь, на внутренней странице - путь. */
$navHref = static fn(string $href): string => str_starts_with($href, '#')
    ? $href
    : base_url($href);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description ?? 'ООО «Альтус»: объёмные буквы, световые короба, крышные установки, оформление витрин, монтаж и высотные работы, ростовые фигуры, брендирование авто. Екатеринбург.') ?>">
<meta name="theme-color" content="#0B0D10">

<meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description ?? 'Полный цикл наружной рекламы: дизайн, согласование, производство, монтаж и обслуживание.') ?>">
<meta property="og:image" content="<?= e($ogImage ?? asset('assets/img/hero.jpg')) ?>">

<link rel="preload" href="<?= asset('assets/fonts/unbounded-cyrillic.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= asset('assets/fonts/onest-cyrillic.woff2') ?>" as="font" type="font/woff2" crossorigin>

<link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/components.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/sections.css') ?>">

<link rel="icon" href="<?= asset('assets/img/favicon-64.png') ?>" sizes="64x64" type="image/png">
<link rel="apple-touch-icon" href="<?= asset('assets/img/favicon-180.png') ?>">
</head>
<body>

<?php require __DIR__ . '/_sprite.php'; ?>

<a class="skip-link" href="#main">Перейти к содержанию</a>

<header class="header" data-header>
  <div class="shell header__bar">

    <a class="logo" href="<?= base_url('/') ?>" aria-label="Альтус, на главную">
      <img class="logo__mark" src="<?= asset('assets/img/logo-mark.png') ?>" alt=""<?= img_dims('assets/img/logo-mark.png') ?> data-logo-mark>
      <span>АЛЬТУС<span class="logo__sub">рекламно-производственная группа</span></span>
    </a>

    <button class="nav-toggle" type="button" data-nav-toggle
            aria-expanded="false" aria-controls="nav" aria-label="Открыть меню">
      <svg class="icon" aria-hidden="true"><use href="#i-menu-2"></use></svg>
    </button>

    <nav class="nav" id="nav" data-nav aria-label="Основная навигация">
      <?php foreach ($nav as $href => $label): ?>
        <a class="nav__link" href="<?= e($navHref($href)) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="btn btn--primary btn--sm" href="<?= e($navHref(array_key_last($nav))) ?>">Обсудить проект</a>
    </nav>

    <a class="btn btn--primary btn--sm header__cta" href="<?= e($navHref(array_key_last($nav))) ?>">Обсудить проект</a>
  </div>
</header>

<main id="main">
<?php require $__template; ?>
</main>

<footer class="footer">
  <div class="shell">
    <div class="footer__grid">

      <div>
        <a class="logo" href="<?= base_url('/') ?>" aria-label="Альтус, на главную">
          <img class="logo__mark" src="<?= asset('assets/img/logo-mark.png') ?>" alt=""<?= img_dims('assets/img/logo-mark.png') ?> data-logo-mark>
          <span>АЛЬТУС<span class="logo__sub">рекламно-производственная группа</span></span>
        </a>
        <p class="meta u-mt-4 u-narrow">
          Производство и монтаж наружной рекламы и вывесок в Екатеринбурге.
        </p>
      </div>

      <div>
        <p class="footer__title">Разделы</p>
        <ul class="footer__list">
          <?php foreach ($nav as $href => $label): ?>
            <?php if ($label === 'Контакты') { continue; } ?>
            <li><a href="<?= e($navHref($href)) ?>"><?= e($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <p class="footer__title">Связь</p>
        <ul class="footer__list">
          <li><a href="tel:+79120454444">+7 912 045-44-44</a></li>
          <li><a href="mailto:scharapov.wadym@yandex.ru">scharapov.wadym@yandex.ru</a></li>
          <li><a href="<?= e($navHref(array_key_last($nav))) ?>">Екатеринбург, улица Данилы Зверева, 23</a></li>
          <li><a href="<?= e($navHref(array_key_last($nav))) ?>">Оставить заявку</a></li>
        </ul>
      </div>

    </div>

    <div class="footer__bottom">
      <span>ООО «Альтус», 2018 по <?= date('Y') ?>. Производство и монтаж вывесок, наружная реклама.</span>
      <span class="footer__legal">
        <a href="<?= base_url('/privacy') ?>">Политика конфиденциальности</a>
        <a href="<?= base_url('/terms') ?>">Пользовательское соглашение</a>
      </span>
    </div>
  </div>
</footer>

<!-- Уведомление о cookie. Полоса внизу, а не окно поверх страницы:
     сайт читается и до нажатия, кнопка «Понятно» ничего не разблокирует.
     Разметка приходит с сервера скрытой, показывает её ui.js, если человек
     ещё не закрывал уведомление. Так оно не мигает у тех, кто уже закрыл. -->
<div class="cookie" data-cookie hidden>
  <div class="shell cookie__in">
    <p class="cookie__text">
      Сайт использует только технические cookie: они нужны, чтобы работала
      форма заявки. Аналитики и рекламных счётчиков здесь нет.
      <a href="<?= base_url('/privacy') ?>">Подробнее</a>
    </p>
    <button class="btn btn--primary btn--sm cookie__ok" type="button" data-cookie-ok>
      Понятно
    </button>
  </div>
</div>

<script src="<?= asset('assets/js/ui.js') ?>" defer></script>
<script src="<?= asset('assets/js/lightbox.js') ?>" defer></script>
<script src="<?= asset('assets/js/form.js') ?>" defer></script>
</body>
</html>
