<?php
include 'admin_check.php';

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

// Формируем запрос
$query = "SELECT p.*, u.username as seller_name FROM products p LEFT JOIN users u ON p.user_id = u.id WHERE 1=1";

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
}

if ($status === 'active') {
    $query .= " AND p.end_time > NOW()";
} elseif ($status === 'ended') {
    $query .= " AND p.end_time <= NOW()";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if ($search) {
    $searchParam = "%$search%";
    $stmt->bind_param('ss', $searchParam, $searchParam);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Управление лотами</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 50px auto;
            padding: 20px;
        }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .admin-nav {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .admin-nav a {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .admin-nav a:hover {
            background: rgba(255,255,255,0.3);
        }
        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .filter-box form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-box input, .filter-box select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .filter-box input[type="text"] {
            flex: 1;
            min-width: 250px;
        }
        .filter-box button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .lots-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .lots-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .lots-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        .lots-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .lots-table tr:hover {
            background: #f8f9fa;
        }
        .lot-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-ended {
            background: #f8d7da;
            color: #721c24;
        }
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 5px;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-view {
            background: #3498db;
            color: white;
            text-decoration: none;
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
        }
        .message {
            padding: 15px;
            background: #d4edda;
            color: #155724;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Управление лотами</h1>
        <div class="admin-nav">
            <a href="admin.php">Главная</a>
            <a href="admin_lots.php">Управление лотами</a>
            <a href="admin_users.php">Управление пользователями</a>
            <a href="index.php">На сайт</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="message"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <div class="filter-box">
        <form method="GET">
            <input type="text" name="search" placeholder="Поиск по названию или описанию" value="<?= htmlspecialchars($search) ?>">
            <select name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Все лоты</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Активные</option>
                <option value="ended" <?= $status === 'ended' ? 'selected' : '' ?>>Завершенные</option>
            </select>
            <button type="submit">Поиск</button>
            <?php if ($search || $status !== 'all'): ?>
                <a href="admin_lots.php" style="color: #667eea;">Сбросить</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="lots-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Продавец</th>
                    <th>Цена</th>
                    <th>Окончание</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php 
                    $is_active = strtotime($product['end_time']) > time();
                    ?>
                    <tr>
                        <td><?= $product['id'] ?></td>
                        <td>
                            <?php if ($product['image']): ?>
                                <img src="<?= htmlspecialchars($product['image']) ?>" alt="Лот" class="lot-image">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999;">Нет фото</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                            <small style="color: #666;"><?= mb_substr(htmlspecialchars($product['description']), 0, 50) ?>...</small>
                        </td>
                        <td><?= htmlspecialchars($product['seller_name']) ?></td>
                        <td><strong><?= number_format($product['price'], 2) ?> ₽</strong></td>
                        <td><?= date('d.m.Y H:i', strtotime($product['end_time'])) ?></td>
                        <td>
                            <?php if ($is_active): ?>
                                <span class="status-badge status-active">Активен</span>
                            <?php else: ?>
                                <span class="status-badge status-ended">Завершен</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="auction.php?id=<?= $product['id'] ?>" class="btn-view" target="_blank">Просмотр</a>
                            <form method="POST" action="admin_actions.php" style="display: inline;">
                                <input type="hidden" name="action" value="delete_lot">
                                <input type="hidden" name="lot_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="action-btn btn-delete" onclick="return confirm('Удалить этот лот? Это действие нельзя отменить!');">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px;">Лоты не найдены</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>