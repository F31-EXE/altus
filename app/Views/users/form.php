<?php
/** @var array|null $user */
/** @var string $action */
$isEdit = $user !== null;
?>
<div class="panel panel--form">
    <form method="post" action="<?= e($action) ?>" class="form">
        <?= csrf_field() ?>

        <label class="field">
            <span class="field__label">Имя *</span>
            <input type="text" name="name" class="input <?= hasError('name') ? 'input--error' : '' ?>"
                   value="<?= e(old('name', $user['name'] ?? '')) ?>" required maxlength="100">
            <?php foreach (errors('name') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <label class="field">
            <span class="field__label">Email *</span>
            <input type="email" name="email" class="input <?= hasError('email') ? 'input--error' : '' ?>"
                   value="<?= e(old('email', $user['email'] ?? '')) ?>" required maxlength="190">
            <?php foreach (errors('email') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <label class="field">
            <span class="field__label">
                Пароль <?= $isEdit ? '<span class="muted">(оставьте пустым, чтобы не менять)</span>' : '*' ?>
            </span>
            <input type="password" name="password" class="input <?= hasError('password') ? 'input--error' : '' ?>"
                   <?= $isEdit ? '' : 'required' ?> minlength="10" maxlength="200" autocomplete="new-password">
            <?php foreach (errors('password') as $m): ?><span class="field__error"><?= e($m) ?></span><?php endforeach; ?>
        </label>

        <div class="form__actions">
            <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить' : 'Создать' ?></button>
            <a href="<?= admin_url('/users') ?>" class="btn btn--ghost">Отмена</a>
        </div>
    </form>
</div>
