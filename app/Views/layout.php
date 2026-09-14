<?php
use App\Core\Auth;
use App\Core\Flash;
/** @var string $__template */
/** @var string $title */
$flashes = Flash::pull();
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/admin/css/admin.css') ?>">
</head>
<body>
<div class="layout">
    <?php require APP_PATH . '/Views/partials/sidebar.php'; ?>

    <main class="content">
        <header class="topbar">
            <div class="topbar__title"><?= e($title) ?></div>
            <div class="topbar__user">
                <?php if ($currentUser): ?>
                    <span><?= e($currentUser['name']) ?></span>
                    <form method="post" action="<?= admin_url('/logout') ?>" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--ghost btn--sm">Выйти</button>
                    </form>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($flashes): ?>
            <div class="flash-stack">
                <?php foreach ($flashes as $f): ?>
                    <div class="flash flash--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="page">
            <?php require $__template; ?>
        </div>
    </main>
</div>
<script src="<?= asset('assets/admin/js/admin.js') ?>"></script>
</body>
</html>
