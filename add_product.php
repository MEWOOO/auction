<?php
include("db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = ""; // Инициализация переменной для сообщений
$errors = []; // Для ошибок валидации

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['start_price'] ?? 0);
    $category = $_POST['category'] ?? 'Прочее';
    $end_time = $_POST['end_time'] ?? null;

    if ($price <= 0) {
        $errors[] = "Начальная цена должна быть больше 0.";
    }
    if (!$end_time) {
        $errors[] = "Укажите дату окончания аукциона.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO products (user_id, title, description, start_price, current_price, image, category, end_time)
             VALUES (?, ?, ?, ?, ?, '', ?, ?)"
        );
        $stmt->bind_param("issddss", $user_id, $title, $description, $price, $price, $category, $end_time);

        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;

            // ===== Загрузка нескольких изображений =====
            if (!empty($_FILES['images']['name'][0])) {
                $count = count($_FILES['images']['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $originalName = basename($_FILES['images']['name'][$i]);
                    $filename = time() . "_" . $originalName;
                    $tmp = $_FILES['images']['tmp_name'][$i];
                    $allowed = ["image/jpeg", "image/png", "image/jpg"];
                    if (!in_array($_FILES['images']['type'][$i], $allowed)) continue;
                    if (move_uploaded_file($tmp, "uploads/" . $filename)) {
                        $conn->query("INSERT INTO product_images (product_id, filename) VALUES ($product_id, '$filename')");
                    }
                }

                $imgQ = $conn->query("SELECT filename FROM product_images WHERE product_id = $product_id LIMIT 1");
                if ($imgQ->num_rows > 0) {
                    $firstImg = $imgQ->fetch_assoc()['filename'];
                    $conn->query("UPDATE products SET image = '$firstImg' WHERE id = $product_id");
                }
            }
            $message = "Аукцион успешно создан!";
        } else {
            $errors[] = "Ошибка при создании аукциона.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить аукцион</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ===== Новая современная форма добавления товара ===== */
        .form-section {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 30px 35px;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .form-section h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
            color: #4e54c8;
        }
        form label {
            display: block;
            margin: 10px 0 6px;
            font-weight: bold;
        }
        form input, form select, form textarea {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 15px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 15px;
            transition: 0.25s;
        }
        form input:focus, form select:focus, form textarea:focus {
            border-color: #4e54c8;
            box-shadow: 0 0 8px rgba(78, 84, 200, 0.3);
            outline: none;
        }
        .btn-submit {
            background: #4e54c8;
            color: #fff;
            font-weight: bold;
            border: none;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            cursor: pointer;
            transition: 0.25s;
        }
        .btn-submit:hover {
            background: #3b3fc1;
            transform: translateY(-2px);
        }
        .message {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .message.success { color: #28a745; }
        .message.error { color: #d9534f; }

        /* ===== Превью выбранных изображений ===== */
        .preview-images {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .preview-images img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ccc;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <section class="form-section">
        <h2>Создать новый аукцион</h2>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?= implode("<br>", array_map('htmlspecialchars', $errors)) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="productForm">
            <label>Название товара:</label>
            <input type="text" name="title" required>

            <label>Описание:</label>
            <textarea name="description" rows="4" required></textarea>

            <label>Категория:</label>
            <select name="category" required>
                <option value="Электроника">Электроника</option>
                <option value="Одежда">Одежда</option>
                <option value="Мебель">Мебель</option>
                <option value="Книги">Книги</option>
                <option value="Прочее">Прочее</option>
            </select>

            <label>Начальная цена (₽):</label>
            <input type="number" name="start_price" step="0.01" min="1" required>

            <label>Дата и время окончания аукциона:</label>
            <input type="datetime-local" name="end_time" required>

            <label>Фото товара (можно несколько):</label>
            <input type="file" name="images[]" multiple accept="image/*" id="imagesInput">

            <div class="preview-images" id="preview"></div>

            <button type="submit" class="btn-submit">Создать аукцион</button>
        </form>
    </section>
</main>

<?php include 'footer.php'; ?>

<script>
    // Превью выбранных изображений
    const imagesInput = document.getElementById('imagesInput');
    const preview = document.getElementById('preview');

    imagesInput.addEventListener('change', function() {
        preview.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            }
            reader.readAsDataURL(file);
        });
    });
</script>

</body>
</html>
