<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Core\BaseController;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;

class PostController extends BaseController
{
    private const PER_PAGE = 10;

    // GET /posts — список с переключателем макета
    public function index(): string
    {
        $page   = $this->currentPage();
        $layout = $this->currentLayout();

        $postModel = $this->container->make(Post::class);

        $offset = ($page - 1) * self::PER_PAGE;
        $posts  = $postModel->getAll('published', self::PER_PAGE, $offset);
        $total  = $postModel->getTotalCount('published');

        return $this->render('post/list', [
            'posts'         => $posts,
            'layout'        => $layout,
            'page_title'    => 'Все публикации',
            'pagination'    => $this->buildPagination($total, $page, '/posts', ['layout' => $layout]),
            'sidebar_data'  => $this->sidebarData(),
        ]);
    }

    // GET /post/{slug}
    public function show(string $slug): string
    {
        $postModel = $this->container->make(Post::class);
        $post      = $postModel->findBySlug($slug);

        if (!$post || $post['status'] !== 'published') {
            http_response_code(404);
            return $this->render('errors/404', ['message' => 'Запись не найдена']);
        }

        $postModel->incrementViews((int) $post['id']);

        $tagModel     = $this->container->make(Tag::class);
        $commentModel = $this->container->make(Comment::class);

        $post['tags']     = $tagModel->getByPostId((int) $post['id']);
        $comments         = $commentModel->getByPostId((int) $post['id']);
        $commentsCount    = $commentModel->countByPost((int) $post['id']);

        return $this->render('post/show', [
            'post'           => $post,
            'comments'       => $comments,
            'comments_count' => $commentsCount,
            'sidebar_data'   => $this->sidebarData(),
        ]);
    }

    // GET /category/{slug}
    public function byCategory(string $slug): string
    {
        $categoryModel = $this->container->make(Category::class);
        $category      = $categoryModel->findBySlug($slug);

        if (!$category) {
            http_response_code(404);
            return $this->render('errors/404', ['message' => 'Категория не найдена']);
        }

        $page   = $this->currentPage();
        $layout = $this->currentLayout();
        $offset = ($page - 1) * self::PER_PAGE;

        $postModel = $this->container->make(Post::class);
        $posts     = $postModel->getByCategory((int) $category['id'], self::PER_PAGE, $offset);
        $total     = $postModel->getCountByCategory((int) $category['id']);

        return $this->render('post/list', [
            'posts'        => $posts,
            'layout'       => $layout,
            'page_title'   => $category['name'],
            'subtitle'     => 'Записи в категории',
            'category'     => $category,
            'pagination'   => $this->buildPagination(
                $total, $page, '/category/' . $category['slug'], ['layout' => $layout]
            ),
            'sidebar_data' => $this->sidebarData(),
        ]);
    }

    // GET /tag/{slug}
    public function byTag(string $slug): string
    {
        $tagModel = $this->container->make(Tag::class);
        $tag      = $tagModel->findBySlug($slug);

        if (!$tag) {
            http_response_code(404);
            return $this->render('errors/404', ['message' => 'Тег не найден']);
        }

        $page   = $this->currentPage();
        $layout = $this->currentLayout();
        $offset = ($page - 1) * self::PER_PAGE;

        $postModel = $this->container->make(Post::class);
        $posts     = $postModel->getByTag((int) $tag['id'], self::PER_PAGE, $offset);
        $total     = $postModel->getCountByTag((int) $tag['id']);

        return $this->render('post/list', [
            'posts'        => $posts,
            'layout'       => $layout,
            'page_title'   => '#' . $tag['name'],
            'subtitle'     => 'Записи с тегом',
            'tag'          => $tag,
            'pagination'   => $this->buildPagination(
                $total, $page, '/tag/' . $tag['slug'], ['layout' => $layout]
            ),
            'sidebar_data' => $this->sidebarData(),
        ]);
    }

    // ─── helpers ───

    private function currentPage(): int
    {
        $page = (int) ($_GET['page'] ?? 1);
        return $page < 1 ? 1 : $page;
    }

    private function currentLayout(): string
    {
        $layout = $_GET['layout'] ?? 'grid';
        return in_array($layout, ['grid', 'list', 'left'], true) ? $layout : 'grid';
    }

    private function buildPagination(int $total, int $current, string $baseUrl, array $extra = []): array
    {
        $totalPages = (int) ceil($total / self::PER_PAGE);
        $current    = max(1, min($current, max(1, $totalPages)));

        return [
            'total'        => $total,
            'per_page'     => self::PER_PAGE,
            'current'      => $current,
            'total_pages'  => $totalPages,
            'has_prev'     => $current > 1,
            'has_next'     => $current < $totalPages,
            'base_url'     => $baseUrl,
            'query_extra'  => $extra,
        ];
    }

    // данные для сайдбара — категории, популярное, теги
    private function sidebarData(): array
    {
        $categoryModel = $this->container->make(Category::class);
        $postModel     = $this->container->make(Post::class);
        $tagModel      = $this->container->make(Tag::class);

        return [
            'categories' => $categoryModel->getAll(),
            'popular'    => $postModel->getPopular(4),
            'tags'       => $tagModel->getAll(),
        ];
    }
}
