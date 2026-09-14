<?php
/**
 * Список всех новостей.
 * Раскладка рядами, а не мозаикой, чтобы не повторять блок главной.
 *
 * @var array $posts
 */
?>
<section class="page-head">
  <div class="shell">
    <nav class="crumbs" aria-label="Хлебные крошки">
      <a href="<?= base_url('/') ?>">Главная</a>
      <svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
      <span>Блог</span>
    </nav>
    <h1 class="display h1">Блог</h1>
    <p class="lede u-mt-4">
      Разбираем, из чего складывается цена, чем отличаются материалы
      и что стоит проверить до монтажа.
    </p>
  </div>
</section>

<section class="section section--flush">
  <div class="shell">
    <?php if (!$posts): ?>
      <div class="empty">
        <svg class="icon" aria-hidden="true"><use href="#i-file-description"></use></svg>
        <p>Записей пока нет.</p>
        <p class="meta u-mt-2">
          Новости добавляются из панели администратора, раздел «Блог».
        </p>
      </div>
    <?php else: ?>
      <div class="post-list" data-reveal-group>
        <?php foreach ($posts as $p): ?>
          <a class="post post--row" href="<?= base_url('/blog/' . rawurlencode($p['slug'])) ?>" data-reveal>
            <span class="media media--tinted post__media">
              <img src="<?= e(media_url($p['preview_image_path'])) ?>"<?= img_dims($p['preview_image_path']) ?>
                   loading="lazy" decoding="async" alt="<?= e($p['title']) ?>">
            </span>
            <span>
              <time class="post__date" datetime="<?= e(iso_date($p['created_at'])) ?>"><?= e(ru_date($p['created_at'])) ?></time>
              <h2 class="post__title display u-mt-1"><?= e($p['title']) ?></h2>
              <?php if (trim((string) $p['excerpt']) !== ''): ?>
                <p class="post__excerpt u-mt-2"><?= e($p['excerpt']) ?></p>
              <?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="cta-band u-mt-4">
      <div>
        <h2 class="h3 display">Нужна вывеска?</h2>
        <p>Посчитаем смету и подберём материалы. Выезд специалиста бесплатный.</p>
      </div>
      <a class="btn btn--primary" href="<?= base_url('/#contacts') ?>">
        Обсудить проект
        <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
      </a>
    </div>
  </div>
</section>
