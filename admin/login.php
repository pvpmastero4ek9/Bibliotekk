<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!empty($_SESSION['admin'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === 'Admin26' && $password === 'Demo20') {
        $_SESSION['admin'] = true;
        header('Location: /admin/index.php');
        exit;
    }
    $error = 'Неверный логин или пароль администратора';
}

$pageTitle = 'Администратор — Учусь.РФ';
$bodyClass = 'page-auth page-admin-login';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="card auth-card slide-up">
    <h2>Панель администратора</h2>
    <p class="subtitle">Вход для администратора системы</p>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= escape($error) ?></div>
    <?php endif; ?>
    <form method="post" class="form">
        <div class="field">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" autocomplete="username">
        </div>
        <div class="field">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Войти</button>
    </form>
    <p class="auth-switch"><a href="/login.php">На главную</a></p>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
