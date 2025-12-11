<?php
include("db.php");
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Данные пользователя
$stmt = $conn->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Лоты пользователя
$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Личный кабинет</title>
<link rel="stylesheet" href="style.css">
<style>
/* ==== ПРОФИЛЬ 2025 ==== */
.profile {
    padding: 40px 20px;
    max-width: 1100px;
    margin: auto;
}

.profile h2 {
    font-size: 32px;
    margin-bottom: 25px;
    color: #1c1c1c;
    text-align: center;
}

.profile-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(255,255,255,0.7);
    backdrop-filter: blur(12px);
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    gap: 12px;
    margin-bottom: 40px;
}

.profile-avatar {
    position: relative;
    display: inline-block;
    cursor: pointer;
    width: 120px;
    height: 120px;
    margin-bottom: 20px;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #6a5acd;
    transition: transform 0.25s, box-shadow 0.25s;
}

.profile-avatar img:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.profile-buttons {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.profile-buttons .btn {
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: bold;
    transition: 0.25s ease;
}

.profile-buttons .btn.red {
    background: #ff5a5f;
    color: #fff;
}

.profile-buttons .btn.red:hover {
    background: #e04444;
}

.profile-buttons .btn.heart {
    background: #ffb6b9;
    color: #1c1c1c;
}

.profile-buttons .btn.heart:hover {
    background: #ff7b81;
    color: white;
}

/* ===== ЛОТЫ ПОЛЬЗОВАТЕЛЯ ===== */
.product-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 25px;
}

.product-card {
    background: rgba(255,255,255,0.6);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    padding: 18px;
    text-align: center;
    box-shadow: 0 12px 30px rgba(0,0,0,0.07);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 360px;
    opacity: 0;
    animation: fadeCard 0.5s forwards;
}

.product-card:nth-child(1) { animation-delay: 0.05s; }
.product-card:nth-child(2) { animation-delay: 0.1s; }
.product-card:nth-child(3) { animation-delay: 0.15s; }

.product-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 18px 40px rgba(0,0,0,0.12);
}

.product-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 14px;
    margin-bottom: 12px;
    transition: transform 0.25s;
}

.product-card:hover img {
    transform: scale(1.06);
}

.product-card h4 {
    font-size: 18px;
    margin: 10px 0 6px;
    font-weight: 600;
    color: #1c1c1c;
}

.product-card p {
    font-size: 15px;
    margin: 6px 0;
    color: #444;
}

.product-card .actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: auto;
}

.product-card .btn.small {
    padding: 8px 14px;
    border-radius: 10px;
    transition: 0.25s;
    background: #6a5acd;
    color: #fff;
}

.product-card .btn.small:hover {
    background: #5848d9;
    transform: translateY(-2px);
}

.product-card .btn.small.red {
    background: #ff5a5f;
}

.product-card .btn.small.red:hover {
    background: #e04444;
}

.product-card .disabled {
    background: #aaa;
    cursor: default;
}

@keyframes fadeCard {
    from { opacity: 0; transform: translateY(20px) scale(0.95);}
    to { opacity: 1; transform: translateY(0) scale(1);}
}

hr {
    border: none;
    border-top: 1px solid #ddd;
    margin: 25px 0;
}

</style>
</head>
<body>
<?php include 'header.php'; ?>

<main>
<section class="profile">
    <h2>Личный кабинет</h2>

    <div class="profile-info">
        <div class="profile-avatar">
            <img 
                id="userAvatar" 
                src="<?= htmlspecialchars($user['avatar'] ?: 'images/default-avatar.png') ?>" 
                alt="Аватар"
                title="Нажмите, чтобы изменить аватар"
            >
            <input type="file" id="avatarInput" accept="image/png, image/jpeg, image/webp" style="display:none;">
        </div>
        <p><strong>Имя пользователя:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <div class="profile-buttons">
            <a href="favorites.php" class="btn heart">❤️ Мои избранные</a>
            <a href="logout.php" class="btn red">Выйти</a>
        </div>
    </div>

    <hr>

    <h3>Мои лоты</h3>
    <div class="product-list">
        <?php if ($products->num_rows === 0): ?>
            <p>У вас пока нет созданных лотов.</p>
        <?php else: ?>
            <?php while ($product = $products->fetch_assoc()): ?>
                <?php
                $timeLeft = strtotime($product['end_time']) - time();
                if ($timeLeft > 0) {
                    $days = floor($timeLeft / 86400);
                    $hours = floor(($timeLeft % 86400) / 3600);
                    $minutes = floor(($timeLeft % 3600) / 60);
                    $timeText = ($days ? "$days д " : "") . ($hours ? "$hours ч " : "") . "$minutes мин";
                } else {
                    $timeText = "Аукцион завершён";
                }
                ?>
                <div class="product-card">
                    <a href="auction.php?id=<?= $product['id'] ?>">
                        <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
                        <h4><?= htmlspecialchars($product['title']) ?></h4>
                        <p><strong>Текущая цена:</strong> <?= $product['current_price'] ?> ₽</p>
                        <p><strong>Осталось:</strong> <?= $timeText ?></p>
                    </a>
                    <div class="actions">
                        <?php if ($timeLeft > 0): ?>
                            <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn small">Редактировать</a>
                            <a href="delete_product.php?id=<?= $product['id'] ?>" class="btn small red" onclick="return confirm('Удалить лот?')">Удалить</a>
                        <?php else: ?>
                            <span class="btn small disabled">Лот завершён</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</section>
</main>

<script>
document.getElementById('userAvatar').addEventListener('click', function() {
    document.getElementById('avatarInput').click();
});

document.getElementById('avatarInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('avatar', file);

    fetch('upload_avatar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.startsWith('uploads/')) {
            document.getElementById('userAvatar').src = data + '?' + new Date().getTime();
        } else {
            alert(data);
        }
    })
    .catch(err => alert('Ошибка загрузки аватара'));
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>
