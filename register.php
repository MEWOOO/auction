<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("db.php");
session_start();

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $agree = isset($_POST['agree']);

    if ($username === '' || $email === '' || $password === '') {
        $errors[] = "Заполните все поля.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Неверный формат email.";
    } elseif (!$agree) {
        $errors[] = "Вы должны принять условия конфиденциальности.";
    } else {
        // проверим существование email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            $errors[] = "Ошибка базы данных: " . $conn->error;
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Пользователь с таким email уже существует.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtIns = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                if (!$stmtIns) {
                    $errors[] = "Ошибка базы данных: " . $conn->error;
                } else {
                    $stmtIns->bind_param('sss', $username, $email, $hash);
                    if ($stmtIns->execute()) {
                        // Получаем ID только что созданного пользователя
                        $new_user_id = (int)$conn->insert_id;

                        // Создаём кошелёк (wallet) для пользователя безопасно (prepared)
                        $walletStmt = $conn->prepare("INSERT INTO wallet (user_id, balance) VALUES (?, 0)");
                        if ($walletStmt) {
                            $walletStmt->bind_param('i', $new_user_id);
                            if (!$walletStmt->execute()) {
                                // Не фатальная ошибка — логируем/показываем пользователю
                                $errors[] = "Пользователь создан, но не удалось автоматически создать кошелёк: " . $walletStmt->error;
                            }
                            $walletStmt->close();
                        } else {
                            $errors[] = "Пользователь создан, но ошибка при подготовке создания кошелька: " . $conn->error;
                        }

                        $success = "Регистрация успешна. Можете <a href='login.php'>войти</a>.";
                    } else {
                        $errors[] = "Ошибка базы данных при создании пользователя: " . $stmtIns->error;
                    }
                    $stmtIns->close();
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Регистрация</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="auth-box">
    <div class="auth-card">

        <h2>Создать аккаунт</h2>
        <p class="auth-subtitle">Добро пожаловать! Заполните данные ниже</p>

        <?php if ($errors): ?>
            <div class="errors">
                <?php foreach ($errors as $e) echo "<p>" . htmlspecialchars($e) . "</p>"; ?>
            </div>
        <?php endif; ?>

        <?php if ($success) echo "<p class='success'>$success</p>"; ?>

        <form method="POST" novalidate>

            <div class="input-group">
                <label>Имя пользователя:</label>
                <input type="text" name="username" required value="<?=htmlspecialchars($_POST['username'] ?? '')?>">
            </div>

            <div class="input-group">
                <label>Email:</label>
                <input type="email" name="email" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
            </div>

            <div class="input-group">
                <label>Пароль:</label>
                <input type="password" name="password" required>
            </div>

            <!-- Новая галочка -->
            <label class="checkbox-line">
                <input type="checkbox" name="agree" <?= isset($_POST['agree']) ? 'checked' : '' ?> >
                <span>Принимаю <a href="privacy.php" target="_blank">условия конфиденциальности</a></span>
            </label>

            <button type="submit" class="btn auth-btn">Зарегистрироваться</button>

        </form>

        <p class="auth-footer">
            Уже есть аккаунт? <a href="login.php">Войти</a>
        </p>

    </div>
</section>

<?php include 'footer.php'; ?>


</body>
</html>
