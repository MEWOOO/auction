<?php
session_start();
include("db.php");
include("header.php");

// Проверка наличия ID лота
if (!isset($_GET['id'])) {
    echo "<main><p class='error'>Аукцион не найден.</p></main>";
    exit;
}

$id = intval($_GET['id']);
$productQuery = $conn->query("SELECT * FROM products WHERE id = $id");

if (!$productQuery || $productQuery->num_rows === 0) {
    echo "<main><p class='error'>Лот не найден.</p></main>";
    exit;
}

$product = $productQuery->fetch_assoc();

// ====== ГАЛЕРЕЯ ======
$images = $conn->query("SELECT filename FROM product_images WHERE product_id = $id");
$imagesList = [];

while ($img = $images->fetch_assoc()) {
    $imagesList[] = $img['filename'];
}

if (count($imagesList) == 0) {
    $imagesList[] = $product['image']; // fallback для старых товаров с 1 фото
}

// История ставок
$bidsQuery = $conn->query("
    SELECT b.amount, u.username 
    FROM bids b 
    JOIN users u ON b.user_id = u.id 
    WHERE b.product_id = $id 
    ORDER BY b.amount DESC
");

// Подсчет времени
$timeLeft = strtotime($product['end_time']) - time();
$timeText = ($timeLeft > 0) ? 
    ((floor($timeLeft / 86400) ? floor($timeLeft / 86400) . " д " : "") .
     (floor(($timeLeft % 86400)/3600) ? floor(($timeLeft % 86400)/3600) . " ч " : "") .
     floor(($timeLeft % 3600)/60) . " мин") 
    : "Аукцион завершён";

// Проверка избранного
$isFavorite = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $checkFav = $conn->query("SELECT id FROM favorites WHERE user_id=$uid AND product_id=$id");
    if ($checkFav->num_rows > 0) $isFavorite = true;
}
?>

<main>
<section class="auction-page">

    <!-- Левая часть: галерея -->
    <div class="auction-image">
        <!-- Главное фото -->
        <img id="main-photo" src="uploads/<?= $imagesList[0] ?>" alt="<?= htmlspecialchars($product['title']) ?>" class="big-photo">

        <!-- Миниатюры -->
        <div class="thumbnails">
            <?php foreach ($imagesList as $img): ?>
                <img src="uploads/<?= $img ?>" class="thumb" onclick="changePhoto('uploads/<?= $img ?>')">
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Правая часть: информация -->
    <div class="auction-info">
        <h2><?= htmlspecialchars($product['title']) ?></h2>

        <!-- Кнопка избранного -->
        <div style="margin: 10px 0;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($isFavorite): ?>
                    <a href="favorite_toggle.php?id=<?= $id ?>" class="btn small red">
                        ❤️ Удалить из избранного
                    </a>
                <?php else: ?>
                    <a href="favorite_toggle.php?id=<?= $id ?>" class="btn small">
                        🤍 Добавить в избранное
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <p class="error" style="margin:0;">
                    <a href="login.php">Войдите</a>, чтобы добавить в избранное
                </p>
            <?php endif; ?>
        </div>

        <p><strong>Категория:</strong> <?= htmlspecialchars($product['category']) ?></p>
        <p><strong>Описание:</strong></p>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <hr style="margin: 15px 0; border: 0; border-top: 1px solid #ddd;">

        <p><strong>Текущая ставка:</strong> 
            <span style="font-size: 18px; color:#007bff;">
                <?= number_format($product['current_price'], 2, '.', ' ') ?> ₽
            </span>
        </p>

        <p><strong>Окончание:</strong> <?= date("d.m.Y H:i", strtotime($product['end_time'])) ?></p>
        <p><strong>Осталось времени:</strong> <span id="timer"><?= $timeText ?></span></p>

        <!-- Форма ставки -->
        <?php if ($timeLeft > 0 && isset($_SESSION['user_id'])): ?>
            <form method="POST" class="bid-form">
                <input type="number" name="bid" step="0.01" placeholder="Введите ставку (₽)" required>
                <button type="submit" class="btn small">Сделать ставку</button>
            </form>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <p class="error">Чтобы сделать ставку, <a href="login.php">войдите</a>.</p>
        <?php else: ?>
            <p class="error">Аукцион завершён.</p>
        <?php endif; ?>

        <?php
        // Обработка ставки
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bid']) && $timeLeft > 0 && isset($_SESSION['user_id'])) {
            $bid = floatval($_POST['bid']);
            if ($bid > $product['current_price']) {
                $userId = $_SESSION['user_id'];
                $conn->query("INSERT INTO bids (product_id, user_id, amount) VALUES ($id, $userId, $bid)");
                $conn->query("UPDATE products SET current_price = $bid WHERE id = $id");
                echo "<p class='success'>Ставка успешно принята!</p>";
                echo "<meta http-equiv='refresh' content='1'>";
            } else {
                echo "<p class='error'>Ставка должна быть выше текущей.</p>";
            }
        }
        ?>

        <!-- Кнопка написать продавцу -->
        <?php if (isset($_SESSION['user_id']) && $product['user_id'] != $_SESSION['user_id']): ?>
            <a href="chat.php?product=<?= $id ?>&to=<?= $product['user_id'] ?>" 
               class="btn small"
               style="background:#28a745; display:inline-block; margin-top:15px;">
               💬 Написать продавцу
            </a>
        <?php endif; ?>

        <!-- История ставок -->
        <h3 style="margin-top:25px;">История ставок</h3>
        <ul class="bid-list">
            <?php if ($bidsQuery && $bidsQuery->num_rows > 0): ?>
                <?php while ($b = $bidsQuery->fetch_assoc()): ?>
                    <li><strong><?= htmlspecialchars($b['username']) ?></strong> — <?= $b['amount'] ?> ₽</li>
                <?php endwhile; ?>
            <?php else: ?>
                <li>Пока нет ставок.</li>
            <?php endif; ?>
        </ul>
    </div>
</section>
</main>

<?php include 'footer.php'; ?>


<!-- Скрипты -->
<script>
// Таймер
let endTime = new Date("<?= date('Y-m-d H:i:s', strtotime($product['end_time'])) ?>").getTime();
let timerElem = document.getElementById("timer");

if (timerElem) {
    let timer = setInterval(() => {
        let now = new Date().getTime();
        let distance = endTime - now;

        if (distance <= 0) {
            clearInterval(timer);
            timerElem.innerText = "Аукцион завершён";
        } else {
            let d = Math.floor(distance / (1000*60*60*24));
            let h = Math.floor((distance % (1000*60*60*24)) / (1000*60*60));
            let m = Math.floor((distance % (1000*60*60)) / (1000*60));
            timerElem.innerText = `${d > 0 ? d + ' д ' : ''}${h > 0 ? h + ' ч ' : ''}${m} мин`;
        }
    }, 60000);
}

// Смена фото в галерее
function changePhoto(src) {
    document.getElementById("main-photo").src = src;
}
</script>

</body>
</html>
