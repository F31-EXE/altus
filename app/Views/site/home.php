<?php
/** @var array $services */
/** @var array $works */
/** @var array $reviews */
/** @var array $posts */
?>
<p class="s-lead">Тестовая страница: показывает, как данные из админ-панели выглядят на сайте.</p>

<?php
require APP_PATH . '/Views/site/_services.php';
require APP_PATH . '/Views/site/_works.php';
$showMore = true;
require APP_PATH . '/Views/site/_posts.php';
require APP_PATH . '/Views/site/_reviews.php';
