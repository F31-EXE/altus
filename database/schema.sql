-- Схема БД админ-панели. Импорт: mysql -u root adminpanel < database/schema.sql
-- или через phpMyAdmin / HeidiSQL (Laragon).

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- Пользователи админки
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100)  NOT NULL,
    `email`         VARCHAR(190)  NOT NULL,
    `password_hash` VARCHAR(255)  NOT NULL,
    `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Шаг 1. Наши услуги
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `services` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(200)   NOT NULL,
    `price`       DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    `description` TEXT           NOT NULL,
    `image_path`  VARCHAR(255)   NOT NULL,
    `sort_order`  INT            NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_services_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Шаг 2. Отзывы
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `author`        VARCHAR(200)   NOT NULL,          -- ФИО или название компании
    `body`          TEXT           NOT NULL,          -- текст отзыва
    `document_path` VARCHAR(255)   NULL DEFAULT NULL, -- скан благодарственного письма (pdf/jpg/png)
    `sort_order`    INT            NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reviews_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Шаг 3. Блог
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `posts` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`              VARCHAR(200)  NOT NULL,
    `slug`               VARCHAR(200)  NOT NULL,             -- адрес будущей страницы новости
    `excerpt`            VARCHAR(500)  NULL DEFAULT NULL,    -- краткое описание для карточки-превью
    `preview_image_path` VARCHAR(255)  NOT NULL,            -- картинка превью
    `body`               LONGTEXT      NOT NULL,            -- HTML из редактора
    `is_published`       TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_posts_slug` (`slug`),
    KEY `idx_posts_published` (`is_published`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Шаг 4. Наши работы
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `works` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `image_path`  VARCHAR(255)   NOT NULL,          -- фотография работы
    `description` TEXT           NOT NULL,          -- описание к фото
    `sort_order`  INT            NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_works_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Шаг 5. Видео
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `videos` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `video_path` VARCHAR(255)   NOT NULL,            -- загруженный видеофайл
    `title`      VARCHAR(200)   NULL DEFAULT NULL,   -- заголовок (опционально)
    `subtitle`   VARCHAR(255)   NULL DEFAULT NULL,   -- подзаголовок (опционально)
    `sort_order` INT            NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_videos_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Защита от перебора пароля на /login
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_throttle` (
    `ip`           VARCHAR(45)  NOT NULL,
    `fails`        INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME     NULL DEFAULT NULL,
    `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Шаг 6. О нас
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `about` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `image_path`  VARCHAR(255)   NOT NULL,            -- фотография
    `title`       VARCHAR(200)   NULL DEFAULT NULL,   -- заголовок (необязательно)
    `description` TEXT           NULL DEFAULT NULL,   -- текстовое описание (необязательно)
    `sort_order`  INT            NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_about_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
