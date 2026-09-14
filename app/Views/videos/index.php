<?php /** @var array $videos  результат paginate */ ?>
<div class="toolbar">
    <a href="<?= admin_url('/videos/create') ?>" class="btn btn--primary">+ Новое видео</a>
</div>

<?php if (!$videos['items']): ?>
    <div class="panel"><p class="muted">Видео пока нет.</p></div>
<?php else: ?>
    <div class="works-grid">
        <?php foreach ($videos['items'] as $v): ?>
            <div class="work-card">
                <div class="work-card__video">
                    <video src="<?= asset($v['video_path']) ?>" controls preload="metadata"></video>
                </div>
                <div class="work-card__body">
                    <?php if (!empty($v['title'])): ?>
                        <p class="work-card__title"><?= e($v['title']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($v['subtitle'])): ?>
                        <p class="work-card__desc muted"><?= e($v['subtitle']) ?></p>
                    <?php endif; ?>
                    <?php if (empty($v['title']) && empty($v['subtitle'])): ?>
                        <p class="work-card__desc muted">Без заголовка</p>
                    <?php endif; ?>
                    <div class="work-card__meta muted">Порядок: <?= (int) $v['sort_order'] ?></div>
                </div>
                <div class="work-card__actions">
                    <a href="<?= admin_url('/videos/' . $v['id'] . '/edit') ?>" class="btn btn--ghost btn--sm">Изменить</a>
                    <form method="post" action="<?= admin_url('/videos/' . $v['id'] . '/delete') ?>" class="inline"
                          onsubmit="return confirm('Удалить это видео?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php $data = $videos; $baseUrl = admin_url('/videos'); require APP_PATH . '/Views/partials/pagination.php'; ?>
