<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireGuest();

$errors = [];
$values = [
    'login' => '',
    'full_name' => '',
    'phone' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['login'] = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $values['full_name'] = trim($_POST['full_name'] ?? '');
    $values['phone'] = trim($_POST['phone'] ?? '');
    $values['email'] = trim($_POST['email'] ?? '');

    $fieldErrors = [
        'login' => validateLogin($values['login']),
        'password' => validatePassword($password),
        'full_name' => validateFullName($values['full_name']),
        'phone' => validatePhone($values['phone']),
        'email' => validateEmail($values['email']),
    ];

    if ($password !== $passwordConfirm) {
        $fieldErrors['password_confirm'] = 'Пароли не совпадают';
    }

    foreach ($fieldErrors as $field => $message) {
        if ($message !== null) {
            $errors[$field] = $message;
        }
    }

    if (!$errors) {
        $db = Database::getConnection();
        $exists = $db->prepare('SELECT id FROM users WHERE login = ?');
        $exists->execute([$values['login']]);
        if ($exists->fetch()) {
            $errors['login'] = 'Логин уже занят, выберите другой';
        } else {
            $stmt = $db->prepare('INSERT INTO users (login, password, full_name, phone, email) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $values['login'],
                password_hash($password, PASSWORD_DEFAULT),
                $values['full_name'],
                $values['phone'],
                $values['email'],
            ]);
            $_SESSION['flash_success'] = 'Регистрация успешна. Войдите в систему.';
            header('Location: /login.php');
            exit;
        }
    }
}

$pageTitle = 'Регистрация — Учусь.РФ';
$bodyClass = 'page-auth';
require __DIR__ . '/includes/header.php';
?>
<section class="card auth-card slide-up">
    <h2>Регистрация</h2>
    <p class="subtitle">Создайте аккаунт для записи на курсы</p>
    <form method="post" class="form" novalidate>
        <div class="field">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" value="<?= escape($values['login']) ?>" autocomplete="username">
            <?php if (!empty($errors['login'])): ?><span class="field-error"><?= escape($errors['login']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" autocomplete="new-password">
            <?php if (!empty($errors['password'])): ?><span class="field-error"><?= escape($errors['password']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="password_confirm">Подтверждение пароля</label>
            <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password">
            <?php if (!empty($errors['password_confirm'])): ?><span class="field-error"><?= escape($errors['password_confirm']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="full_name">ФИО</label>
            <input type="text" id="full_name" name="full_name" value="<?= escape($values['full_name']) ?>">
            <?php if (!empty($errors['full_name'])): ?><span class="field-error"><?= escape($errors['full_name']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="phone">Телефон</label>
            <input type="tel" id="phone" name="phone" value="<?= escape($values['phone']) ?>" placeholder="+7 (___) ___-__-__">
            <?php if (!empty($errors['phone'])): ?><span class="field-error"><?= escape($errors['phone']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= escape($values['email']) ?>">
            <?php if (!empty($errors['email'])): ?><span class="field-error"><?= escape($errors['email']) ?></span><?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
    </form>
    <p class="auth-switch">Уже есть аккаунт? <a href="/login.php">Вход</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
