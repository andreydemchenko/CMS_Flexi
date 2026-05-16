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
    <title>Вход — CMS-Flexi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-body-tertiary">
<main class="container py-5" style="max-width: 420px;">
    <h1 class="h3 mb-4 text-center">Вход</h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= $e((string)$err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="/login" class="card card-body shadow-sm">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= $e((string)($old['email'] ?? '')) ?>" required autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Пароль</label>
            <input type="password" id="password" name="password" class="form-control"
                   minlength="8" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Войти</button>
    </form>

    <p class="mt-3 text-center small text-muted">
        Нет аккаунта? <a href="/register">Зарегистрироваться</a>
    </p>
</main>
</body>
</html>
