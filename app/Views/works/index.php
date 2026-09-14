<?php /** @var array $works  результат paginate */ ?>
<div class="toolbar">
    <a href="<?= admin_url('/works/create') ?>" class="btn btn--primary">+ Новая работа</a>
</div>

<?php if (!$works['items']): ?>
    <div class="panel"><p class="muted">Работ пока нет.</p></div>
<?php else: ?>
    <div class="works-grid">
        <?php foreach ($works['items'] as $w): ?>
            <div class="work-card">
                <div class="work-card__img">
                    <img src="<?= asset($w['image_path']) ?>" alt="">
                </div>
                <div class="work-card__body">
                    <p class="work-card__desc"><?= e(mb_strimwidth($w['description'], 0, 180, '…')) ?></p>
                    <div class="work-card__meta muted">Порядок: <?= (int) $w['sort_order'] ?></div>
                </div>
                <div class="work-card__actions">
                    <a href="<?= admin_url('/works/' . $w['id'] . '/edit') ?>" class="btn btn--ghost btn--sm">Изменить</a>
                    <form method="post" action="<?= admin_url('/works/' . $w['id'] . '/delete') ?>" class="inline"
                          onsubmit="return confirm('Удалить эту работу?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php $data = $works; $baseUrl = admin_url('/works'); require APP_PATH . '/Views/partials/pagination.php'; ?>
