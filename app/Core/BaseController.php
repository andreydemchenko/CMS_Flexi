<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Twig\Environment as Twig;

abstract class BaseController
{
    public function __construct(protected Container $container) {}

    // рендер Twig-шаблона из app/Views/themes/default
    protected function render(string $template, array $data = []): string
    {
        // .twig добавляем сами, если забыли
        if (!str_contains($template, '.')) {
            $template .= '.twig';
        } elseif (!str_ends_with($template, '.twig')) {
            $template = str_replace('.', '/', $template) . '.twig';
        }

        /** @var Twig $twig */
        $twig = $this->container->make(Twig::class);
        return $twig->render($template, $data);
    }

    // отдать JSON
    protected function renderJson(array $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    // ---- старый PHP-рендер (для уже существующих auth-вьюх) ----
    protected function view(string $view, array $data = []): string
    {
        $path = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;
        return (string) ob_get_clean();
    }

    protected function json(array $data, int $status = 200): string
    {
        return $this->renderJson($data, $status);
    }

    protected function redirect(string $url, int $status = 302): string
    {
        http_response_code($status);
        header('Location: ' . $url);
        return '';
    }
}
