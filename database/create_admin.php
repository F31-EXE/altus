<?php
/**
 * Создание/обновление пользователя админки.
 *
 * Запуск из корня проекта:
 *   php database/create_admin.php "Имя" email@example.com пароль
 *
 * Если пользователь с таким email уже есть — обновит имя и пароль.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Models\User;

[$script, $name, $email, $password] = array_pad($argv, 4, null);

if (!$name || !$email || !$password) {
    fwrite(STDERR, "Использование: php database/create_admin.php \"Имя\" email пароль\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Некорректный email\n");
    exit(1);
}
if (mb_strlen($password) < 10) {
    fwrite(STDERR, "Пароль должен быть не короче 10 символов\n");
    exit(1);
}

$existing = User::findByEmail($email);

if ($existing) {
    User::update((int) $existing['id'], ['name' => $name, 'email' => $email]);
    User::updatePassword((int) $existing['id'], $password);
    echo "Пользователь обновлён: {$email}\n";
} else {
    $id = User::create([
        'name'          => $name,
        'email'         => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    echo "Пользователь создан (id={$id}): {$email}\n";
}
