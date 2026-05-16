<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class Router
{
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    public function __construct(private Container $container) {}

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, array $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function patch(string $pattern, array $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, array $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array $handler): void
    {
        $pattern = '/' . trim($pattern, '/');
        $params  = [];

        // {slug} -> ([^/]+) и запоминаем имена
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            function ($m) use (&$params) {
                $params[] = $m[1];
                return '([^/]+)';
            },
            $pattern
        );

        $this->routes[$method][] = [
            'regex'   => '#^' . $regex . '$#',
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $method = strtoupper($method);
        $path   = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');

        if (!isset($this->routes[$method])) {
            return $this->notFound();
        }

        foreach ($this->routes[$method] as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches); // убираем полное совпадение
            $args = array_combine($route['params'], $matches) ?: [];

            [$class, $action] = $route['handler'];
            $controller = $this->container->make($class);

            if (!method_exists($controller, $action)) {
                throw new RuntimeException("Action {$class}::{$action} not found.");
            }

            return $controller->{$action}(...array_values($args));
        }

        return $this->notFound();
    }

    private function notFound(): mixed
    {
        http_response_code(404);
        echo '404 Not Found';
        return null;
    }
}
