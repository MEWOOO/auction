<?php
session_start();
include("db.php");
include 'header.php';

$message = "";
$success = false;

// Обработка формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Подготовленный запрос — защита от SQL-инъекций
    $stmt = $conn->prepare("SELECT id, username, password, is_admin, is_blocked, avatar FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Проверяем, не заблокирован ли пользователь
        if ($user['is_blocked'] == 1) {
            $message = "Ваш аккаунт заблокирован. Обратитесь к администратору.";
        } elseif (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['avatar'] = $user['avatar'];

            header("Location: index.php");
            exit;

        } else {
            $message = "Неверный пароль!";
        }
    } else {
        $message = "Пользователь с таким Email не найден!";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- КРАСИВЫЙ БЛОК АВТОРИЗАЦИИ -->
<div class="auth-box">
    <div class="auth-card">

        <h2>Вход</h2>
        <p class="auth-subtitle">Рады видеть вас снова!</p>

        <?php if ($message): ?>
            <p class="error" style="text-align:center;"><?= $message ?></p>
        <?php endif; ?>

        <form method="POST">

            <div class="input-group">
                <label>Email:</label>
                <input type="email" name="email" required autofocus>
            </div>

            <div class="input-group">
                <label>Пароль:</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="passwordInput" required>
                    <span id="showPass"
                        style="
                            position:absolute; 
                            right:12px; 
                            top:50%; 
                            transform:translateY(-50%);
                            cursor:pointer;
                            font-size:13px;
                            color:#4e54c8;
                        ">
                        Показать
                    </span>
                </div>
            </div>

            <button type="submit" class="btn auth-btn">Войти</button>

        </form>

        <div class="auth-footer">
            <p>Нет аккаунта? <a href="register.php">Создать</a></p>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>


<script>
// === Показать / скрыть пароль ===
document.getElementById("showPass").onclick = function() {
    let field = document.getElementById("passwordInput");
    if (field.type === "password") {
        field.type = "text";
        this.textContent = "Скрыть";
    } else {
        field.type = "password";
        this.textContent = "Показать";
    }
};
</script>

</body>
</html>