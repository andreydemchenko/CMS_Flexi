<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use App\Models\Tag;
use App\Support\Slug;

class TagAdminController extends AdminController
{
    public function index(): string
    {
        $tags = $this->container->make(Tag::class);

        $editId  = (int) ($this->query('edit', 0) ?: 0);
        $editing = $editId > 0 ? $tags->findById($editId) : null;

        return $this->adminRender('tags/index', [
            'tags'    => $tags->getAll(),
            'editing' => $editing,
            'errors'  => $_SESSION['flash_errors']['tag'] ?? [],
            'old'     => $_SESSION['flash_old']['tag'] ?? [],
        ]);
    }

    public function store(): string
    {
        $this->ensurePost();
        return $this->save(null);
    }

    public function update(int|string $id): string
    {
        $this->ensurePost();
        $id  = (int) $id;
        $tag = $this->container->make(Tag::class)->findById($id);
        if (!$tag) {
            $this->flashError('Тег не найден.');
            return $this->redirect('/admin/tags');
        }
        return $this->save($tag);
    }

    public function delete(int|string $id): string
    {
        $this->ensurePost();
        $id   = (int) $id;
        $tags = $this->container->make(Tag::class);
        if (!$tags->findById($id)) {
            $this->flashError('Тег не найден.');
            return $this->redirect('/admin/tags');
        }
        $tags->delete($id);
        $this->flashSuccess('Тег удалён.');
        return $this->redirect('/admin/tags');
    }

    private function save(?array $existing): string
    {
        $name    = $this->trimmed('name');
        $slugRaw = $this->trimmed('slug');

        $errors = [];
        if ($name === '' || mb_strlen($name) > 80) {
            $errors['name'] = 'Имя обязательно (до 80 символов).';
        }
        $slug = $slugRaw !== '' ? Slug::make($slugRaw, 100) : Slug::make($name, 100);
        if ($slug === '') {
            $errors['slug'] = 'Не удалось сформировать slug.';
        }

        if ($errors) {
            $_SESSION['flash_errors']['tag'] = $errors;
            $_SESSION['flash_old']['tag']    = ['name' => $name, 'slug' => $slugRaw];
            $this->flashError('Проверьте поля формы.');
            return $this->redirect('/admin/tags' . ($existing ? '?edit=' . (int) $existing['id'] : ''));
        }

        unset($_SESSION['flash_errors']['tag'], $_SESSION['flash_old']['tag']);

        $tags = $this->container->make(Tag::class);
        if ($existing) {
            $tags->update((int) $existing['id'], ['name' => $name, 'slug' => $slug]);
            $this->flashSuccess('Тег обновлён.');
        } else {
            $tags->create(['name' => $name, 'slug' => $slug]);
            $this->flashSuccess('Тег создан.');
        }
        return $this->redirect('/admin/tags');
    }
}
