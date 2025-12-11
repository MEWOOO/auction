<?php
include 'admin_check.php';

// Получаем статистику
$stats = [];

// Общее количество пользователей
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Заблокированные пользователи
$result = $conn->query("SELECT COUNT(*) as blocked FROM users WHERE is_blocked = 1");
$stats['blocked_users'] = $result->fetch_assoc()['blocked'];

// Общее количество товаров
$result = $conn->query("SELECT COUNT(*) as total FROM products");
$stats['total_products'] = $result->fetch_assoc()['total'];

// Активные аукционы
$result = $conn->query("SELECT COUNT(*) as active FROM products WHERE end_time > NOW()");
$stats['active_auctions'] = $result->fetch_assoc()['active'];

// Завершенные аукционы
$result = $conn->query("SELECT COUNT(*) as ended FROM products WHERE end_time <= NOW()");
$stats['ended_auctions'] = $result->fetch_assoc()['ended'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Админ-панель</title>
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
            transition: all 0.3s;
        }
        .admin-nav a:hover {
            background: rgba(255,255,255,0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Панель администратора</h1>
        <p>Добро пожаловать, <?= htmlspecialchars($_SESSION['username'] ?? 'Администратор') ?></p>
        <div class="admin-nav">
            <a href="admin.php">Главная</a>
            <a href="admin_lots.php">Управление лотами</a>
            <a href="admin_users.php">Управление пользователями</a>
            <a href="index.php">На сайт</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Всего пользователей</div>
            <div class="stat-number"><?= $stats['total_users'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Заблокированных</div>
            <div class="stat-number" style="color: #e74c3c;"><?= $stats['blocked_users'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Всего лотов</div>
            <div class="stat-number"><?= $stats['total_products'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Активные аукционы</div>
            <div class="stat-number" style="color: #27ae60;"><?= $stats['active_auctions'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Завершенные аукционы</div>
            <div class="stat-number" style="color: #95a5a6;"><?= $stats['ended_auctions'] ?></div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>