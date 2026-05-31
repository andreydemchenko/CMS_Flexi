<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Core\BaseController;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;

class HomeController extends BaseController
{
    public function index(): string
    {
        $postModel     = $this->container->make(Post::class);
        $categoryModel = $this->container->make(Category::class);
        $tagModel      = $this->container->make(Tag::class);

        $recent  = $postModel->getRecent(6);
        $popular = $postModel->getPopular(4);

        return $this->render('home/index', [
            'posts'         => $recent,
            'popular_posts' => $popular,
            'sidebar_data'  => [
                'categories' => $categoryModel->getAll(),
                'popular'    => $popular,
                'tags'       => $tagModel->getAll(),
            ],
        ]);
    }
}
