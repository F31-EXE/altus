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

# --- 1a. Какой именно PHP мы настраиваем ----------------------------------

# /usr/bin/php - это ссылка из update-alternatives, и мета-пакеты (php-cli,
# php-defaults) умеют переставить её уже после установки, на другую версию
# или на диспетчер php.default. Апачу при этом служит своя версия - та, для
# которой собран libapache2-mod-php. Поэтому версию берём от Апача и дальше
# зовём её бинарник напрямую: расширения, которые мы проверяем, тогда ровно
# те, с которыми будет работать сайт, а не те, что достались `php` по
# стечению обстоятельств.
#
# sort -V, а не просто последний по алфавиту: 8.10 старше 8.5, но лексически
# идёт раньше.
PHP_VER="$(ls -1d /etc/php/*/apache2 2>/dev/null | awk -F/ '{print $4}' | sort -V | tail -1)"
[ -n "$PHP_VER" ] || fail "Модуль PHP для Apache не установился: каталога /etc/php/*/apache2 нет."

# Мета-пакет php-cli мог притащить другую версию, чем libapache2-mod-php.
# Тогда бинарника нужной версии просто нет - доставляем, иначе проверять
# расширения будем не у того PHP, что обслуживает сайт.
if ! command -v "php$PHP_VER" >/dev/null 2>&1; then
    say "Доставляю php$PHP_VER-cli"
    apt-get install -y -qq "php$PHP_VER-cli" >/dev/null 2>&1 || true
fi

PHP_BIN="php$PHP_VER"
command -v "$PHP_BIN" >/dev/null 2>&1 || PHP_BIN="php"
command -v "$PHP_BIN" >/dev/null 2>&1 || fail "PHP не установился: нет ни php$PHP_VER, ни php."

say "PHP $PHP_VER ($(command -v "$PHP_BIN"))"
"$PHP_BIN" -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' \
    || fail "Нужен PHP 8.1 или новее, установлен $PHP_VER"

# --- 1b. Расширения -------------------------------------------------------

# Пакет кладёт ini в mods-available, а включение в SAPI - отдельный шаг,
# и он происходит сам не всегда. Плюс мета-пакет php-mysql мог поставить
# модуль к другой версии PHP, не к той, что у Апача. Поэтому не сдаёмся
# на первом промахе: сначала пробуем включить, потом доставить пакет нужной
# версии, и только потом ругаемся - сразу всем списком и с диагностикой,
# чтобы не выяснять по одному расширению за запуск.

has_ext() { "$PHP_BIN" -m | grep -qix "$1"; }

# Имя пакета не всегда совпадает с именем расширения.
ext_package() {
    case "$1" in
        pdo_mysql) echo "php$PHP_VER-mysql"    ;;
        dom)       echo "php$PHP_VER-xml"      ;;
        fileinfo)  echo ""                     ;;  # собран в ядро, пакета нет
        *)         echo "php$PHP_VER-$1"       ;;
    esac
}

MISSING=""
for ext in pdo_mysql mbstring dom fileinfo; do
    if has_ext "$ext"; then continue; fi

    if command -v phpenmod >/dev/null 2>&1; then
        phpenmod -v "$PHP_VER" -s ALL "$ext" >/dev/null 2>&1 || true
        if has_ext "$ext"; then say "Включил расширение $ext"; continue; fi
    fi

    pkg="$(ext_package "$ext")"
    if [ -n "$pkg" ]; then
        say "Доставляю $pkg"
        apt-get install -y -qq "$pkg" >/dev/null 2>&1 || true
        if command -v phpenmod >/dev/null 2>&1; then
            phpenmod -v "$PHP_VER" -s ALL "$ext" >/dev/null 2>&1 || true
        fi
        if has_ext "$ext"; then continue; fi
    fi

    MISSING="$MISSING $ext"
done

if [ -n "$MISSING" ]; then
    printf '\n--- %s -v ---\n'    "$PHP_BIN"; "$PHP_BIN" -v    || true
    printf '\n--- %s --ini ---\n' "$PHP_BIN"; "$PHP_BIN" --ini || true
    printf '\n--- %s -m ---\n'    "$PHP_BIN"; "$PHP_BIN" -m    || true
    printf '\n--- пакеты php ---\n'; dpkg-query -W -f='${Package} ${Status}\n' 'php*' 2>/dev/null \
        | grep ' install ok installed$' | awk '{print "  " $1}' || true
    fail "Не хватает расширений PHP:$MISSING
Выше вывод php -v, php --ini, php -m и список пакетов - пришлите его целиком."
fi

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
"$PHP_BIN" "$APP_DIR/database/install.php"

say "Кладу стартовый контент"
"$PHP_BIN" "$APP_DIR/database/seed.php"

say "Создаю администратора"
ADMIN_EMAIL="scharapov.wadym@yandex.ru"
ADMIN_PASS="$(openssl rand -base64 18 | tr -d '/+=' | head -c 16)"
"$PHP_BIN" "$APP_DIR/database/create_admin.php" "Вадим" "$ADMIN_EMAIL" "$ADMIN_PASS"

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
       $PHP_BIN database/create_admin.php "Вадим" $ADMIN_EMAIL новый-пароль
    2. Выпустить сертификат (нужен домен, а не IP):
       apt install -y certbot python3-certbot-apache
       certbot --apache -d $DOMAIN
       затем в config/config.php поменять base_url на https://$DOMAIN
    3. Настроить SPF у регистратора домена, иначе письма с заявками
       будут попадать в спам. Заявки при этом всё равно сохраняются
       в админке, раздел «Заявки с сайта».
============================================================

SUMMARY
