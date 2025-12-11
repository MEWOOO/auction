<?php
include("db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)($_GET['id'] ?? 0);

// Получаем товар
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if ($product) {
    // Удаляем фото
    if ($product['image'] && file_exists("uploads/" . $product['image'])) {
        unlink("uploads/" . $product['image']);
    }

    // Удаляем запись
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
}

header("Location: profile.php");
exit;
