<?php
/** @var array|null $item */
/** @var string $action */
$isEdit = $item !== null;
?>
<div class="panel panel--form">
    <form method="post" action="<?= e($action) ?>" class="form" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="field">
            <span class="field__label">Фотография <?= $isEdit ? '<span class="muted">(выберите файл, чтобы заменить)</span>' : '*' ?></span>
            <?php if ($isEdit && !empty($item['image_path'])): ?>
                <div class="current-image"><img src="<?= asset($item['image_path']) ?>" alt=""></div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/*"
                   class="input <?= hasError('image') ? 'input--error' : '' ?>" <?= $isEdit ? '' : 'required' ?>>
            <span class="field__hint">JPG, PNG, WEBP или GIF, до 8 МБ</span>
            <?php foreach (errors('image') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </div>

        <label class="field">
            <span class="field__label">Заголовок <span class="muted">(необязательно)</span></span>
            <input type="text" name="title" class="input <?= hasError('title') ? 'input--error' : '' ?>"
                   value="<?= e(old('title', $item['title'] ?? '')) ?>" maxlength="200">
            <?php foreach (errors('title') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <label class="field">
            <span class="field__label">Описание <span class="muted">(необязательно)</span></span>
            <textarea name="description" rows="4" class="input <?= hasError('description') ? 'input--error' : '' ?>"
                      maxlength="5000"><?= e(old('description', $item['description'] ?? '')) ?></textarea>
            <?php foreach (errors('description') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <label class="field" style="max-width:220px">
            <span class="field__label">Порядок сортировки</span>
            <input type="number" name="sort_order" step="1"
                   class="input <?= hasError('sort_order') ? 'input--error' : '' ?>"
                   value="<?= e(old('sort_order', $item['sort_order'] ?? '0')) ?>">
            <span class="field__hint">Меньше — выше в списке</span>
        </label>

        <div class="form__actions">
            <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить' : 'Создать' ?></button>
            <a href="<?= admin_url('/about') ?>" class="btn btn--ghost">Отмена</a>
        </div>
    </form>
</div>
