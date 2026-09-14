<?php /** @var array $leads  результат paginate */ ?>

<?php if (!$leads['items']): ?>
    <div class="panel">
        <p class="muted">Заявок пока нет.</p>
        <p class="muted">Они приходят из формы в разделе «Контакты» на сайте
            и дублируются письмом на <?= e((string) config('mail.to')) ?>.</p>
    </div>
<?php else: ?>
    <table class="table">
        <thead>
        <tr>
            <th>Когда</th>
            <th>Кто</th>
            <th>Что нужно</th>
            <th>Письмо</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($leads['items'] as $l): ?>
            <tr<?= $l['is_read'] ? '' : ' class="is-new"' ?>>
                <td class="nowrap">
                    <?= e(date('d.m.Y', strtotime($l['created_at']))) ?><br>
                    <span class="muted"><?= e(date('H:i', strtotime($l['created_at']))) ?></span>
                </td>
                <td>
                    <strong><?= e($l['name']) ?></strong><br>
                    <a href="tel:<?= e(preg_replace('/\D+/', '', $l['phone'])) ?>"><?= e($l['phone']) ?></a>
                    <?php if (!empty($l['contact'])): ?>
                        <br><span class="muted"><?= e($l['contact']) ?></span>
                    <?php endif; ?>
                </td>
                <?php /* Поле необязательное: пустая ячейка читалась бы как
                         сбой вёрстки, поэтому подписываем явно. */ ?>
                <td><?= trim((string) $l['message']) !== ''
                        ? nl2br(e($l['message']))
                        : '<span class="muted">не указано</span>' ?></td>
                <td class="nowrap">
                    <?php if ($l['mailed']): ?>
                        <span class="muted">отправлено</span>
                    <?php else: ?>
                        <strong title="Письмо не ушло. Заявка сохранена здесь, свяжитесь вручную.">не ушло</strong>
                    <?php endif; ?>
                </td>
                <td class="nowrap">
                    <form method="post" action="<?= admin_url('/leads/' . $l['id'] . '/read') ?>" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--ghost btn--sm">
                            <?= $l['is_read'] ? 'Вернуть в новые' : 'Прочитано' ?>
                        </button>
                    </form>
                    <form method="post" action="<?= admin_url('/leads/' . $l['id'] . '/delete') ?>" class="inline"
                          onsubmit="return confirm('Удалить заявку? Это нужно, если заявитель попросил удалить свои данные.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php $data = $leads; $baseUrl = admin_url('/leads'); require APP_PATH . '/Views/partials/pagination.php'; ?>
