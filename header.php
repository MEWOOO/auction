<?php
// header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("db.php"); 
include_once("wallet_functions.php"); // ← исправлено

// Баланс пользователя для хедера
$header_balance = 0;
if (isset($_SESSION['user_id'])) {
    $header_balance = getBalance($_SESSION['user_id'], $conn);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аукцион ретро-товаров</title>

    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="new-header">
    <div class="header-left">
        <a href="index.php" class="logo-block">
            <img src="images/logo.png" class="logo-img" alt="logo">
            <span class="logo-text">Аукцион ретро-товаров</span>
        </a>
    </div>

    <nav class="header-nav">
        <a href="index.php">Главная</a>
        <a href="catalog.php">Аукционы</a>
        <a href="add_product.php">Добавить лот</a>
        <a href="dialogs.php">Чаты</a>
    </nav>

    <div class="header-right">

        <?php if (isset($_SESSION['user_id'])): ?>

            <!-- Кошелёк в хедере -->
            <a href="wallet.php" class="wallet-header">
                <img src="images/wallet-icon.png" class="wallet-icon" alt="wallet">
                <span class="wallet-money"><?= number_format($header_balance, 2) ?> ₽</span>
            </a>

            <!-- Иконка профиля -->
            <a href="profile.php" class="profile-icon">
                <img src="<?= $_SESSION['avatar'] ?? 'images/user-icon.png' ?>" alt="Профиль">
            </a>

        <?php else: ?>

            <a href="login.php" class="btn-login">Войти</a>
            <a href="register.php" class="btn-register">Регистрация</a>

        <?php endif; ?>

    </div>
</header>
