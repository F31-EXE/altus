#!/usr/bin/env bash
#
# Развёртывание сайта «Альтус» на чистом сервере Debian или Ubuntu.
#
#   sudo bash deploy/server-setup.sh [домен]
#
# Домен необязателен. Без него сайт откроется по IP-адресу сервера, и это
# нормально для проверки; для боевой работы домен нужен, иначе не получить
# сертификат и почта с формы будет уходить в спам ещё охотнее.
#
# Скрипт ставит Apache, PHP и MariaDB, заводит базу со случайным паролем,
# пишет config/config.php, наполняет базу стартовым контентом и создаёт
# администратора. Пароли печатаются в конце, запишите их.
#
# Повторный запуск безопасен: пакеты не переустанавливаются, база не
# затирается (install.php и seed.php идемпотентны), но config/config.php
# будет перезаписан новым паролем базы.

set -euo pipefail

DOMAIN="${1:-}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_NAME="altus"
DB_USER="altus"

say()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
fail() { printf '\n\033[1;31mОшибка: %s\033[0m\n' "$*" >&2; exit 1; }

# --- 0. Проверки до того, как что-то менять -------------------------------

[ "$(id -u)" -eq 0 ] || fail "Запускать от root: sudo bash $0"
command -v apt-get >/dev/null || fail "Скрипт рассчитан на Debian или Ubuntu (нужен apt)."
[ -f "$APP_DIR/public/index.php" ] || fail "Не вижу public/index.php. Положите скрипт внутрь проекта и запускайте из его корня."
[ -f "$APP_DIR/database/install.php" ] || fail "Не вижу database/install.php. Проект неполный."

if [ -z "$DOMAIN" ]; then
    DOMAIN="$(hostname -I | awk '{print $1}')"
    say "Домен не указан, сайт будет открываться по адресу $DOMAIN"
fi

# --- 1. Пакеты ------------------------------------------------------------

say "Ставлю Apache, PHP и MariaDB"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq \
    apache2 libapache2-mod-php \
    php-cli php-mysql php-mbstring php-xml \
    mariadb-server \
    unzip curl ca-certificates

# .htaccess проекта опирается на эти два модуля: без них красивые адреса
# не работают и заголовки безопасности не ставятся.
a2enmod rewrite headers >/dev/null
systemctl enable --now apache2 mariadb >/dev/null

PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
say "PHP $PHP_VER"
php -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' || fail "Нужен PHP 8.1 или новее, установлен $PHP_VER"

for ext in pdo_mysql mbstring dom fileinfo; do
    php -m | grep -qx "$ext" || fail "Не хватает расширения PHP: $ext"
done

# --- 2. База --------------------------------------------------------------

say "Завожу базу $DB_NAME"
DB_PASS="$(openssl rand -base64 18 | tr -d '/+=' | head -c 20)"

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# --- 3. Конфигурация ------------------------------------------------------

say "Пишу config/config.php"

# Схема адреса пока http: сертификат выпускается ниже, и до этого момента
# https в base_url означал бы битые картинки.
cat > "$APP_DIR/config/config.php" <<PHPCONF
<?php
/**
 * Создан автоматически: deploy/server-setup.sh
 * Правьте руками, повторный запуск скрипта затрёт этот файл.
 */
return [
    'app' => [
        'name'         => 'Альтус. Панель управления',
        'base_url'     => 'http://$DOMAIN',
        'admin_prefix' => '/admin',
        'debug'        => false,
        'timezone'     => 'Asia/Yekaterinburg',
    ],
    'legal' => [
        'operator' => 'ООО «Альтус»',
        'inn'      => '6659196518',
        'ogrn'     => '1096659011404',
        'address'  => 'г. Екатеринбург, улица Данилы Зверева, 23, офис 311',
        'email'    => 'scharapov.wadym@yandex.ru',
        'phone'    => '+7 912 045-44-44',
        'updated'  => '14 сентября 2026 года',
    ],
    'mail' => [
        'to'   => 'scharapov.wadym@yandex.ru',
        'from' => 'noreply@$DOMAIN',
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => '$DB_NAME',
        'user'    => '$DB_USER',
        'pass'    => '$DB_PASS',
        'charset' => 'utf8mb4',
    ],
    'uploads' => [
        'dir'            => __DIR__ . '/../public/uploads',
        'url'            => '/uploads',
        'max_size'       => 8 * 1024 * 1024,
        'video_max_size' => 512 * 1024 * 1024,
        'image_ext'      => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'doc_ext'        => ['pdf', 'jpg', 'jpeg', 'png'],
        'video_ext'      => ['mp4', 'webm', 'ogv', 'mov'],
    ],
];
PHPCONF

# Пароль от базы не должен читаться кем попало на сервере.
chown root:www-data "$APP_DIR/config/config.php"
chmod 640 "$APP_DIR/config/config.php"

# --- 4. Таблицы и контент -------------------------------------------------

say "Создаю таблицы"
php "$APP_DIR/database/install.php"

say "Кладу стартовый контент"
php "$APP_DIR/database/seed.php"

say "Создаю администратора"
ADMIN_EMAIL="scharapov.wadym@yandex.ru"
ADMIN_PASS="$(openssl rand -base64 18 | tr -d '/+=' | head -c 16)"
php "$APP_DIR/database/create_admin.php" "Вадим" "$ADMIN_EMAIL" "$ADMIN_PASS"

# --- 5. Права на файлы ----------------------------------------------------

say "Раздаю права"
# Веб-сервер пишет только в каталог загрузок. Остальное ему достаточно читать:
# так взлом через загрузку файла не даст переписать код приложения.
chown -R root:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 750 {} \;
find "$APP_DIR" -type f -exec chmod 640 {} \;
chown -R www-data:www-data "$APP_DIR/public/uploads"
chmod -R 770 "$APP_DIR/public/uploads"
chmod 640 "$APP_DIR/config/config.php"

# --- 6. Apache ------------------------------------------------------------

say "Настраиваю Apache"
# Корень сайта - строго public. Выше лежат config/config.php с паролем
# от базы и весь код: наружу они попадать не должны.
cat > /etc/apache2/sites-available/altus.conf <<VHOST
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot $APP_DIR/public

    <Directory $APP_DIR/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/altus-error.log
    CustomLog \${APACHE_LOG_DIR}/altus-access.log combined
</VirtualHost>
VHOST

a2dissite 000-default >/dev/null 2>&1 || true
a2ensite altus >/dev/null
apache2ctl configtest
systemctl reload apache2

# Видео до 512 МБ не пройдёт через стандартные лимиты PHP.
PHP_INI="/etc/php/$PHP_VER/apache2/php.ini"
if [ -f "$PHP_INI" ]; then
    say "Поднимаю лимиты загрузки в PHP"
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 512M/' "$PHP_INI"
    sed -i 's/^post_max_size = .*/post_max_size = 512M/'             "$PHP_INI"
    sed -i 's/^max_execution_time = .*/max_execution_time = 600/'    "$PHP_INI"
    systemctl reload apache2
fi

# --- 7. Итог --------------------------------------------------------------

cat <<SUMMARY

============================================================
  Готово.

  Сайт    http://$DOMAIN
  Админка http://$DOMAIN/admin

  Вход в админку
    почта   $ADMIN_EMAIL
    пароль  $ADMIN_PASS

  База данных
    имя     $DB_NAME
    юзер    $DB_USER
    пароль  $DB_PASS

  ЗАПИШИТЕ ЭТИ ПАРОЛИ СЕЙЧАС. Пароль базы лежит в
  config/config.php, пароль администратора не хранится
  нигде в открытом виде и восстановлению не подлежит.

  Что сделать дальше
    1. Сменить пароль администратора на свой:
       php database/create_admin.php "Вадим" $ADMIN_EMAIL новый-пароль
    2. Выпустить сертификат (нужен домен, а не IP):
       apt install -y certbot python3-certbot-apache
       certbot --apache -d $DOMAIN
       затем в config/config.php поменять base_url на https://$DOMAIN
    3. Настроить SPF у регистратора домена, иначе письма с заявками
       будут попадать в спам. Заявки при этом всё равно сохраняются
       в админке, раздел «Заявки с сайта».
============================================================

SUMMARY
