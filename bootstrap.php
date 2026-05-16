<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Container;
use App\Core\Database;
use App\Core\Router;
use Twig\Environment as Twig;
use Twig\Loader\FilesystemLoader as TwigLoader;

define('APP_ROOT', __DIR__);

require_once APP_ROOT . '/vendor/autoload.php';

$config = require APP_ROOT . '/config/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');
mb_internal_encoding($config['app']['charset'] ?? 'UTF-8');

// сессия (не стартуем в CLI — мешает тестам)
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

// режим отладки
if (!empty($config['app']['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

$container = new Container();
$container->instance(Container::class, $container);

// конфиг доступен через контейнер
$container->instance('config', new ArrayObject($config, ArrayObject::ARRAY_AS_PROPS));

// БД — синглтон, ленивая инициализация
$container->singleton(Database::class, fn() => new Database($config['database']));

$container->singleton(Router::class, fn(Container $c) => new Router($c));
$container->singleton(App::class);

// Twig — синглтон
$container->singleton(Twig::class, function () use ($config): Twig {
    $debug  = !empty($config['app']['debug']);
    $loader = new TwigLoader(APP_ROOT . '/app/Views/themes/default');

    $twig = new Twig($loader, [
        'cache' => $debug ? false : APP_ROOT . '/storage/cache/twig',
        'debug' => $debug,
        'strict_variables' => false,
        'auto_reload'      => $debug,
    ]);

    // глобальные переменные шаблонов
    $twig->addGlobal('site_name', $config['app']['name'] ?? 'CMS-Flexi');
    $twig->addGlobal('session', $_SESSION ?? []);

    if ($debug) {
        $twig->addExtension(new \Twig\Extension\DebugExtension());
    }

    return $twig;
});

/** @var Router $router */
$router = $container->make(Router::class);

// загружаем роуты, если есть
$routesFile = APP_ROOT . '/config/routes.php';
if (is_file($routesFile)) {
    (require $routesFile)($router);
}

return $container->make(App::class);
