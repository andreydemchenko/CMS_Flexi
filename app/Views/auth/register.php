<?php
/** @var array $errors */
/** @var array $old */
$errors ??= [];
$old    ??= [];
$e = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация — CMS-Flexi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-body-tertiary">
<main class="container py-5" style="max-width: 480px;">
    <h1 class="h3 mb-4 text-center">Регистрация</h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= $e((string)$err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="/register" class="card card-body shadow-sm">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" id="username" name="username" class="form-control"
                   value="<?= $e((string)($old['username'] ?? '')) ?>"
                   minlength="3" maxlength="64" required autofocus>
            <div class="form-text">Латиница, цифры, символы _ . -</div>
        </div>

        <div class="mb-3">
            <label for="display_name" class="form-label">Имя для отображения <span class="text-muted">(необязательно)</span></label>
            <input type="text" id="display_name" name="display_name" class="form-control"
                   value="<?= $e((string)($old['display_name'] ?? '')) ?>" maxlength="120">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= $e((string)($old['email'] ?? '')) ?>" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Пароль</label>
            <input type="password" id="password" name="password" class="form-control"
                   minlength="8" required>
            <div class="form-text">Минимум 8 символов</div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Создать аккаунт</button>
    </form>

    <p class="mt-3 text-center small text-muted">
        Уже есть аккаунт? <a href="/login">Войти</a>
    </p>
</main>
</body>
</html>
