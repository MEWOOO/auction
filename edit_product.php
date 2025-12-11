<?php
include("db.php");
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)($_GET['id'] ?? 0);

// Получаем товар
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo "Товар не найден или у вас нет доступа.";
    exit;
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category = $_POST['category'] ?? 'Прочее'; 
    $imageName = $product['image'];


    if ($title === '' || $description === '' || $price === '') {
        $errors[] = "Все поля должны быть заполнены.";
    }

    // Проверяем, загрузил ли пользователь новую картинку
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir);

        $newImageName = time() . "_" . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $newImageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            // Удаляем старое фото, если было
            if ($product['image'] && file_exists("uploads/" . $product['image'])) {
                unlink("uploads/" . $product['image']);
            }
            $imageName = $newImageName;
        } else {
            $errors[] = "Ошибка загрузки фото.";
        }
    }

    if (!$errors) {
       $stmt = $conn->prepare("UPDATE products 
        SET title=?, description=?, price=?, image=?, category=? 
        WHERE id=? AND user_id=?");
        $stmt->bind_param("ssdssii", $title, $description, $price, $imageName, $category, $product_id, $user_id);

        if ($stmt->execute()) {
            $success = "Изменения сохранены!";
            // Обновим данные товара
            $product['title'] = $title;
            $product['description'] = $description;
            $product['price'] = $price;
            $product['image'] = $imageName;
            $product['category'] = $category;

        } else {
            $errors[] = "Ошибка базы данных.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Редактировать товар</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<main>
  <section class="form-section">
    <div class="container">
      <h2>Редактировать объявление</h2>

      <?php if ($errors): ?>
        <div class="errors">
          <?php foreach ($errors as $e) echo "<p>" . htmlspecialchars($e) . "</p>"; ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <label>Название:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($product['title']) ?>" required>

        <label>Описание:</label>
        <textarea name="description" required><?= htmlspecialchars($product['description']) ?></textarea>

        <label>Категория:</label>
        <select name="category" required>
        <?php
        $categories = ["Электроника","Одежда","Мебель","Книги","Прочее"];
        foreach ($categories as $cat) {
            $selected = ($cat === $product['category']) ? 'selected' : '';
            echo "<option value='$cat' $selected>$cat</option>";
        }
        ?>
        </select>

        <label>Цена (₽):</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>

        <label>Фото:</label>
        <?php if ($product['image']): ?>
          <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="" style="max-width:150px; display:block; margin:10px 0;">
        <?php endif; ?>
        <input type="file" name="image" accept="image/*">

        <button type="submit" class="btn">Сохранить изменения</button>
      </form>
    </div>
  </section>
</main>

<?php include 'footer.php'; ?>


</body>
</html>
