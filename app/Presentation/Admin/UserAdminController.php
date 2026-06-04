<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use App\Models\User;

class UserAdminController extends AdminController
{
    private const ROLES = ['admin', 'editor', 'author', 'subscriber'];

    public function index(): string
    {
        return $this->adminRender('users/index', [
            'users' => $this->container->make(User::class)->getAll(),
            'roles' => self::ROLES,
        ]);
    }

    public function create(): string
    {
        return $this->renderForm(null);
    }

    public function store(): string
    {
        $this->ensurePost();
        return $this->save(null);
    }

    public function edit(int|string $id): string
    {
        $id   = (int) $id;
        $user = $this->container->make(User::class)->findById($id);
        if (!$user) {
            return $this->notFound();
        }
        return $this->renderForm($user);
    }

    public function update(int|string $id): string
    {
        $this->ensurePost();
        $id   = (int) $id;
        $user = $this->container->make(User::class)->findById($id);
        if (!$user) {
            return $this->notFound();
        }
        return $this->save($user);
    }

    public function delete(int|string $id): string
    {
        $this->ensurePost();
        $id    = (int) $id;
        $users = $this->container->make(User::class);
        $user  = $users->findById($id);
        if (!$user) {
            $this->flashError('Пользователь не найден.');
            return $this->redirect('/admin/users');
        }
        $currentId = (int) ($this->auth->user()['id'] ?? 0);
        if ($currentId === $id) {
            $this->flashError('Нельзя удалить самого себя.');
            return $this->redirect('/admin/users');
        }
        try {
            $users->delete($id);
            $this->flashSuccess('Пользователь удалён.');
        } catch (\Throwable $e) {
            $this->flashError('Не удалось удалить: на пользователе висят записи или комментарии.');
        }
        return $this->redirect('/admin/users');
    }

    // ── helpers ───────────────────────────────────────────────────────

    private function renderForm(?array $user, array $errors = [], array $old = []): string
    {
        $data = $user ? array_merge($user, $old) : $old;

        return $this->adminRender('users/form', [
            'user_row' => $data,
            'is_edit'  => $user !== null,
            'roles'    => self::ROLES,
            'errors'   => $errors,
        ]);
    }

    private function save(?array $existing): string
    {
        $username    = $this->trimmed('username');
        $email       = $this->trimmed('email');
        $displayName = $this->trimmed('display_name');
        $role        = $this->trimmed('role') ?: 'subscriber';
        $password    = (string) $this->input('password', '');
        $isActive    = !empty($_POST['is_active']) ? 1 : 0;

        $errors = [];
        if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 64) {
            $errors['username'] = 'Username от 3 до 64 символов.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            $errors['username'] = 'Только латиница, цифры, _.-';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректный email.';
        }
        if (mb_strlen($displayName) > 120) {
            $errors['display_name'] = 'Имя слишком длинное.';
        }
        if (!in_array($role, self::ROLES, true)) {
            $errors['role'] = 'Некорректная роль.';
        }
        if ($existing === null && strlen($password) < 8) {
            $errors['password'] = 'Пароль минимум 8 символов.';
        }
        if ($existing !== null && $password !== '' && strlen($password) < 8) {
            $errors['password'] = 'Пароль минимум 8 символов.';
        }

        $users = $this->container->make(User::class);
        if (!isset($errors['username']) && $users->isTaken('username', $username, $existing['id'] ?? null)) {
            $errors['username'] = 'Такой username уже занят.';
        }
        if (!isset($errors['email']) && $users->isTaken('email', $email, $existing['id'] ?? null)) {
            $errors['email'] = 'Такой email уже занят.';
        }

        if ($errors) {
            $this->flashError('Проверьте поля формы.');
            return $this->renderForm($existing, $errors, [
                'username'     => $username,
                'email'        => $email,
                'display_name' => $displayName,
                'role'         => $role,
                'is_active'    => $isActive,
            ]);
        }

        $payload = [
            'username'     => $username,
            'email'        => $email,
            'display_name' => $displayName !== '' ? $displayName : null,
            'role'         => $role,
            'is_active'    => $isActive,
        ];
        if ($password !== '') {
            $payload['password'] = $password;
        }

        if ($existing) {
            $users->update((int) $existing['id'], $payload);
            $this->flashSuccess('Пользователь обновлён.');
            return $this->redirect('/admin/users/' . (int) $existing['id'] . '/edit');
        }

        $newId = $users->create($payload);
        $this->flashSuccess('Пользователь создан.');
        return $this->redirect('/admin/users/' . $newId . '/edit');
    }

    private function notFound(): string
    {
        http_response_code(404);
        $this->flashError('Пользователь не найден.');
        return $this->redirect('/admin/users');
    }
}
