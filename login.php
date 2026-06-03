<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireGuest();

$error = '';
$loginValue = '';
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginValue = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($loginValue === '' || $password === '') {
        $error = 'Введите логин и пароль';
    } else {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, password FROM users WHERE login = ?');
        $stmt->execute([$loginValue]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Неверный логин или пароль';
        } else {
            $_SESSION['user_id'] = (int)$user['id'];
            header('Location: /cabinet.php');
            exit;
        }
    }
}

$pageTitle = 'Вход — Учусь.РФ';
$bodyClass = 'page-auth';
require __DIR__ . '/includes/header.php';
?>
<section class="card auth-card slide-up">
    <div class="card-head">
        <h2>Вход</h2>
    </div>
    <p class="subtitle">Войдите в личный кабинет</p>
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= escape($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= escape($error) ?></div>
    <?php endif; ?>
    <form method="post" class="form">
        <div class="field">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" value="<?= escape($loginValue) ?>" autocomplete="username">
        </div>
        <div class="field">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Войти</button>
    </form>
    <p class="auth-switch">Еще не зарегистрированы? <a href="/register.php">Регистрация</a></p>
    <p class="auth-switch"><a href="/admin/login.php">Панель администратора</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
