<?php /** @var array $services  результат paginate */ ?>
<div class="toolbar">
    <a href="<?= admin_url('/services/create') ?>" class="btn btn--primary">+ Новая услуга</a>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <th style="width:80px">Фото</th>
            <th>Заголовок</th>
            <th style="width:140px">Цена</th>
            <th style="width:90px">Порядок</th>
            <th style="width:170px"></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$services['items']): ?>
            <tr><td colspan="5" class="table__empty">Услуг пока нет</td></tr>
        <?php endif; ?>
        <?php foreach ($services['items'] as $s): ?>
            <tr>
                <td>
                    <?php if (!empty($s['image_path'])): ?>
                        <img src="<?= asset($s['image_path']) ?>" alt="" class="thumb">
                    <?php else: ?>
                        <span class="thumb thumb--empty"></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="cell-title"><?= e($s['title']) ?></div>
                    <div class="cell-desc muted"><?= e(mb_strimwidth($s['description'], 0, 120, '…')) ?></div>
                </td>
                <td><?= money($s['price']) ?></td>
                <td><?= (int) $s['sort_order'] ?></td>
                <td class="row-actions">
                    <a href="<?= admin_url('/services/' . $s['id'] . '/edit') ?>" class="btn btn--ghost btn--sm">Изменить</a>
                    <form method="post" action="<?= admin_url('/services/' . $s['id'] . '/delete') ?>" class="inline"
                          onsubmit="return confirm('Удалить услугу «<?= e($s['title']) ?>»?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $data = $services; $baseUrl = admin_url('/services'); require APP_PATH . '/Views/partials/pagination.php'; ?>
