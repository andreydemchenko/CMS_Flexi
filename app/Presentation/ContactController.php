<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Core\BaseController;
use App\Core\Database;

class ContactController extends BaseController
{
    public function show(): string
    {
        return $this->render('contact/index', [
            'flash' => $this->popFlash(),
            'old'   => $this->popOld(),
        ]);
    }

    public function send(): string
    {
        $name    = trim((string) ($_POST['name']    ?? ''));
        $email   = trim((string) ($_POST['email']   ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Укажите имя (до 120 символов).';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Укажите корректный email.';
        }
        if (mb_strlen($subject) > 200) {
            $errors['subject'] = 'Тема слишком длинная.';
        }
        if (mb_strlen($message) < 5) {
            $errors['message'] = 'Сообщение слишком короткое.';
        }

        if ($errors) {
            $_SESSION['flash']['contact'] = ['type' => 'error', 'errors' => $errors];
            $_SESSION['old']['contact']   = compact('name', 'email', 'subject', 'message');
            return $this->redirect('/contact');
        }

        // если в БД есть таблица contact_messages — пишем туда; иначе просто принимаем
        try {
            $db = $this->container->make(Database::class);
            $tableExists = $db->fetchOne(
                "SELECT 1 AS x FROM information_schema.tables
                  WHERE table_schema = DATABASE() AND table_name = 'contact_messages' LIMIT 1"
            );
            if ($tableExists) {
                $db->execute(
                    'INSERT INTO contact_messages (name, email, subject, message, created_at)
                     VALUES (?, ?, ?, ?, NOW())',
                    [$name, $email, $subject ?: null, $message]
                );
            }
        } catch (\Throwable $e) {
            // не блокируем пользователя на инфраструктурных ошибках
        }

        $_SESSION['flash']['contact'] = [
            'type'    => 'success',
            'message' => 'Спасибо! Сообщение отправлено — мы ответим в течение рабочего дня.',
        ];

        return $this->redirect('/contact');
    }

    private function popFlash(): ?array
    {
        $flash = $_SESSION['flash']['contact'] ?? null;
        unset($_SESSION['flash']['contact']);
        return $flash;
    }

    private function popOld(): array
    {
        $old = $_SESSION['old']['contact'] ?? [];
        unset($_SESSION['old']['contact']);
        return $old;
    }
}
