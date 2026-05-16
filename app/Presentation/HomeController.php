<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Core\BaseController;

class HomeController extends BaseController
{
    public function index(): string
    {
        // пока без БД — 3 фейковых поста для проверки шаблона
        $posts = [
            [
                'slug'          => 'php-8-2-novosti',
                'title'         => 'PHP 8.2: что нового и зачем оно вам',
                'excerpt'       => 'Новые возможности PHP 8.2 и стоит ли уже мигрировать.',
                'image'         => '/assets/images/post-01.jpg',
                'category'      => 'Технологии',
                'category_slug' => 'tech',
                'author'        => 'Андрей Демченко',
                'date'          => '12 мая 2026',
            ],
            [
                'slug'          => 'mvc-bez-frameworka',
                'title'         => 'MVC своими руками без фреймворка',
                'excerpt'       => 'Простой контейнер, роутер и PDO — этого хватает на 90% задач.',
                'image'         => '/assets/images/post-02.jpg',
                'category'      => 'Разработка',
                'category_slug' => 'tech',
                'author'        => 'CMS-Flexi Team',
                'date'          => '08 мая 2026',
            ],
            [
                'slug'          => 'twig-vs-blade',
                'title'         => 'Twig против Blade: какой шаблонизатор выбрать',
                'excerpt'       => 'Сравнение синтаксиса, скорости и удобства поддержки.',
                'image'         => '/assets/images/post-03.jpg',
                'category'      => 'Гайды',
                'category_slug' => 'news',
                'author'        => 'Андрей Демченко',
                'date'          => '03 мая 2026',
            ],
        ];

        $popular = [
            ['slug' => 'php-8-2-novosti', 'title' => 'PHP 8.2: что нового', 'category' => 'Технологии', 'category_slug' => 'tech', 'date' => '12 мая', 'image' => '/assets/images/popular-post-01.jpg'],
            ['slug' => 'twig-vs-blade',   'title' => 'Twig против Blade',   'category' => 'Гайды',      'category_slug' => 'news', 'date' => '03 мая', 'image' => '/assets/images/popular-post-02.jpg'],
        ];

        return $this->render('home/index', [
            'posts'          => $posts,
            'popular_posts'  => $popular,
        ]);
    }
}
