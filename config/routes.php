<?php

declare(strict_types=1);

use App\Core\Router;
use App\Presentation\AuthController;
use App\Presentation\HomeController;

// роуты приложения
return function (Router $router): void {
    // главная
    $router->get('/', [HomeController::class, 'index']);

    // авторизация
    $router->get('/login',     [AuthController::class, 'showLogin']);
    $router->post('/login',    [AuthController::class, 'login']);
    $router->get('/register',  [AuthController::class, 'showRegister']);
    $router->post('/register', [AuthController::class, 'register']);
    $router->get('/logout',    [AuthController::class, 'logout']);
};
