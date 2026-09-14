<?php
use App\Core\Auth;
/** @var array $users  результат paginate */
?>
<div class="toolbar">
    <a href="<?= admin_url('/users/create') ?>" class="btn btn--primary">+ Новый пользователь</a>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <th style="width:60px">ID</th>
            <th>Имя</th>
            <th>Email</th>
            <th>Создан</th>
            <th style="width:170px"></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$users['items']): ?>
            <tr><td colspan="5" class="table__empty">Пользователей нет</td></tr>
        <?php endif; ?>
        <?php foreach ($users['items'] as $u): ?>
            <tr>
                <td><?= (int) $u['id'] ?></td>
                <td><?= e($u['name']) ?><?= $u['id'] == Auth::id() ? ' <span class="badge">вы</span>' : '' ?></td>
                <td><?= e($u['email']) ?></td>
                <td class="muted"><?= e($u['created_at']) ?></td>
                <td class="row-actions">
                    <a href="<?= admin_url('/users/' . $u['id'] . '/edit') ?>" class="btn btn--ghost btn--sm">Изменить</a>
                    <?php if ($u['id'] != Auth::id()): ?>
                        <form method="post" action="<?= admin_url('/users/' . $u['id'] . '/delete') ?>" class="inline"
                              onsubmit="return confirm('Удалить пользователя «<?= e($u['name']) ?>»?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $data = $users; $baseUrl = admin_url('/users'); require APP_PATH . '/Views/partials/pagination.php'; ?>
