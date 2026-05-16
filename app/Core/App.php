<?php

declare(strict_types=1);

namespace App\Core;

class App
{
    public function __construct(
        private Container $container,
        private Router $router,
    ) {}

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }

    // запуск
    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI']    ?? '/';

        $output = $this->router->dispatch($method, $uri);

        if (is_string($output)) {
            echo $output;
        }
    }
}
