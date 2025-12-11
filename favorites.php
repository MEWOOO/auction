<?php
include("db.php");
session_start();
include("header.php");

if (!isset($_SESSION['user_id'])) {
    die("<p class='error'>Вы должны <a href='login.php'>войти</a>.</p>");
}

$user_id = $_SESSION['user_id'];

$sql = "
SELECT products.* 
FROM favorites 
JOIN products ON products.id = favorites.product_id
WHERE favorites.user_id = $user_id
";

$result = $conn->query($sql);
?>

<main class="catalog">
<h2 style="text-align:center; margin-bottom:20px;">Избранные лоты ❤️</h2>

<div class="product-list">

<?php
if ($result->num_rows == 0) {
    echo "<p>У вас нет избранных лотов.</p>";
} else {
    while ($row = $result->fetch_assoc()) {

        echo "
        <div class='product-card'>
            <img src='uploads/{$row['image']}' alt='{$row['title']}'>
            <h3>{$row['title']}</h3>
            <p><strong>Текущая цена:</strong> {$row['current_price']} ₽</p>
            <a href='auction.php?id={$row['id']}' class='btn small'>Подробнее</a>
            <a href='favorite_toggle.php?id={$row['id']}' class='btn red small'>Убрать</a>
        </div>
        ";
    }
}
?>
</div>
</main>

<?php include 'footer.php'; ?>


</body>
</html>
