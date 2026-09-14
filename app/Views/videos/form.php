<?php
/** @var array|null $video */
/** @var string $action */
$isEdit = $video !== null;
?>
<div class="panel panel--form">
    <form method="post" action="<?= e($action) ?>" class="form" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="field">
            <span class="field__label">Видеофайл <?= $isEdit ? '<span class="muted">(выберите файл, чтобы заменить)</span>' : '*' ?></span>
            <?php if ($isEdit && !empty($video['video_path'])): ?>
                <div class="current-video">
                    <video src="<?= asset($video['video_path']) ?>" controls preload="metadata"></video>
                </div>
            <?php endif; ?>
            <input type="file" name="video" accept="video/mp4,video/webm,video/ogg,video/quicktime,.mp4,.webm,.ogv,.mov"
                   class="input <?= hasError('video') ? 'input--error' : '' ?>" <?= $isEdit ? '' : 'required' ?>>
            <span class="field__hint">MP4, WEBM, OGV или MOV, до 512 МБ</span>
            <?php foreach (errors('video') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </div>

        <label class="field">
            <span class="field__label">Заголовок <span class="muted">(необязательно)</span></span>
            <input type="text" name="title" class="input <?= hasError('title') ? 'input--error' : '' ?>"
                   value="<?= e(old('title', $video['title'] ?? '')) ?>" maxlength="200">
            <?php foreach (errors('title') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <label class="field">
            <span class="field__label">Подзаголовок <span class="muted">(необязательно)</span></span>
            <input type="text" name="subtitle" class="input <?= hasError('subtitle') ? 'input--error' : '' ?>"
                   value="<?= e(old('subtitle', $video['subtitle'] ?? '')) ?>" maxlength="255">
            <?php foreach (errors('subtitle') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <label class="field" style="max-width:220px">
            <span class="field__label">Порядок сортировки</span>
            <input type="number" name="sort_order" step="1"
                   class="input <?= hasError('sort_order') ? 'input--error' : '' ?>"
                   value="<?= e(old('sort_order', $video['sort_order'] ?? '0')) ?>">
            <span class="field__hint">Меньше — выше в списке</span>
        </label>

        <div class="form__actions">
            <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить' : 'Создать' ?></button>
            <a href="<?= admin_url('/videos') ?>" class="btn btn--ghost">Отмена</a>
        </div>
    </form>
</div>
