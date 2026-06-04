<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

class DashboardController extends AdminController
{
    public function index(): string
    {
        $posts      = $this->container->make(Post::class);
        $comments   = $this->container->make(Comment::class);
        $users      = $this->container->make(User::class);
        $categories = $this->container->make(Category::class);

        $stats = [
            'posts_total'      => $posts->getTotalCountAny(),
            'posts_published'  => $posts->getTotalCount('published'),
            'posts_draft'      => $posts->getTotalCount('draft'),
            'comments_total'   => $comments->totalCount(),
            'comments_pending' => $comments->countForAdmin('pending'),
            'users_total'      => $users->totalCount(),
            'categories_total' => count($categories->getAll()),
        ];

        return $this->adminRender('dashboard/index', [
            'stats'           => $stats,
            'recent_comments' => $comments->getRecent(5),
        ]);
    }
}
