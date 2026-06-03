<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Учусь.РФ';
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?= escape($pageTitle) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="<?= escape($bodyClass) ?>">
<div class="app-shell">
    <header class="site-header fade-in">
        <a href="<?= !empty($_SESSION['user_id']) ? '/cabinet.php' : '/login.php' ?>" class="logo-link">
            <img src="/assets/20220922_logo.jpg" alt="Учусь.РФ" class="logo">
        </a>
        <div class="header-text">
            <h1>Учусь.РФ</h1>
            <p>Дистанционное обучение</p>
        </div>
    </header>
    <main class="site-main">
