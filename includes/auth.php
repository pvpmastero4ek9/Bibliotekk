<?php

declare(strict_types=1);

session_start();

function requireUser(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function requireGuest(): void
{
    if (!empty($_SESSION['user_id'])) {
        header('Location: /cabinet.php');
        exit;
    }
}

function requireAdmin(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function currentUserId(): int
{
    return (int)$_SESSION['user_id'];
}

function validateLogin(string $login): ?string
{
    if (!preg_match('/^[a-zA-Z0-9]{6,}$/', $login)) {
        return 'Логин должен содержать латинские буквы и цифры, минимум 6 символов';
    }
    return null;
}

function validatePassword(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Пароль должен содержать не менее 8 символов';
    }
    return null;
}

function validateEmail(string $email): ?string
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Укажите корректный e-mail';
    }
    return null;
}

function validatePhone(string $phone): ?string
{
    if (!preg_match('/^\+?[0-9\s\-()]{10,20}$/', $phone)) {
        return 'Укажите корректный номер телефона';
    }
    return null;
}

function utf8Length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }
    return preg_match_all('/./u', $value, $matches) ? count($matches[0]) : strlen($value);
}

function statusClassSlug(string $status): string
{
    $map = [
        'Новая' => 'новая',
        'Идет обучение' => 'идет-обучение',
        'Обучение завершено' => 'обучение-завершено',
    ];
    if (isset($map[$status])) {
        return $map[$status];
    }
    $normalized = function_exists('mb_strtolower')
        ? mb_strtolower($status, 'UTF-8')
        : $status;
    return preg_replace('/\s+/', '-', $normalized) ?? $normalized;
}

function validateFullName(string $name): ?string
{
    if (utf8Length(trim($name)) < 3) {
        return 'Укажите полное ФИО';
    }
    return null;
}

function validateDate(string $date): ?string
{
    if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
        return 'Дата должна быть в формате ДД.ММ.ГГГГ';
    }
    [$d, $m, $y] = array_map('intval', explode('.', $date));
    if (!checkdate($m, $d, $y)) {
        return 'Указана некорректная дата';
    }
    return null;
}

function dateToStorage(string $date): string
{
    [$d, $m, $y] = explode('.', $date);
    return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
}

function dateFromStorage(string $date): string
{
    $parts = explode('-', $date);
    if (count($parts) !== 3) {
        return $date;
    }
    return sprintf('%02d.%02d.%04d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
