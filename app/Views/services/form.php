<?php
/** @var array|null $service */
/** @var string $action */
$isEdit = $service !== null;
?>
<div class="panel panel--form">
    <form method="post" action="<?= e($action) ?>" class="form" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label class="field">
            <span class="field__label">Заголовок *</span>
            <input type="text" name="title" class="input <?= hasError('title') ? 'input--error' : '' ?>"
                   value="<?= e(old('title', $service['title'] ?? '')) ?>" required maxlength="200">
            <?php foreach (errors('title') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <div class="field-row">
            <label class="field">
                <span class="field__label">Цена, ₽ *</span>
                <input type="number" name="price" step="0.01" min="0"
                       class="input <?= hasError('price') ? 'input--error' : '' ?>"
                       value="<?= e(old('price', $service['price'] ?? '')) ?>" required>
                <?php foreach (errors('price') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
            </label>

            <label class="field">
                <span class="field__label">Порядок сортировки</span>
                <input type="number" name="sort_order" step="1"
                       class="input <?= hasError('sort_order') ? 'input--error' : '' ?>"
                       value="<?= e(old('sort_order', $service['sort_order'] ?? '0')) ?>">
                <span class="field__hint">Меньше — выше в списке</span>
            </label>
        </div>

        <label class="field">
            <span class="field__label">Описание *</span>
            <textarea name="description" rows="5" class="input <?= hasError('description') ? 'input--error' : '' ?>"
                      required maxlength="5000"><?= e(old('description', $service['description'] ?? '')) ?></textarea>
            <?php foreach (errors('description') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <div class="field">
            <span class="field__label">Фото <?= $isEdit ? '<span class="muted">(выберите файл, чтобы заменить)</span>' : '*' ?></span>
            <?php if ($isEdit && !empty($service['image_path'])): ?>
                <div class="current-image">
                    <img src="<?= asset($service['image_path']) ?>" alt="">
                </div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/*"
                   class="input <?= hasError('image') ? 'input--error' : '' ?>" <?= $isEdit ? '' : 'required' ?>>
            <span class="field__hint">JPG, PNG, WEBP или GIF, до 8 МБ</span>
            <?php foreach (errors('image') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </div>

        <div class="form__actions">
            <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить' : 'Создать' ?></button>
            <a href="<?= admin_url('/services') ?>" class="btn btn--ghost">Отмена</a>
        </div>
    </form>
</div>
