-- CMS-Flexi — схема БД
-- MySQL 8.0+ / MariaDB 10.5+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `cms_flexi`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `cms_flexi`;

-- пользователи
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(64)     NOT NULL,
    `email`         VARCHAR(190)    NOT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,
    `display_name`  VARCHAR(120)    NULL DEFAULT NULL,
    `role`          ENUM('admin','editor','author','subscriber') NOT NULL DEFAULT 'subscriber',
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- категории (с поддержкой вложенности)
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id`   BIGINT UNSIGNED NULL DEFAULT NULL,
    `name`        VARCHAR(120)    NOT NULL,
    `slug`        VARCHAR(160)    NOT NULL,
    `description` TEXT            NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`),
    KEY `ix_categories_parent` (`parent_id`),
    CONSTRAINT `fk_categories_parent`
        FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- теги
DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(80)     NOT NULL,
    `slug`       VARCHAR(100)    NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tags_slug` (`slug`),
    UNIQUE KEY `uq_tags_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- посты
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `author_id`      BIGINT UNSIGNED NOT NULL,
    `category_id`    BIGINT UNSIGNED NULL DEFAULT NULL,
    `title`          VARCHAR(200)    NOT NULL,
    `slug`           VARCHAR(220)    NOT NULL,
    `excerpt`        VARCHAR(500)    NULL DEFAULT NULL,
    `content`        LONGTEXT        NOT NULL,
    `featured_image` VARCHAR(255)    NULL DEFAULT NULL,
    `status`         ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `views`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `published_at`   DATETIME        NULL DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_posts_slug` (`slug`),
    KEY `ix_posts_author`     (`author_id`),
    KEY `ix_posts_category`   (`category_id`),
    KEY `ix_posts_status_pub` (`status`, `published_at`),
    CONSTRAINT `fk_posts_author`
        FOREIGN KEY (`author_id`) REFERENCES `users`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_posts_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- связь постов и тегов (M:N)
DROP TABLE IF EXISTS `post_tags`;
CREATE TABLE `post_tags` (
    `post_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`  BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`post_id`, `tag_id`),
    KEY `ix_post_tags_tag` (`tag_id`),
    CONSTRAINT `fk_post_tags_post`
        FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_post_tags_tag`
        FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- комментарии (с древовидностью через parent_id)
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id`      BIGINT UNSIGNED NOT NULL,
    `user_id`      BIGINT UNSIGNED NULL DEFAULT NULL,
    `parent_id`    BIGINT UNSIGNED NULL DEFAULT NULL,
    `author_name`  VARCHAR(120)    NULL DEFAULT NULL,
    `author_email` VARCHAR(190)    NULL DEFAULT NULL,
    `content`      TEXT            NOT NULL,
    `status`       ENUM('pending','approved','spam','trash') NOT NULL DEFAULT 'pending',
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_comments_post`   (`post_id`),
    KEY `ix_comments_user`   (`user_id`),
    KEY `ix_comments_parent` (`parent_id`),
    KEY `ix_comments_status` (`status`),
    CONSTRAINT `fk_comments_post`
        FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_comments_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_comments_parent`
        FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
