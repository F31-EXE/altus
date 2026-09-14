<aside class="sidebar">
    <div class="sidebar__brand"><?= e(config('app.name')) ?></div>
    <nav class="sidebar__nav">
        <a class="nav-link <?= (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/') ? 'active' : '' ?>"
           href="<?= admin_url('/') ?>">Дашборд</a>
        <?php $newLeads = \App\Models\Lead::unreadCount(); ?>
        <a class="nav-link <?= nav_active('/leads') ?>" href="<?= admin_url('/leads') ?>">
            Заявки с сайта<?= $newLeads ? ' <span class="badge">' . $newLeads . '</span>' : '' ?>
        </a>
        <a class="nav-link <?= nav_active('/services') ?>" href="<?= admin_url('/services') ?>">Наши услуги</a>
        <a class="nav-link <?= nav_active('/reviews') ?>" href="<?= admin_url('/reviews') ?>">Отзывы</a>
        <a class="nav-link <?= nav_active('/blog') ?>" href="<?= admin_url('/blog') ?>">Блог</a>
        <a class="nav-link <?= nav_active('/works') ?>" href="<?= admin_url('/works') ?>">Наши работы</a>
        <a class="nav-link <?= nav_active('/videos') ?>" href="<?= admin_url('/videos') ?>">Видео</a>
        <a class="nav-link <?= nav_active('/about') ?>" href="<?= admin_url('/about') ?>">О нас</a>
        <div class="sidebar__sep"></div>
        <a class="nav-link <?= nav_active('/users') ?>" href="<?= admin_url('/users') ?>">Пользователи</a>
    </nav>
</aside>
