<?php /** @var array $works */ ?>
<section class="s-section">
    <h2>Наши работы</h2>
    <?php if (!$works): ?>
        <p class="s-muted">Пока нет работ.</p>
    <?php else: ?>
        <div class="s-grid s-grid--3">
            <?php foreach ($works as $w): ?>
                <figure class="s-work">
                    <img src="<?= asset($w['image_path']) ?>" alt="">
                    <figcaption><?= nl2br(e($w['description'])) ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
