<?php
/** @var array $data  результат Model::paginate() */
/** @var string $baseUrl */
if (($data['pages'] ?? 1) <= 1) {
    return;
}
$cur = $data['page'];
$sep = str_contains($baseUrl, '?') ? '&' : '?';
?>
<nav class="pagination">
    <?php for ($p = 1; $p <= $data['pages']; $p++): ?>
        <?php if ($p === $cur): ?>
            <span class="pagination__item pagination__item--current"><?= $p ?></span>
        <?php else: ?>
            <a class="pagination__item" href="<?= e($baseUrl . $sep . 'page=' . $p) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</nav>
