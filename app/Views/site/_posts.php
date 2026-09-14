<?php
/** @var array $posts */
/** @var bool $showMore  показывать ссылку «все новости» */
$showMore = $showMore ?? false;
?>
<section class="s-section">
    <h2>Блог</h2>
    <?php if (!$posts): ?>
        <p class="s-muted">Пока нет новостей.</p>
    <?php else: ?>
        <div class="s-grid s-grid--3">
            <?php foreach ($posts as $p): ?>
                <article class="s-post-card">
                    <a href="<?= base_url('/site/blog/' . rawurlencode($p['slug'])) ?>">
                        <?php if (!empty($p['preview_image_path'])): ?>
                            <img class="s-post-card__img" src="<?= asset($p['preview_image_path']) ?>" alt="">
                        <?php endif; ?>
                        <h3><?= e($p['title']) ?></h3>
                    </a>
                    <?php if (!empty($p['excerpt'])): ?>
                        <p><?= e($p['excerpt']) ?></p>
                    <?php endif; ?>
                    <p class="s-date"><?= e(mb_substr((string) $p['created_at'], 0, 10)) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if ($showMore): ?>
            <p><a href="<?= base_url('/site/blog') ?>">Все новости →</a></p>
        <?php endif; ?>
    <?php endif; ?>
</section>
