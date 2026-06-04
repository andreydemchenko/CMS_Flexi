<?php

declare(strict_types=1);

namespace App\Support;

// CSRF-токен: один на сессию, регенерируется по запросу
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const FIELD       = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function check(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION[self::SESSION_KEY])
            && hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    // вызывать в начале каждого POST-обработчика; при провале отдаёт 419 и завершает запрос
    public static function abortIfInvalid(): void
    {
        $token = $_POST[self::FIELD] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::check(is_string($token) ? $token : null)) {
            http_response_code(419);
            echo '<!doctype html><meta charset="utf-8"><h1 style="font-family:sans-serif;text-align:center;margin-top:80px">419 CSRF token mismatch</h1>';
            exit;
        }
    }

    public static function field(): string
    {
        return self::FIELD;
    }

    // одноразовая ротация после login/logout, чтобы старый токен не работал
    public static function rotate(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
}
