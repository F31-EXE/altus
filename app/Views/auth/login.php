<?php /** @var array $flashes */ ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/admin/css/admin.css') ?>">
</head>
<body class="auth-body">
<div class="auth-card">
    <h1 class="auth-card__title"><?= e(config('app.name')) ?></h1>
    <p class="auth-card__subtitle">Вход в панель управления</p>

    <?php foreach ($flashes as $f): ?>
        <div class="flash flash--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= admin_url('/login') ?>" class="form">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field__label">Email</span>
            <input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus class="input">
        </label>
        <label class="field">
            <span class="field__label">Пароль</span>
            <input type="password" name="password" required class="input">
        </label>
        <button type="submit" class="btn btn--primary btn--block">Войти</button>
    </form>
</div>
</body>
</html>
