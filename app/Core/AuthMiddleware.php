<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

// проверка авторизации и ролей; вызывается из конструкторов admin-контроллеров
class AuthMiddleware
{
    // иерархия ролей: чем больше число — тем выше привилегия
    private const HIERARCHY = [
        'subscriber' => 1,
        'author'     => 2,
        'editor'     => 3,
        'admin'      => 4,
    ];

    private ?array $cachedUser = null;
    private bool   $loaded     = false;

    public function __construct(private User $users) {}

    public function requireAuth(string $redirectTo = '/login'): void
    {
        if (!$this->isLoggedIn()) {
            // запомним куда пользователь шёл — чтобы вернуть его сюда после входа
            $intended = $_SERVER['REQUEST_URI'] ?? '';
            if (is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '/login')) {
                $_SESSION['intended_url'] = $intended;
            }
            $_SESSION['flash']['auth'] = [
                'type'    => 'error',
                'message' => 'Нужна авторизация.',
            ];
            $this->redirect($redirectTo);
        }
    }

    public function requireRole(string $minRole): void
    {
        $this->requireAuth();
        $user = $this->user();

        $required = self::HIERARCHY[$minRole] ?? 0;
        $actual   = self::HIERARCHY[$user['role'] ?? ''] ?? 0;

        if ($required === 0 || $actual < $required) {
            http_response_code(403);
            echo '<!doctype html><meta charset="utf-8"><h1 style="font-family:sans-serif;text-align:center;margin-top:80px">403 Forbidden</h1><p style="text-align:center;color:#888">Недостаточно прав для доступа к этой странице.</p>';
            exit;
        }
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }

    public function user(): ?array
    {
        if ($this->loaded) {
            return $this->cachedUser;
        }
        $this->loaded = true;

        if (!$this->isLoggedIn()) {
            return $this->cachedUser = null;
        }

        return $this->cachedUser = $this->users->findById((int) $_SESSION['user_id']);
    }

    private function redirect(string $url): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url, true, 302);
        }
        exit;
    }
}
