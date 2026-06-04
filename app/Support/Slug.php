<?php

declare(strict_types=1);

namespace App\Support;

// генерация slug из строки на русском/латинице
final class Slug
{
    private const TRANSLIT = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh',
        'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
        'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c',
        'ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu',
        'я'=>'ya',
    ];

    public static function make(string $text, int $maxLength = 200): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, self::TRANSLIT);
        $text = preg_replace('/[^a-z0-9\-_]+/u', '-', $text) ?? '';
        $text = preg_replace('/-+/', '-', $text) ?? '';
        $text = trim($text, '-');

        if ($text === '') {
            $text = 'item-' . substr(bin2hex(random_bytes(4)), 0, 6);
        }

        return mb_substr($text, 0, $maxLength, 'UTF-8');
    }
}
