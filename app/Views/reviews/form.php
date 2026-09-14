<?php
/** @var array|null $review */
/** @var string $action */
$isEdit = $review !== null;
$hasDoc = $isEdit && !empty($review['document_path']);
$docExt = $hasDoc ? strtolower(pathinfo($review['document_path'], PATHINFO_EXTENSION)) : '';
?>
<div class="panel panel--form">
    <form method="post" action="<?= e($action) ?>" class="form" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label class="field">
            <span class="field__label">ФИО или компания *</span>
            <input type="text" name="author" class="input <?= hasError('author') ? 'input--error' : '' ?>"
                   value="<?= e(old('author', $review['author'] ?? '')) ?>" required maxlength="200">
            <?php foreach (errors('author') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <label class="field">
            <span class="field__label">Текст отзыва *</span>
            <textarea name="body" rows="6" class="input <?= hasError('body') ? 'input--error' : '' ?>"
                      required maxlength="5000"><?= e(old('body', $review['body'] ?? '')) ?></textarea>
            <?php foreach (errors('body') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <div class="field">
            <span class="field__label">Документ <span class="muted">(оригинал благодарственного письма — необязательно)</span></span>

            <?php if ($hasDoc): ?>
                <div class="current-image">
                    <?php if (in_array($docExt, ['jpg', 'jpeg', 'png'], true)): ?>
                        <img src="<?= asset($review['document_path']) ?>" alt="">
                    <?php else: ?>
                        <a href="<?= asset($review['document_path']) ?>" target="_blank" rel="noopener" class="doc-badge doc-badge--lg">
                            <?= e(strtoupper($docExt ?: 'файл')) ?> — открыть
                        </a>
                    <?php endif; ?>
                </div>
                <label class="check">
                    <input type="checkbox" name="remove_document" value="1"> Удалить текущий документ
                </label>
            <?php endif; ?>

            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,image/*,application/pdf"
                   class="input <?= hasError('document') ? 'input--error' : '' ?>">
            <span class="field__hint">PDF, JPG или PNG, до 8 МБ<?= $isEdit ? '. Выбор файла заменит текущий.' : '' ?></span>
            <?php foreach (errors('document') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </div>

        <label class="field" style="max-width:220px">
            <span class="field__label">Порядок сортировки</span>
            <input type="number" name="sort_order" step="1"
                   class="input <?= hasError('sort_order') ? 'input--error' : '' ?>"
                   value="<?= e(old('sort_order', $review['sort_order'] ?? '0')) ?>">
            <span class="field__hint">Меньше — выше в списке</span>
        </label>

        <div class="form__actions">
            <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить' : 'Создать' ?></button>
            <a href="<?= admin_url('/reviews') ?>" class="btn btn--ghost">Отмена</a>
        </div>
    </form>
</div>
