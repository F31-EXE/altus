<?php
/**
 * Страница одной новости.
 *
 * Тело записи - готовый HTML из редактора админки. Он уже прогнан через
 * App\Core\HtmlSanitizer при сохранении: <script>, on*, javascript:, style=
 * и <iframe> вырезаны, остаётся белый список тегов. Поэтому здесь он
 * выводится как есть, без e(). Это единственное место на сайте, где HTML
 * печатается неэкранированным, и оно намеренно одно.
 *
 * @var array $post
 */
/* Редактор вставляет картинки без width/height, и статья подпрыгивает при
   их загрузке. Дописываем размеры с диска. */
$body = with_img_dims((string) $post['body']);
?>
<article class="article">
  <div class="page-head">
    <div class="shell">
      <nav class="crumbs" aria-label="Хлебные крошки">
        <a href="<?= base_url('/') ?>">Главная</a>
        <svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
        <a href="<?= base_url('/blog') ?>">Блог</a>
        <svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
        <span><?= e($post['title']) ?></span>
      </nav>
      <h1 class="display h1"><?= e($post['title']) ?></h1>
      <p class="post__date u-mt-4">
        <time datetime="<?= e(iso_date($post['created_at'])) ?>"><?= e(ru_date($post['created_at'])) ?></time>
      </p>
    </div>
  </div>

  <div class="shell">
    <?php if (trim((string) $post['preview_image_path']) !== ''): ?>
      <div class="media media--tinted article__hero">
        <img src="<?= e(media_url($post['preview_image_path'])) ?>"<?= img_dims($post['preview_image_path']) ?>
             fetchpriority="high" decoding="async" alt="<?= e($post['title']) ?>">
      </div>
    <?php endif; ?>

    <div class="prose">
      <?php if (trim((string) $post['excerpt']) !== ''): ?>
        <p class="lede"><?= e($post['excerpt']) ?></p>
      <?php endif; ?>
      <?= $body ?>
    </div>

    <div class="article__foot">
      <a class="link-arrow" href="<?= base_url('/blog') ?>">
        Все новости
        <svg class="icon" aria-hidden="true"><use href="#i-arrow-up-right"></use></svg>
      </a>
    </div>

    <aside class="cta-band">
      <div>
        <h2 class="display h3">Нужна вывеска на ваш объект</h2>
        <p>Приедем на замер, покажем визуализацию на вашем фасаде и посчитаем смету.</p>
      </div>
      <a class="btn btn--primary" href="<?= base_url('/#contacts') ?>">
        Обсудить проект
        <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
      </a>
    </aside>

  </div>
</article>
