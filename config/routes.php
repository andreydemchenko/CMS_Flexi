<?php

declare(strict_types=1);

use App\Core\Router;
use App\Presentation\Admin\CategoryAdminController;
use App\Presentation\Admin\CommentAdminController;
use App\Presentation\Admin\DashboardController;
use App\Presentation\Admin\PostAdminController;
use App\Presentation\Admin\TagAdminController;
use App\Presentation\Admin\UserAdminController;
use App\Presentation\AuthController;
use App\Presentation\CommentController;
use App\Presentation\ContactController;
use App\Presentation\HomeController;
use App\Presentation\PostController;

// роуты приложения
return function (Router $router): void {

    // ─── ПУБЛИЧНЫЙ САЙТ ──────────────────────────────────────────────
    $router->get('/', [HomeController::class, 'index']);

    $router->get('/posts',       [PostController::class, 'index']);
    $router->get('/post/{slug}', [PostController::class, 'show']);

    $router->get('/category/{slug}', [PostController::class, 'byCategory']);
    $router->get('/tag/{slug}',      [PostController::class, 'byTag']);

    $router->post('/post/{slug}/comment', [CommentController::class, 'store']);

    $router->get('/contact',  [ContactController::class, 'show']);
    $router->post('/contact', [ContactController::class, 'send']);

    // ─── АВТОРИЗАЦИЯ ────────────────────────────────────────────────
    $router->get('/login',     [AuthController::class, 'showLogin']);
    $router->post('/login',    [AuthController::class, 'login']);
    $router->get('/register',  [AuthController::class, 'showRegister']);
    $router->post('/register', [AuthController::class, 'register']);
    $router->get('/logout',    [AuthController::class, 'logout']);

    // ─── АДМИНКА (защищена AuthMiddleware в конструкторах контроллеров) ──
    $router->get('/admin', [DashboardController::class, 'index']);

    // posts
    $router->get( '/admin/posts',             [PostAdminController::class, 'index']);
    $router->get( '/admin/posts/create',      [PostAdminController::class, 'create']);
    $router->post('/admin/posts',             [PostAdminController::class, 'store']);
    $router->get( '/admin/posts/{id}/edit',   [PostAdminController::class, 'edit']);
    $router->post('/admin/posts/{id}',        [PostAdminController::class, 'update']);
    $router->post('/admin/posts/{id}/delete', [PostAdminController::class, 'delete']);

    // categories
    $router->get( '/admin/categories',             [CategoryAdminController::class, 'index']);
    $router->post('/admin/categories',             [CategoryAdminController::class, 'store']);
    $router->post('/admin/categories/{id}',        [CategoryAdminController::class, 'update']);
    $router->post('/admin/categories/{id}/delete', [CategoryAdminController::class, 'delete']);

    // tags
    $router->get( '/admin/tags',             [TagAdminController::class, 'index']);
    $router->post('/admin/tags',             [TagAdminController::class, 'store']);
    $router->post('/admin/tags/{id}',        [TagAdminController::class, 'update']);
    $router->post('/admin/tags/{id}/delete', [TagAdminController::class, 'delete']);

    // comments
    $router->get( '/admin/comments',                [CommentAdminController::class, 'index']);
    $router->post('/admin/comments/{id}/approve',   [CommentAdminController::class, 'approve']);
    $router->post('/admin/comments/{id}/delete',    [CommentAdminController::class, 'delete']);

    // users
    $router->get( '/admin/users',             [UserAdminController::class, 'index']);
    $router->get( '/admin/users/create',      [UserAdminController::class, 'create']);
    $router->post('/admin/users',             [UserAdminController::class, 'store']);
    $router->get( '/admin/users/{id}/edit',   [UserAdminController::class, 'edit']);
    $router->post('/admin/users/{id}',        [UserAdminController::class, 'update']);
    $router->post('/admin/users/{id}/delete', [UserAdminController::class, 'delete']);
};
