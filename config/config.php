<?php

declare(strict_types=1);

return [
    // настройки приложения
    'app' => [
        'name'     => 'CMS-Flexi',
        'env'      => 'development',
        'debug'    => true,
        'base_url' => 'http://localhost',
        'charset'  => 'UTF-8',
        'timezone' => 'UTC',
    ],

    // настройки БД
    'database' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'cms_flexi',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'collation'=> 'utf8mb4_unicode_ci',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],

    // безопасность / пароли
    'security' => [
        'password_algo' => PASSWORD_BCRYPT,
        'password_cost' => 12,
    ],
];
