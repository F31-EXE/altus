<?php /** @var array $reviews */ ?>
<section class="s-section">
    <h2>Отзывы</h2>
    <?php if (!$reviews): ?>
        <p class="s-muted">Пока нет отзывов.</p>
    <?php else: ?>
        <div class="s-reviews">
            <?php foreach ($reviews as $r): ?>
                <?php $ext = strtolower(pathinfo($r['document_path'] ?? '', PATHINFO_EXTENSION)); ?>
                <blockquote class="s-review">
                    <p class="s-review__text"><?= nl2br(e($r['body'])) ?></p>
                    <footer class="s-review__author">— <?= e($r['author']) ?></footer>
                    <?php if (!empty($r['document_path'])): ?>
                        <p class="s-review__doc">
                            <a href="<?= asset($r['document_path']) ?>" target="_blank" rel="noopener">
                                <?= in_array($ext, ['jpg', 'jpeg', 'png'], true) ? 'смотреть письмо' : 'смотреть письмо (' . e(strtoupper($ext)) . ')' ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </blockquote>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
