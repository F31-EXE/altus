<?php /** @var array $items  результат paginate */ ?>
<div class="toolbar">
    <a href="<?= admin_url('/about/create') ?>" class="btn btn--primary">+ Новый блок</a>
</div>

<?php if (!$items['items']): ?>
    <div class="panel"><p class="muted">Блоков пока нет.</p></div>
<?php else: ?>
    <div class="works-grid">
        <?php foreach ($items['items'] as $it): ?>
            <div class="work-card">
                <div class="work-card__img">
                    <img src="<?= asset($it['image_path']) ?>" alt="">
                </div>
                <div class="work-card__body">
                    <?php if (!empty($it['title'])): ?>
                        <p class="work-card__title"><?= e($it['title']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($it['description'])): ?>
                        <p class="work-card__desc muted"><?= e(mb_strimwidth($it['description'], 0, 180, '…')) ?></p>
                    <?php endif; ?>
                    <?php if (empty($it['title']) && empty($it['description'])): ?>
                        <p class="work-card__desc muted">Без текста</p>
                    <?php endif; ?>
                    <div class="work-card__meta muted">Порядок: <?= (int) $it['sort_order'] ?></div>
                </div>
                <div class="work-card__actions">
                    <a href="<?= admin_url('/about/' . $it['id'] . '/edit') ?>" class="btn btn--ghost btn--sm">Изменить</a>
                    <form method="post" action="<?= admin_url('/about/' . $it['id'] . '/delete') ?>" class="inline"
                          onsubmit="return confirm('Удалить этот блок?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php $data = $items; $baseUrl = admin_url('/about'); require APP_PATH . '/Views/partials/pagination.php'; ?>
