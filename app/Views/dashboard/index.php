<?php /** @var array $counts */ ?>
<div class="cards">
    <?php foreach ($counts as $label => $value): ?>
        <div class="card">
            <div class="card__value"><?= $value === null ? '—' : (int) $value ?></div>
            <div class="card__label"><?= e($label) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="panel">
    <p>Готовы все разделы: «Наши услуги», «Отзывы», «Блог», «Наши работы».</p>
    <p><a href="<?= admin_url('/site') ?>" target="_blank" rel="noopener">Открыть демо-сайт (клиентская часть) →</a></p>
</div>
