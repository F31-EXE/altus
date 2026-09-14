<?php /** @var array $post */ ?>
<article class="s-article">
    <p class="s-back"><a href="<?= base_url('/site/blog') ?>">← Все новости</a></p>

    <h1><?= e($post['title']) ?></h1>
    <p class="s-date"><?= e(mb_substr((string) $post['created_at'], 0, 10)) ?></p>

    <?php if (!empty($post['preview_image_path'])): ?>
        <img class="s-article__cover" src="<?= asset($post['preview_image_path']) ?>" alt="">
    <?php endif; ?>

    <div class="s-article__body">
        <?= $post['body'] // HTML из редактора, автор — администратор ?>
    </div>
</article>
