<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Core\BaseController;
use App\Core\Container;
use App\Models\User;

class AuthController extends BaseController
{
    public function __construct(Container $container, private User $users)
    {
        parent::__construct($container);
    }

    public function showLogin(): string
    {
        return $this->view('auth.login', $this->popFlash());
    }

    public function login(): string
    {
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $errors = [];
        if ($email === '')                                 $errors[] = 'Email обязателен';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';
        if (strlen($password) < 8)                          $errors[] = 'Пароль минимум 8 символов';

        if ($errors) {
            return $this->flashAndBack('/login', $errors, ['email' => $email]);
        }

        $user = $this->users->findByEmail($email);

        // одинаковый ответ для «нет пользователя» и «неверный пароль» — чтобы не палить базу
        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            return $this->flashAndBack('/login', ['Неверный email или пароль'], ['email' => $email]);
        }

        if (!$user['is_active']) {
            return $this->flashAndBack('/login', ['Аккаунт отключён']);
        }

        // защита от session fixation
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        return $this->redirect('/');
    }

    public function logout(): string
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return $this->redirect('/login');
    }

    public function showRegister(): string
    {
        return $this->view('auth.register', $this->popFlash());
    }

    public function register(): string
    {
        $username    = trim((string)($_POST['username'] ?? ''));
        $email       = trim((string)($_POST['email'] ?? ''));
        $password    = (string)($_POST['password'] ?? '');
        $displayName = trim((string)($_POST['display_name'] ?? ''));

        $errors = [];

        if ($username === '')                              $errors[] = 'Username обязателен';
        elseif (strlen($username) < 3)                     $errors[] = 'Username минимум 3 символа';
        elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) $errors[] = 'Username: только латиница, цифры и _.-';

        if ($email === '')                                  $errors[] = 'Email обязателен';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';

        if (strlen($password) < 8) $errors[] = 'Пароль минимум 8 символов';

        // проверяем уникальность только если базовая валидация прошла
        if (!$errors) {
            if ($this->users->findByEmail($email))       $errors[] = 'Email уже занят';
            if ($this->users->findByUsername($username)) $errors[] = 'Username уже занят';
        }

        if ($errors) {
            return $this->flashAndBack('/register', $errors, [
                'username'     => $username,
                'email'        => $email,
                'display_name' => $displayName,
            ]);
        }

        $id = $this->users->create([
            'username'     => $username,
            'email'        => $email,
            'password'     => $password,
            'display_name' => $displayName !== '' ? $displayName : null,
        ]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;

        return $this->redirect('/');
    }

    // ------ flash-сообщения ------

    private function flashAndBack(string $url, array $errors, array $old = []): string
    {
        $_SESSION['flash_errors'] = $errors;
        $_SESSION['flash_old']    = $old;
        return $this->redirect($url);
    }

    private function popFlash(): array
    {
        $errors = $_SESSION['flash_errors'] ?? [];
        $old    = $_SESSION['flash_old']    ?? [];
        unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

        return ['errors' => $errors, 'old' => $old];
    }
}
