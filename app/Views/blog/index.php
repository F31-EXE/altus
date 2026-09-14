<?php /** @var array $posts  результат paginate */ ?>
<div class="toolbar">
    <a href="<?= admin_url('/blog/create') ?>" class="btn btn--primary">+ Новая новость</a>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <th style="width:80px">Превью</th>
            <th>Заголовок</th>
            <th style="width:200px">Slug</th>
            <th style="width:120px">Статус</th>
            <th style="width:130px">Создана</th>
            <th style="width:170px"></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$posts['items']): ?>
            <tr><td colspan="6" class="table__empty">Новостей пока нет</td></tr>
        <?php endif; ?>
        <?php foreach ($posts['items'] as $p): ?>
            <tr>
                <td>
                    <?php if (!empty($p['preview_image_path'])): ?>
                        <img src="<?= asset($p['preview_image_path']) ?>" alt="" class="thumb">
                    <?php else: ?>
                        <span class="thumb thumb--empty"></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="cell-title"><?= e($p['title']) ?></div>
                    <?php if (!empty($p['excerpt'])): ?>
                        <div class="cell-desc muted"><?= e(mb_strimwidth($p['excerpt'], 0, 120, '…')) ?></div>
                    <?php endif; ?>
                </td>
                <td><code class="slug"><?= e($p['slug']) ?></code></td>
                <td>
                    <?php if ((int) $p['is_published'] === 1): ?>
                        <span class="badge badge--green">опубликовано</span>
                    <?php else: ?>
                        <span class="badge badge--gray">черновик</span>
                    <?php endif; ?>
                </td>
                <td class="muted"><?= e(mb_substr((string) $p['created_at'], 0, 10)) ?></td>
                <td class="row-actions">
                    <a href="<?= admin_url('/blog/' . $p['id'] . '/edit') ?>" class="btn btn--ghost btn--sm">Изменить</a>
                    <form method="post" action="<?= admin_url('/blog/' . $p['id'] . '/delete') ?>" class="inline"
                          onsubmit="return confirm('Удалить новость «<?= e($p['title']) ?>»?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $data = $posts; $baseUrl = admin_url('/blog'); require APP_PATH . '/Views/partials/pagination.php'; ?>
