<?php /** @var array $services */ ?>
<section class="s-section">
    <h2>Наши услуги</h2>
    <?php if (!$services): ?>
        <p class="s-muted">Пока нет услуг.</p>
    <?php else: ?>
        <div class="s-grid">
            <?php foreach ($services as $s): ?>
                <article class="s-card">
                    <?php if (!empty($s['image_path'])): ?>
                        <img class="s-card__img" src="<?= asset($s['image_path']) ?>" alt="<?= e($s['title']) ?>">
                    <?php endif; ?>
                    <div class="s-card__body">
                        <h3><?= e($s['title']) ?></h3>
                        <p class="s-price"><?= money($s['price']) ?></p>
                        <p><?= nl2br(e($s['description'])) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
