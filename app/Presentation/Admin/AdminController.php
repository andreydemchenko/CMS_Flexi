<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use App\Core\AuthMiddleware;
use App\Core\BaseController;
use App\Core\Container;
use App\Support\Csrf;

// общий предок: проверка роли admin + helpers (flash, redirect, csrf)
abstract class AdminController extends BaseController
{
    protected AuthMiddleware $auth;

    public function __construct(Container $container, AuthMiddleware $auth)
    {
        parent::__construct($container);
        $this->auth = $auth;
        $this->auth->requireRole('admin');
    }

    // быстрый рендер с инжекцией flash + current_user (current_user уже глобальный)
    protected function adminRender(string $template, array $data = []): string
    {
        $flash = $_SESSION['flash']['admin'] ?? null;
        unset($_SESSION['flash']['admin']);

        return $this->render($template, array_merge([
            'flash'        => $flash,
            'current_user' => $this->auth->user(),
        ], $data));
    }

    protected function flashSuccess(string $message): void
    {
        $_SESSION['flash']['admin'] = ['type' => 'success', 'message' => $message];
    }

    protected function flashError(string $message): void
    {
        $_SESSION['flash']['admin'] = ['type' => 'error', 'message' => $message];
    }

    // POST-обработчики начинают с этого
    protected function ensurePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo '405 Method Not Allowed';
            exit;
        }
        Csrf::abortIfInvalid();
    }

    protected function input(string $key, $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function trimmed(string $key): string
    {
        return trim((string) ($_POST[$key] ?? ''));
    }

    protected function query(string $key, $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}
