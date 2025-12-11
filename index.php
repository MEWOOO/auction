<?php
session_start();
include("db.php");
include 'header.php';

// Популярные товары
$popular = $conn->query("
    SELECT p.*, 
    (SELECT COUNT(*) FROM bids WHERE product_id = p.id) AS bid_count 
    FROM products p
    WHERE p.end_time > NOW()
    ORDER BY bid_count DESC, current_price DESC 
    LIMIT 8
");

// Новые поступления (последние 7 дней)
$new_arrivals = $conn->query("
    SELECT * FROM products 
    WHERE end_time > NOW()
    ORDER BY created_at DESC
    LIMIT 6
");
?>

<main>

<!-- ======= HERO ======= -->
<section class="home-hero">
    <div class="hero-content">
        <h2>Маркетплейс ретро-товаров и антиквариата</h2>
        <p>Покупай уникальные вещи, коллекционные предметы и редкие раритеты.</p>
        <a href="catalog.php" class="btn-main">Перейти в каталог</a>
    </div>
</section>

<!-- ======= Как это работает ======= -->
<section class="how-it-works">
    <div class="container">
        <h2>Как это работает</h2>
        <div class="steps">
            <div class="step">
                <span class="step-number">1</span>
                <p>Выбираешь товар</p>
            </div>
            <div class="step">
                <span class="step-number">2</span>
                <p>Ставишь цену</p>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <p>Побеждаешь и получаешь товар</p>
            </div>
        </div>
    </div>
</section>

<!-- ======= ПОПУЛЯРНЫЕ АУКЦИОНЫ ======= -->
<section class="popular-block">
    <h2>🔥 Самые популярные аукционы</h2>

    <?php if ($popular->num_rows > 0): ?>
        <div class="popular-grid">
            <?php 
            $first = true;
            while ($p = $popular->fetch_assoc()):
                $img = $p['image'] ?: "no-image.png";
            ?>
            <a href="auction.php?id=<?= $p['id'] ?>" class="popular-card <?= $first ? 'featured' : '' ?>">
                <img src="uploads/<?= $img ?>" alt="">
                <h3><?= htmlspecialchars($p['title']) ?></h3>
                <div class="price"><?= $p['current_price'] ?> ₽</div>
                <div>Ставок: <?= $p['bid_count'] ?></div>
                <?php if ($first): ?><span class="badge-popular">🔥 Популярно</span><?php endif; ?>
                <span class="btn">Открыть</span>
            </a>
            <?php $first = false; endwhile; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;">Нет доступных аукционов.</p>
    <?php endif; ?>
</section>

<!-- ======= НОВЫЕ ПОСТУПЛЕНИЯ ======= -->
<section class="new-arrivals">
    <h2>🆕 Новые поступления</h2>
    <?php if ($new_arrivals->num_rows > 0): ?>
        <div class="popular-grid">
            <?php while ($n = $new_arrivals->fetch_assoc()):
                $img = $n['image'] ?: "no-image.png";
            ?>
            <a href="auction.php?id=<?= $n['id'] ?>" class="popular-card">
                <img src="uploads/<?= $img ?>" alt="">
                <h3><?= htmlspecialchars($n['title']) ?></h3>
                <div class="price"><?= $n['current_price'] ?> ₽</div>
                <span class="btn">Открыть</span>
            </a>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;">Нет новых товаров.</p>
    <?php endif; ?>
</section>

<!-- ===== Подписка на новости ===== -->
<section class="newsletter">
    <div class="newsletter-card">
        <div class="newsletter-icon">📩</div>
        <h2>Будь в курсе новых аукционов</h2>
        <p>Подпишись и получай уведомления о новых товарах и акциях.</p>
        <form method="POST" action="subscribe.php" class="newsletter-form">
            <input type="email" name="email" placeholder="Введите ваш email" required>
            <button type="submit" class="btn-main">Подписаться</button>
        </form>
    </div>
</section>


</main>

<?php include 'footer.php'; ?>
