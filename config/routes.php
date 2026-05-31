<?php

declare(strict_types=1);

use App\Core\Router;
use App\Presentation\AuthController;
use App\Presentation\CommentController;
use App\Presentation\ContactController;
use App\Presentation\HomeController;
use App\Presentation\PostController;

// роуты приложения
return function (Router $router): void {
    // главная
    $router->get('/', [HomeController::class, 'index']);

    // посты
    $router->get('/posts',       [PostController::class, 'index']);
    $router->get('/post/{slug}', [PostController::class, 'show']);

    // категории и теги
    $router->get('/category/{slug}', [PostController::class, 'byCategory']);
    $router->get('/tag/{slug}',      [PostController::class, 'byTag']);

    // комментарии
    $router->post('/post/{slug}/comment', [CommentController::class, 'store']);

    // контакты
    $router->get('/contact',  [ContactController::class, 'show']);
    $router->post('/contact', [ContactController::class, 'send']);

    // авторизация
    $router->get('/login',     [AuthController::class, 'showLogin']);
    $router->post('/login',    [AuthController::class, 'login']);
    $router->get('/register',  [AuthController::class, 'showRegister']);
    $router->post('/register', [AuthController::class, 'register']);
    $router->get('/logout',    [AuthController::class, 'logout']);
};
