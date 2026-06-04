<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use App\Models\Category;
use App\Support\Slug;

class CategoryAdminController extends AdminController
{
    public function index(): string
    {
        $categories = $this->container->make(Category::class);

        // ?edit=ID — предзаполняем форму справа для редактирования
        $editId   = (int) ($this->query('edit', 0) ?: 0);
        $editing  = $editId > 0 ? $categories->findById($editId) : null;

        return $this->adminRender('categories/index', [
            'categories'    => $categories->getAll(),
            'editing'       => $editing,
            'all_for_parent'=> $categories->getAll(),
            'errors'        => $_SESSION['flash_errors']['category'] ?? [],
            'old'           => $_SESSION['flash_old']['category'] ?? [],
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
        $cat = $this->container->make(Category::class)->findById($id);
        if (!$cat) {
            $this->flashError('Категория не найдена.');
            return $this->redirect('/admin/categories');
        }
        return $this->save($cat);
    }

    public function delete(int|string $id): string
    {
        $this->ensurePost();
        $id  = (int) $id;
        $cat = $this->container->make(Category::class);
        if (!$cat->findById($id)) {
            $this->flashError('Категория не найдена.');
            return $this->redirect('/admin/categories');
        }
        $cat->delete($id);
        $this->flashSuccess('Категория удалена.');
        return $this->redirect('/admin/categories');
    }

    private function save(?array $existing): string
    {
        $name        = $this->trimmed('name');
        $slugRaw     = $this->trimmed('slug');
        $description = $this->trimmed('description');
        $parentRaw   = $this->input('parent_id');
        $parentId    = is_numeric($parentRaw) && (int) $parentRaw > 0 ? (int) $parentRaw : null;

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Имя обязательно (до 120 символов).';
        }
        $slug = $slugRaw !== '' ? Slug::make($slugRaw) : Slug::make($name);
        if ($slug === '') {
            $errors['slug'] = 'Не удалось сформировать slug.';
        }

        if ($existing && $parentId === (int) $existing['id']) {
            $errors['parent_id'] = 'Категория не может быть родителем самой себе.';
        }

        if ($errors) {
            $_SESSION['flash_errors']['category'] = $errors;
            $_SESSION['flash_old']['category']    = compact('name', 'slugRaw', 'description', 'parentId') + ['slug' => $slugRaw];
            $this->flashError('Проверьте поля формы.');
            return $this->redirect('/admin/categories' . ($existing ? '?edit=' . (int) $existing['id'] : ''));
        }

        unset($_SESSION['flash_errors']['category'], $_SESSION['flash_old']['category']);

        $payload = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description !== '' ? $description : null,
            'parent_id'   => $parentId,
        ];

        $cats = $this->container->make(Category::class);
        if ($existing) {
            $cats->update((int) $existing['id'], $payload);
            $this->flashSuccess('Категория обновлена.');
        } else {
            $cats->create($payload);
            $this->flashSuccess('Категория создана.');
        }

        return $this->redirect('/admin/categories');
    }
}
