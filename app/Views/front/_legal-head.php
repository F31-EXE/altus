<?php
/**
 * Общая шапка правовых страниц: хлебные крошки, заголовок, дата редакции.
 *
 * @var string $legalTitle      обычный текст: крошки, <title>
 * @var string $legalTitleHtml  необязательно: тот же заголовок с мягкими
 *                              переносами для крупного кегля
 * @var string $legalLead
 */
?>
<section class="page-head">
  <div class="shell">
    <nav class="crumbs" aria-label="Хлебные крошки">
      <a href="<?= base_url('/') ?>">Главная</a>
      <svg class="icon" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
      <span><?= e($legalTitle) ?></span>
    </nav>
    <?php /* Русские слова длиннее колонки телефона: «конфиденциальности»
             на 32px это 452px при колонке 320px. Мягкий перенос задаёт точку
             разрыва по слогам, и слово рвётся с дефисом, а не посередине. */ ?>
    <h1 class="display h1"><?= $legalTitleHtml ?? e($legalTitle) ?></h1>
    <p class="lede u-mt-4 u-narrow"><?= e($legalLead) ?></p>
    <p class="meta u-mt-3">Редакция от <?= legal('updated') ?>.</p>
  </div>
</section>
