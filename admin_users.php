<?php
include 'admin_check.php';

$message = '';
$search = $_GET['search'] ?? '';

// Получаем список пользователей
$query = "SELECT id, username, email, is_blocked, created_at FROM users WHERE 1=1";
if ($search) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
}
$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if ($search) {
    $searchParam = "%$search%";
    $stmt->bind_param('ss', $searchParam, $searchParam);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1200px;
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
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .search-box button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .users-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .users-table tr:hover {
            background: #f8f9fa;
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
        .status-blocked {
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
        .btn-block {
            background: #e74c3c;
            color: white;
        }
        .btn-unblock {
            background: #27ae60;
            color: white;
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
        <h1>Управление пользователями</h1>
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

    <div class="search-box">
        <form method="GET">
            <input type="text" name="search" placeholder="Поиск по имени или email" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Поиск</button>
            <?php if ($search): ?>
                <a href="admin_users.php" style="margin-left: 10px;">Сбросить</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="users-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя пользователя</th>
                    <th>Email</th>
                    <th>Дата регистрации</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php if ($user['id'] == $_SESSION['user_id']) continue; // Не показываем текущего админа ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                        <td>
                            <?php if ($user['is_blocked']): ?>
                                <span class="status-badge status-blocked">Заблокирован</span>
                            <?php else: ?>
                                <span class="status-badge status-active">Активен</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['is_blocked']): ?>
                                <form method="POST" action="admin_actions.php" style="display: inline;">
                                    <input type="hidden" name="action" value="unblock_user">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="action-btn btn-unblock" onclick="return confirm('Разблокировать пользователя?')">Разблокировать</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="admin_actions.php" style="display: inline;">
                                    <input type="hidden" name="action" value="block_user">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="action-btn btn-block" onclick="return confirm('Заблокировать пользователя?')">Заблокировать</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px;">Пользователи не найдены</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>