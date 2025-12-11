<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Нет ID товара");
}

$user_id = intval($_SESSION['user_id']);
$product_id = intval($_GET['id']);

// Проверяем, есть ли уже в избранном
$check = $conn->query("SELECT id FROM favorites WHERE user_id=$user_id AND product_id=$product_id");

if ($check->num_rows > 0) {
    // Удаляем
    $conn->query("DELETE FROM favorites WHERE user_id=$user_id AND product_id=$product_id");
} else {
    // Добавляем
    $conn->query("INSERT INTO favorites (user_id, product_id) VALUES ($user_id, $product_id)");
}

header("Location: auction.php?id=".$product_id);
exit;
?>
