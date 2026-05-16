# CMS-Flexi

Лёгкий блог-CMS на PHP 8.2, MVC, без фреймворков.

## Требования

- PHP **8.2+** с расширениями `pdo`, `pdo_mysql`
- [Composer](https://getcomposer.org/)
- MySQL **8.0+** (или MariaDB 10.5+)

## Запуск за 5 шагов

### 1. Зависимости

```bash
composer install
```

### 2. База данных

**Вариант А — через Docker** (быстрее всего):

```bash
docker run -d --name cms-flexi-mysql \
  -e MYSQL_ALLOW_EMPTY_PASSWORD=yes \
  -e MYSQL_DATABASE=cms_flexi \
  -p 3306:3306 mysql:8.0

# подождать ~10 секунд, затем накатить схему:
docker exec -i cms-flexi-mysql mysql -uroot -h127.0.0.1 --protocol=TCP < database/schema.sql
```

**Вариант Б — локальный MySQL:**

```bash
mysql -u root < database/schema.sql
```

### 3. Конфиг

Открыть [config/config.php](config/config.php) и при необходимости поменять секцию `database` (host / user / password). По умолчанию:

```
host:     127.0.0.1
port:     3306
database: cms_flexi
user:     root
password: (пустой)
```

### 4. Сервер

Простейший вариант — встроенный PHP-сервер:

```bash
php -S 127.0.0.1:8000 -t public
```

Для продакшена — Apache/Nginx, document root → папка `public/`. `.htaccess` уже настроен.

### 5. Открыть в браузере

- Регистрация — http://127.0.0.1:8000/register
- Вход — http://127.0.0.1:8000/login
- Выход — http://127.0.0.1:8000/logout

## Структура проекта

```
app/
  Core/          # ядро (Container, Router, Database, BaseController, App)
  Models/        # модели (User, ...)
  Presentation/  # контроллеры
  Views/         # шаблоны
config/          # config.php, routes.php
database/        # schema.sql
public/          # фронт-контроллер index.php
bootstrap.php    # инициализация приложения
```

## Полезные команды

```bash
# проверка синтаксиса всех PHP-файлов
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

# пересобрать автозагрузчик после добавления классов
composer dump-autoload -o

# остановить и удалить Docker-контейнер с БД
docker rm -f cms-flexi-mysql
```

## Что внутри уже работает

- DI-контейнер с авторезолвом через Reflection API
- Роутер (GET/POST/PUT/PATCH/DELETE, параметры в URL: `/post/{slug}`)
- PDO-обёртка только с prepared statements
- Регистрация / логин / логаут (`bcrypt`, защита от session fixation, PRG flash-сообщения)
- Схема БД: `users`, `categories`, `tags`, `posts`, `post_tags`, `comments`
