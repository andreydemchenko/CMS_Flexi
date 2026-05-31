<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Core\BaseController;
use App\Models\Comment;
use App\Models\Post;

class CommentController extends BaseController
{
    // POST /post/{slug}/comment
    public function store(string $slug): string
    {
        $postModel = $this->container->make(Post::class);
        $post      = $postModel->findBySlug($slug);

        if (!$post) {
            http_response_code(404);
            return $this->render('errors/404', ['message' => 'Запись не найдена']);
        }

        $authorName  = trim((string) ($_POST['author_name']  ?? ''));
        $authorEmail = trim((string) ($_POST['author_email'] ?? ''));
        $content     = trim((string) ($_POST['content']      ?? ''));
        $parentIdRaw = $_POST['parent_id'] ?? null;
        $parentId    = is_numeric($parentIdRaw) && (int) $parentIdRaw > 0 ? (int) $parentIdRaw : null;

        $userId = $_SESSION['user_id'] ?? null;
        $userId = is_numeric($userId) ? (int) $userId : null;

        $errors = [];
        if ($userId === null) {
            if ($authorName === '' || mb_strlen($authorName) > 120) {
                $errors['author_name'] = 'Укажите имя (до 120 символов).';
            }
            if ($authorEmail === '' || !filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['author_email'] = 'Укажите корректный email.';
            }
        }
        if (mb_strlen($content) < 3) {
            $errors['content'] = 'Комментарий слишком короткий.';
        }
        if (mb_strlen($content) > 4000) {
            $errors['content'] = 'Комментарий слишком длинный.';
        }

        if ($errors) {
            $_SESSION['flash']['comment'] = ['type' => 'error', 'errors' => $errors];
            $_SESSION['old']['comment']   = compact('author_name', 'author_email', 'content');
            return $this->redirect('/post/' . $slug . '#comment-form');
        }

        $commentModel = $this->container->make(Comment::class);
        $commentModel->create([
            'post_id'      => (int) $post['id'],
            'user_id'      => $userId,
            'parent_id'    => $parentId,
            'author_name'  => $userId ? null : $authorName,
            'author_email' => $userId ? null : $authorEmail,
            'content'      => $content,
            'status'       => 'pending',
        ]);

        $_SESSION['flash']['comment'] = [
            'type'    => 'success',
            'message' => 'Спасибо! Комментарий отправлен и появится после модерации.',
        ];

        return $this->redirect('/post/' . $slug . '#comments');
    }
}
