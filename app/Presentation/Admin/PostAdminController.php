<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Support\Slug;

class PostAdminController extends AdminController
{
    private const PER_PAGE = 15;
    private const STATUSES = ['draft', 'published', 'archived'];

    public function index(): string
    {
        $status     = $this->query('status');
        $categoryId = $this->query('category_id');

        $status     = is_string($status)     && in_array($status, self::STATUSES, true) ? $status : null;
        $categoryId = is_numeric($categoryId) ? (int) $categoryId : null;

        $page   = max(1, (int) ($this->query('page', 1) ?: 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $posts      = $this->container->make(Post::class);
        $categories = $this->container->make(Category::class);

        $rows  = $posts->getForAdmin($status, $categoryId, self::PER_PAGE, $offset);
        $total = $posts->countForAdmin($status, $categoryId);

        return $this->adminRender('posts/index', [
            'posts'      => $rows,
            'categories' => $categories->getAll(),
            'filter'     => ['status' => $status, 'category_id' => $categoryId],
            'pagination' => [
                'current'     => $page,
                'total'       => $total,
                'per_page'    => self::PER_PAGE,
                'total_pages' => (int) ceil($total / self::PER_PAGE),
            ],
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
        $post = $this->container->make(Post::class)->findById($id);
        if (!$post) {
            return $this->notFound();
        }
        return $this->renderForm($post);
    }

    public function update(int|string $id): string
    {
        $this->ensurePost();
        $id   = (int) $id;
        $post = $this->container->make(Post::class)->findById($id);
        if (!$post) {
            return $this->notFound();
        }
        return $this->save($post);
    }

    // soft delete — переводим в archived
    public function delete(int|string $id): string
    {
        $this->ensurePost();
        $id    = (int) $id;
        $posts = $this->container->make(Post::class);
        $post  = $posts->findById($id);
        if (!$post) {
            return $this->notFound();
        }
        $posts->update($id, ['status' => 'archived']);
        $this->flashSuccess('Запись отправлена в архив.');
        return $this->redirect('/admin/posts');
    }

    // ── helpers ───────────────────────────────────────────────────────

    private function renderForm(?array $post, array $errors = [], array $old = []): string
    {
        $categories = $this->container->make(Category::class)->getAll();
        $tags       = $this->container->make(Tag::class)->getAll();

        $selectedTagIds = [];
        if (!empty($old['tag_ids']) && is_array($old['tag_ids'])) {
            $selectedTagIds = array_map('intval', $old['tag_ids']);
        } elseif ($post) {
            $selectedTagIds = $this->container->make(Post::class)->getTagIds((int) $post['id']);
        }

        $data = $post ? array_merge($post, $old) : $old;

        return $this->adminRender('posts/form', [
            'post'             => $data,
            'is_edit'          => $post !== null,
            'categories'       => $categories,
            'tags'             => $tags,
            'selected_tag_ids' => $selectedTagIds,
            'statuses'         => self::STATUSES,
            'errors'           => $errors,
        ]);
    }

    private function save(?array $existing): string
    {
        $title       = $this->trimmed('title');
        $slugRaw     = $this->trimmed('slug');
        $excerpt     = $this->trimmed('excerpt');
        $content     = (string) $this->input('content', '');
        $categoryId  = $this->input('category_id');
        $status      = $this->trimmed('status') ?: 'draft';
        $publishedAt = $this->trimmed('published_at');
        $image       = $this->trimmed('featured_image');
        $tagIds      = (array) $this->input('tag_ids', []);

        $errors = [];
        if ($title === '' || mb_strlen($title) > 200) {
            $errors['title'] = 'Заголовок обязателен (до 200 символов).';
        }
        if (mb_strlen($excerpt) > 500) {
            $errors['excerpt'] = 'Слишком длинное описание.';
        }
        if ($content === '') {
            $errors['content'] = 'Тело записи обязательно.';
        }
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Некорректный статус.';
        }
        if ($publishedAt !== '' && !strtotime($publishedAt)) {
            $errors['published_at'] = 'Дата публикации в неверном формате.';
        }
        $categoryId = is_numeric($categoryId) && (int) $categoryId > 0 ? (int) $categoryId : null;

        $posts = $this->container->make(Post::class);

        $slug = $slugRaw !== '' ? Slug::make($slugRaw) : Slug::make($title);
        if ($slug === '') {
            $errors['slug'] = 'Не удалось сформировать slug.';
        } elseif ($posts->slugExists($slug, $existing['id'] ?? null)) {
            $errors['slug'] = 'Такой slug уже используется.';
        }

        if ($errors) {
            $this->flashError('Проверьте поля формы.');
            return $this->renderForm($existing, $errors, [
                'title'          => $title,
                'slug'           => $slugRaw,
                'excerpt'        => $excerpt,
                'content'        => $content,
                'category_id'    => $categoryId,
                'status'         => $status,
                'published_at'   => $publishedAt,
                'featured_image' => $image,
                'tag_ids'        => $tagIds,
            ]);
        }

        $publishedAtSql = $publishedAt !== '' ? date('Y-m-d H:i:s', (int) strtotime($publishedAt)) : null;
        if ($publishedAtSql === null && $status === 'published') {
            $publishedAtSql = date('Y-m-d H:i:s');
        }

        $payload = [
            'title'          => $title,
            'slug'           => $slug,
            'excerpt'        => $excerpt !== '' ? $excerpt : null,
            'content'        => $content,
            'category_id'    => $categoryId,
            'status'         => $status,
            'published_at'   => $publishedAtSql,
            'featured_image' => $image !== '' ? $image : null,
            'tag_ids'        => array_map('intval', $tagIds),
        ];

        if ($existing) {
            $posts->update((int) $existing['id'], $payload);
            $this->flashSuccess('Запись сохранена.');
            return $this->redirect('/admin/posts/' . (int) $existing['id'] . '/edit');
        }

        $payload['author_id'] = (int) ($this->auth->user()['id'] ?? 0);
        if ($payload['author_id'] <= 0) {
            $this->flashError('Не удалось определить автора.');
            return $this->redirect('/admin/posts');
        }
        $newId = $posts->create($payload);
        $this->flashSuccess('Запись создана.');
        return $this->redirect('/admin/posts/' . $newId . '/edit');
    }

    private function notFound(): string
    {
        http_response_code(404);
        return $this->adminRender('posts/index', [
            'posts'      => [],
            'categories' => [],
            'filter'     => [],
            'pagination' => ['current' => 1, 'total' => 0, 'per_page' => self::PER_PAGE, 'total_pages' => 0],
            'flash'      => ['type' => 'error', 'message' => 'Запись не найдена.'],
        ]);
    }
}
