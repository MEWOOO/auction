<?php
include 'admin_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit();
}

$action = $_POST['action'] ?? '';
$message = '';

switch ($action) {
    case 'block_user':
        $user_id = (int)$_POST['user_id'];
        if ($user_id && $user_id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            if ($stmt->execute()) {
                $message = 'Пользователь успешно заблокирован';
            } else {
                $message = 'Ошибка блокировки пользователя';
            }
            $stmt->close();
        }
        header('Location: admin_users.php?msg=' . urlencode($message));
        exit();

    case 'unblock_user':
        $user_id = (int)$_POST['user_id'];
        if ($user_id) {
            $stmt = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            if ($stmt->execute()) {
                $message = 'Пользователь успешно разблокирован';
            } else {
                $message = 'Ошибка разблокировки пользователя';
            }
            $stmt->close();
        }
        header('Location: admin_users.php?msg=' . urlencode($message));
        exit();

    case 'delete_lot':
        $lot_id = (int)$_POST['lot_id'];
        if ($lot_id) {
            // Сначала получаем информацию о файле изображения
            $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->bind_param('i', $lot_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();

            // Удаляем лот из базы
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param('i', $lot_id);
            if ($stmt->execute()) {
                // Удаляем файл изображения, если он существует
                if ($product && !empty($product['image']) && file_exists($product['image'])) {
                    @unlink($product['image']);
                }
                $message = 'Лот успешно удален';
            } else {
                $message = 'Ошибка удаления лота';
            }
            $stmt->close();
        }
        header('Location: admin_lots.php?msg=' . urlencode($message));
        exit();

    default:
        header('Location: admin.php');
        exit();
}
?>