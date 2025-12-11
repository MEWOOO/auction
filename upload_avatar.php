<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Неавторизованный доступ";
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024;

    if (!in_array($file['type'], $allowedTypes)) {
        echo "Неверный формат файла. Допустимо: JPG, PNG, WEBP.";
        exit;
    }

    if ($file['size'] > $maxSize) {
        echo "Файл слишком большой. Максимум 2MB.";
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
    $uploadDir = 'uploads/avatars/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $uploadPath = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $uploadPath, $user_id);
        $stmt->execute();
        // Возвращаем путь к новому аватару
        echo $uploadPath;
    } else {
        echo "Ошибка при загрузке файла.";
    }
} else {
    echo "Файл не выбран или произошла ошибка.";
}
?>
