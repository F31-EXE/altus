<?php
use App\Core\Csrf;
/** @var array|null $post */
/** @var string $action */
$isEdit = $post !== null;
$published = $isEdit ? ((int) $post['is_published'] === 1) : true;
if (($old = old('is_published', '__none__')) !== '__none__') {
    $published = (string) $old === '1';
}
?>
<div class="panel panel--form panel--wide">
    <form method="post" action="<?= e($action) ?>" class="form" enctype="multipart/form-data" id="post-form">
        <?= csrf_field() ?>

        <label class="field">
            <span class="field__label">Заголовок *</span>
            <input type="text" name="title" id="post-title"
                   class="input <?= hasError('title') ? 'input--error' : '' ?>"
                   value="<?= e(old('title', $post['title'] ?? '')) ?>" required maxlength="200">
            <?php foreach (errors('title') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <label class="field">
            <span class="field__label">Slug (адрес страницы)</span>
            <input type="text" name="slug" id="post-slug"
                   class="input"
                   value="<?= e(old('slug', $post['slug'] ?? '')) ?>"
                   maxlength="200" placeholder="сгенерируется из заголовка"
                   data-autoslug="<?= $isEdit ? '0' : '1' ?>">
            <span class="field__hint">Латиница, цифры и дефис. Будущий адрес новости: <code>/blog/<span id="slug-preview"><?= e($post['slug'] ?? '…') ?></span></code></span>
        </label>

        <label class="field">
            <span class="field__label">Краткое описание <span class="muted">(для карточки-превью, необязательно)</span></span>
            <textarea name="excerpt" rows="2" class="input <?= hasError('excerpt') ? 'input--error' : '' ?>"
                      maxlength="500"><?= e(old('excerpt', $post['excerpt'] ?? '')) ?></textarea>
            <?php foreach (errors('excerpt') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <div class="field">
            <span class="field__label">Картинка превью <?= $isEdit ? '<span class="muted">(выберите файл, чтобы заменить)</span>' : '*' ?></span>
            <?php if ($isEdit && !empty($post['preview_image_path'])): ?>
                <div class="current-image"><img src="<?= asset($post['preview_image_path']) ?>" alt=""></div>
            <?php endif; ?>
            <input type="file" name="preview_image" accept="image/*"
                   class="input <?= hasError('preview_image') ? 'input--error' : '' ?>" <?= $isEdit ? '' : 'required' ?>>
            <span class="field__hint">JPG, PNG, WEBP или GIF, до 8 МБ</span>
            <?php foreach (errors('preview_image') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </div>

        <div class="field">
            <span class="field__label">Текст новости *</span>
            <textarea name="body" id="post-body"><?= e(old('body', $post['body'] ?? '')) ?></textarea>
            <?php foreach (errors('body') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </div>

        <label class="check">
            <input type="checkbox" name="is_published" value="1" <?= $published ? 'checked' : '' ?>>
            Опубликовано
        </label>

        <div class="form__actions">
            <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить' : 'Создать' ?></button>
            <a href="<?= admin_url('/blog') ?>" class="btn btn--ghost">Отмена</a>
        </div>
    </form>
</div>

<script>
    window.CSRF_TOKEN = <?= json_encode(Csrf::token()) ?>;
    window.BLOG_UPLOAD_URL = <?= json_encode(admin_url('/blog/upload-image')) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js" referrerpolicy="origin"></script>
<script src="<?= asset('assets/admin/js/blog-editor.js') ?>"></script>
