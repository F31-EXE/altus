<?php
/**
 * Создание базы и импорт схемы силами одного PHP.
 *
 * Запуск из корня проекта:
 *   php database/install.php
 *
 * Зачем отдельный скрипт, если есть schema.sql. На Windows консольного
 * клиента mysql часто нет в PATH: он лежит внутри Laragon или XAMPP и
 * наружу не выставлен. А PHP там есть всегда, иначе проект вообще не
 * запустится. Плюс в PowerShell не работает перенаправление файла через
 * `mysql < schema.sql`: оператор `<` там зарезервирован. Этот скрипт
 * убирает оба препятствия.
 *
 * Доступы берутся из config/config.php, отдельно ничего вводить не нужно.
 * Скрипт идемпотентный: существующую базу не трогает, таблицы создаются
 * через CREATE TABLE IF NOT EXISTS, данные не затираются.
 */

if (PHP_SAPI !== 'cli') {
    exit('Только из командной строки.');
}

/* Обычный bootstrap здесь не годится: он в конце сам подключается к базе,
   а её на этот момент ещё нет, и скрипт падал бы на первой же строке.
   Поэтому берём только то, что нужно для чтения конфига. */
define('CONFIG_PATH', dirname(__DIR__) . '/config');

if (!is_file(CONFIG_PATH . '/config.php')) {
    fwrite(STDERR, "Нет config/config.php. Скопируйте config/config.example.php и впишите доступы к БД.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/Core/helpers.php';

$cfg  = config('db');
$name = $cfg['name'];

/* Подключаемся без имени базы: её ещё может не быть. */
$dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $cfg['host'], $cfg['port'], $cfg['charset']);

try {
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Не удалось подключиться к MySQL: " . $e->getMessage() . "\n\n");
    fwrite(STDERR, "Проверьте в config/config.php раздел 'db': host, port, user, pass.\n");
    fwrite(STDERR, "И что сервер MySQL запущен (в Laragon это кнопка «Start All»).\n");
    exit(1);
}

/* Имя базы в идентификатор не подставляется параметром, поэтому проверяем
   его сами: только то, что вообще может быть именем базы. */
if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
    fwrite(STDERR, "Недопустимое имя базы в конфиге: {$name}\n");
    exit(1);
}

$existed = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = " . $pdo->quote($name)
)->fetchColumn();

$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo $existed ? "База {$name} уже была, оставляю как есть\n" : "База {$name} создана\n";

$pdo->exec("USE `{$name}`");

/* Разбираем schema.sql. Комментарии убираем, потом режем по «;».
   В этом файле нет ни процедур, ни точек с запятой внутри строк,
   поэтому такого разбора достаточно. */
$sql = file_get_contents(__DIR__ . '/schema.sql');
$sql = preg_replace('/^\s*--.*$/m', '', $sql);

$statements = array_filter(array_map('trim', explode(';', $sql)), static fn($s) => $s !== '');

$done = 0;
foreach ($statements as $statement) {
    $pdo->exec($statement);
    $done++;
}

echo "Выполнено запросов: {$done}\n";

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
sort($tables);
echo 'Таблицы: ' . implode(', ', $tables) . "\n\n";

echo "Дальше:\n";
echo "  php database/seed.php                 стартовый контент сайта\n";
echo "  php database/create_admin.php \"Имя\" почта пароль-от-10-символов\n";
