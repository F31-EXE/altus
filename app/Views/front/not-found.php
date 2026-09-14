<?php
/** Страница 404. Не тупик: отсюда есть куда пойти. */
?>
<section class="page-head">
  <div class="shell">
    <nav class="crumbs" aria-label="Хлебные крошки">
      <a href="<?= base_url('/') ?>">Главная</a>
      <svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
      <span>Страница не найдена</span>
    </nav>
    <h1 class="display h1">Такой страницы нет</h1>
    <p class="lede u-mt-4 u-narrow">
      Возможно, запись удалили или ссылка набрана с опечаткой.
    </p>
    <div class="hero__actions u-mt-4">
      <a class="btn btn--primary" href="<?= base_url('/') ?>">
        На главную
        <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
      </a>
      <a class="btn btn--ghost" href="<?= base_url('/blog') ?>">Все новости</a>
    </div>
  </div>
</section>
