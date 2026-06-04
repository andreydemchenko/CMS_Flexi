-- CMS-Flexi — тестовые данные
-- запускать ПОСЛЕ schema.sql

USE `cms_flexi`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `comments`;
TRUNCATE TABLE `post_tags`;
TRUNCATE TABLE `posts`;
TRUNCATE TABLE `tags`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────────
-- пользователи (пароль для всех: "password" — bcrypt hash ниже)
-- хэш сгенерирован password_hash('password', PASSWORD_DEFAULT)
-- ────────────────────────────────────────────────────────────────
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `display_name`, `role`, `is_active`) VALUES
(1, 'admin',  'admin@cms-flexi.local',  '$2y$10$ojBVm3IEOFMDNVR0MUt8XOR1pxEDWtVk.y2a6hjvcYLKNGbi2NMDi', 'Андрей Демченко', 'admin',  1),
(2, 'editor', 'editor@cms-flexi.local', '$2y$10$ojBVm3IEOFMDNVR0MUt8XOR1pxEDWtVk.y2a6hjvcYLKNGbi2NMDi', 'CMS-Flexi Team', 'editor', 1),
(3, 'maria',  'maria@cms-flexi.local',  '$2y$10$ojBVm3IEOFMDNVR0MUt8XOR1pxEDWtVk.y2a6hjvcYLKNGbi2NMDi', 'Мария Смирнова', 'author', 1);

-- ────────────────────────────────────────────────────────────────
-- категории (3 шт.)
-- ────────────────────────────────────────────────────────────────
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`) VALUES
(1, NULL, 'Технологии',  'tech',      'Новости и обзоры из мира технологий.'),
(2, NULL, 'Разработка',  'dev',       'PHP, MVC, архитектура, инструменты.'),
(3, NULL, 'Стиль жизни', 'lifestyle', 'Путешествия, мода, культура.');

-- ────────────────────────────────────────────────────────────────
-- теги (5 шт.)
-- ────────────────────────────────────────────────────────────────
INSERT INTO `tags` (`id`, `name`, `slug`) VALUES
(1, 'PHP',     'php'),
(2, 'MySQL',   'mysql'),
(3, 'Twig',    'twig'),
(4, 'Docker',  'docker'),
(5, 'Дизайн',  'design');

-- ────────────────────────────────────────────────────────────────
-- посты (5 шт.)
-- ────────────────────────────────────────────────────────────────
INSERT INTO `posts`
    (`id`, `author_id`, `category_id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `status`, `views`, `published_at`)
VALUES
(1, 1, 1, 'PHP 8.2: что нового и зачем оно вам', 'php-8-2-novosti',
 'Новые возможности PHP 8.2 и стоит ли уже мигрировать на свежую версию.',
 '<p>В PHP 8.2 появилось много долгожданных фич: <strong>readonly классы</strong>, типы <code>true</code>/<code>false</code>/<code>null</code>, расширенная поддержка enum.</p><p>Самый ощутимый эффект для архитектуры — readonly classes. Объявить весь класс иммутабельным теперь можно одной строкой, что особенно полезно для DTO и Value Object''ов.</p><h3>Стоит ли переходить?</h3><p>Если у вас современный кодстайл и нет тяжёлых legacy-зависимостей — однозначно да. PHP 8.2 быстрее, безопаснее и приятнее в работе.</p>',
 '/assets/images/post-01.jpg', 'published', 540, '2026-05-12 10:00:00'),

(2, 2, 2, 'MVC своими руками без фреймворка', 'mvc-bez-frameworka',
 'Простой контейнер, роутер и PDO — этого хватает на 90% задач.',
 '<p>Не каждому проекту нужен Symfony или Laravel. Часто хватает <strong>лёгкого MVC</strong> с DI-контейнером, роутером и обёрткой над PDO.</p><p>Такая архитектура понятна новичкам, быстро стартует и легко поддерживается.</p><blockquote>Главное правило — не пытаться написать «свой Symfony». Делайте ровно столько, сколько нужно конкретному проекту.</blockquote><p>Структура минимального MVC: <code>Container</code>, <code>Router</code>, <code>BaseController</code>, <code>Database</code> и набор моделей. Этого достаточно для блога, лендинга и большинства маленьких CMS.</p>',
 '/assets/images/post-02.jpg', 'published', 380, '2026-05-08 09:30:00'),

(3, 1, 2, 'Twig против Blade: какой шаблонизатор выбрать', 'twig-vs-blade',
 'Сравнение синтаксиса, скорости и удобства поддержки.',
 '<p>Twig и Blade — два самых популярных шаблонизатора в PHP-мире.</p><p><strong>Twig</strong>: чистый, безопасный, отлично работает вне Symfony, есть наследование шаблонов, макросы, фильтры. Минус — чуть многословнее.</p><p><strong>Blade</strong>: короче, удобнее в Laravel-экосистеме, но за её пределами почти не используется.</p><h3>Итог</h3><p>Если проект не на Laravel — берите Twig. Если на Laravel — даже не думайте, Blade.</p>',
 '/assets/images/post-03.jpg', 'published', 295, '2026-05-03 14:15:00'),

(4, 3, 3, 'Женский стиль: тренды весны 2026', 'weekend-fashion-2026',
 'Что носить весной — тренды, которые продержатся весь сезон.',
 '<p>Весна 2026 — это <strong>спокойные пастельные оттенки</strong>, силуэты oversize и возвращение классического denim.</p><p>В моде многослойность, натуральные ткани, минимум аксессуаров. Главный совет — носите то, в чём вам комфортно. Тренд — не приговор.</p>',
 '/assets/images/post-04.jpg', 'published', 180, '2026-04-29 12:00:00'),

(5, 2, 1, 'Docker для PHP-разработчика: с нуля до деплоя', 'docker-php-developer',
 'Практический гид по контейнеризации PHP-приложений.',
 '<p>Docker давно стал стандартом в команде. Базовый стек для PHP — <code>php-fpm</code> + <code>nginx</code> + <code>mysql</code> + опционально <code>redis</code>.</p><p>Главное — научиться писать понятный <code>docker-compose.yml</code>, разделять dev- и prod-конфиги и не тащить в образ лишнее.</p><h3>С чего начать</h3><ul><li>Один сервис — один контейнер</li><li>Конфиги через env-переменные</li><li>Volumes для разработки, COPY для production</li></ul>',
 '/assets/images/post-05.jpg', 'published', 425, '2026-04-22 11:00:00');

-- ────────────────────────────────────────────────────────────────
-- связи постов и тегов
-- ────────────────────────────────────────────────────────────────
INSERT INTO `post_tags` (`post_id`, `tag_id`) VALUES
(1, 1),           -- PHP 8.2 → PHP
(2, 1), (2, 3),   -- MVC → PHP, Twig
(3, 1), (3, 3),   -- Twig vs Blade → PHP, Twig
(4, 5),           -- Мода → Дизайн
(5, 1), (5, 4);   -- Docker → PHP, Docker

-- ────────────────────────────────────────────────────────────────
-- комментарии (3 шт. — все одобренные, один с ответом автора)
-- ────────────────────────────────────────────────────────────────
INSERT INTO `comments`
    (`id`, `post_id`, `user_id`, `parent_id`, `author_name`, `author_email`, `content`, `status`)
VALUES
(1, 1, NULL, NULL, 'Игорь Петров', 'igor@example.com',
 'Отличная статья! Readonly классы реально упрощают жизнь, уже перевёл несколько DTO на новый синтаксис.', 'approved'),

(2, 1, 1, 1, NULL, NULL,
 'Спасибо! DTO — действительно идеальный кейс для readonly классов. Дальше будет больше материала по PHP 8.2.', 'approved'),

(3, 2, NULL, NULL, 'Мария Смирнова', 'maria@example.com',
 'Поддерживаю автора. Не каждому проекту нужна тяжёлая артиллерия — для простого блога свой MVC отлично работает.', 'approved');

-- ────────────────────────────────────────────────────────────────
-- (опционально) таблица для сообщений с контактной формы
-- ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(120)    NOT NULL,
    `email`      VARCHAR(190)    NOT NULL,
    `subject`    VARCHAR(200)    NULL DEFAULT NULL,
    `message`    TEXT            NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_contact_messages_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
