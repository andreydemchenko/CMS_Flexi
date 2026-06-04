<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use App\Models\Comment;

class CommentAdminController extends AdminController
{
    private const PER_PAGE = 20;
    private const STATUSES = ['pending', 'approved', 'spam', 'trash'];

    public function index(): string
    {
        $status = $this->query('status');
        $status = is_string($status) && in_array($status, self::STATUSES, true) ? $status : null;

        $page   = max(1, (int) ($this->query('page', 1) ?: 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $comments = $this->container->make(Comment::class);

        $rows  = $comments->getForAdmin($status, self::PER_PAGE, $offset);
        $total = $comments->countForAdmin($status);

        return $this->adminRender('comments/index', [
            'comments'   => $rows,
            'filter'     => ['status' => $status],
            'statuses'   => self::STATUSES,
            'pagination' => [
                'current'     => $page,
                'total'       => $total,
                'per_page'    => self::PER_PAGE,
                'total_pages' => (int) ceil($total / self::PER_PAGE),
            ],
            'counts'     => [
                'pending'  => $comments->countForAdmin('pending'),
                'approved' => $comments->countForAdmin('approved'),
                'spam'     => $comments->countForAdmin('spam'),
                'trash'    => $comments->countForAdmin('trash'),
                'all'      => $comments->totalCount(),
            ],
        ]);
    }

    public function approve(int|string $id): string
    {
        $this->ensurePost();
        $id       = (int) $id;
        $comments = $this->container->make(Comment::class);
        if (!$comments->findById($id)) {
            $this->flashError('Комментарий не найден.');
            return $this->redirect('/admin/comments');
        }
        $comments->approve($id);
        $this->flashSuccess('Комментарий одобрен.');
        return $this->redirect($this->backUrl());
    }

    public function delete(int|string $id): string
    {
        $this->ensurePost();
        $id       = (int) $id;
        $comments = $this->container->make(Comment::class);
        if (!$comments->findById($id)) {
            $this->flashError('Комментарий не найден.');
            return $this->redirect('/admin/comments');
        }
        $comments->delete($id);
        $this->flashSuccess('Комментарий удалён.');
        return $this->redirect($this->backUrl());
    }

    private function backUrl(): string
    {
        $back = $_POST['_back'] ?? '/admin/comments';
        return is_string($back) && str_starts_with($back, '/admin') ? $back : '/admin/comments';
    }
}
