<?php /** @var array $reviews  результат paginate */ ?>
<div class="toolbar">
    <a href="<?= admin_url('/reviews/create') ?>" class="btn btn--primary">+ Новый отзыв</a>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <th>ФИО / компания</th>
            <th>Текст отзыва</th>
            <th style="width:130px">Документ</th>
            <th style="width:90px">Порядок</th>
            <th style="width:170px"></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$reviews['items']): ?>
            <tr><td colspan="5" class="table__empty">Отзывов пока нет</td></tr>
        <?php endif; ?>
        <?php foreach ($reviews['items'] as $r): ?>
            <?php $ext = strtolower(pathinfo($r['document_path'] ?? '', PATHINFO_EXTENSION)); ?>
            <tr>
                <td><div class="cell-title"><?= e($r['author']) ?></div></td>
                <td><div class="cell-desc muted"><?= e(mb_strimwidth($r['body'], 0, 160, '…')) ?></div></td>
                <td>
                    <?php if (!empty($r['document_path'])): ?>
                        <a href="<?= asset($r['document_path']) ?>" target="_blank" rel="noopener" class="doc-link">
                            <?php if (in_array($ext, ['jpg', 'jpeg', 'png'], true)): ?>
                                <img src="<?= asset($r['document_path']) ?>" alt="" class="thumb">
                            <?php else: ?>
                                <span class="doc-badge"><?= e(strtoupper($ext ?: 'файл')) ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <span class="muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= (int) $r['sort_order'] ?></td>
                <td class="row-actions">
                    <a href="<?= admin_url('/reviews/' . $r['id'] . '/edit') ?>" class="btn btn--ghost btn--sm">Изменить</a>
                    <form method="post" action="<?= admin_url('/reviews/' . $r['id'] . '/delete') ?>" class="inline"
                          onsubmit="return confirm('Удалить отзыв от «<?= e($r['author']) ?>»?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $data = $reviews; $baseUrl = admin_url('/reviews'); require APP_PATH . '/Views/partials/pagination.php'; ?>
