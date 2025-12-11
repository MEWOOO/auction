<?php
// Проверка, является ли пользователь администратором
session_start();
include_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Проверяем, является ли пользователь администратором
$stmt = $conn->prepare("SELECT is_admin, is_blocked FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Проверяем, не заблокирован ли пользователь
if ($user['is_blocked'] == 1) {
    session_destroy();
    echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='utf-8'>
    <title>Доступ заблокирован</title>
    <link rel='stylesheet' href='style.css'>
</head>
<body>
    <div style='text-align: center; margin-top: 100px;'>
        <h2>Ваш аккаунт заблокирован</h2>
        <p>Обратитесь к администратору для разблокировки.</p>
        <a href='index.php'>На главную</a>
    </div>
</body>
</html>";
    exit();
}

// Проверяем права администратора
if ($user['is_admin'] != 1) {
    header('Location: index.php');
    exit();
}
?>