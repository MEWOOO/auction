<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$my_id = $_SESSION['user_id'];
$to_id = intval($_GET['to']);
$product_id = intval($_GET['product']);

// Получаем данные продавца/покупателя
$userStmt = $conn->query("SELECT username FROM users WHERE id = $to_id");
$userInfo = $userStmt->fetch_assoc();

// Отправка сообщения
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $msg = $conn->real_escape_string($_POST['message']);
    if ($msg !== "") {
        $conn->query("
            INSERT INTO messages (sender_id, receiver_id, product_id, message)
            VALUES ($my_id, $to_id, $product_id, '$msg')
        ");
    }
}

// История переписки
$messages = $conn->query("
    SELECT m.*, u.username AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE product_id = $product_id
      AND ((sender_id = $my_id AND receiver_id = $to_id)
       OR  (sender_id = $to_id AND receiver_id = $my_id))
    ORDER BY created_at ASC
");

// Получаем название лота
$prod = $conn->query("SELECT title FROM products WHERE id = $product_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Чат</title>
    <link rel="stylesheet" href="style.css">
</head>
<script>
// Автоподстройка textarea по контенту
const textarea = document.querySelector('.chat-form textarea');
textarea.addEventListener('input', function(){
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

// Автоскролл вниз при загрузке страницы
const chatBox = document.querySelector('.chat-box');
chatBox.scrollTop = chatBox.scrollHeight;
</script>

<body>

<?php include("header.php"); ?>

<main class="chat-page">

    <h2>Чат с пользователем: <?= htmlspecialchars($userInfo['username']) ?></h2>
    <p style="color:#777;">Лот: <strong><?= htmlspecialchars($prod['title']) ?></strong></p>

    <div class="chat-box">
        <?php while ($msg = $messages->fetch_assoc()): ?>
            <div class="chat-message <?= $msg['sender_id'] == $my_id ? 'my' : 'other' ?>">
                <p class="sender"><?= htmlspecialchars($msg['sender_name']) ?></p>
                <p class="text"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                <span class="time"><?= date("H:i d.m.Y", strtotime($msg['created_at'])) ?></span>
            </div>
        <?php endwhile; ?>
    </div>

    <form class="chat-form" method="POST">
        <textarea name="message" placeholder="Введите сообщение..." required></textarea>
        <button class="btn">→</button>
    </form>

</main>

<style>
.chat-page { max-width: 850px; margin: 25px auto; padding: 0 15px; animation: fadeIn 0.5s ease; }
@keyframes fadeIn { from { opacity:0; transform: translateY(15px); } to { opacity:1; transform: translateY(0); } }

/* Чат-бокс */
.chat-box {
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    padding: 20px;
    max-height: 550px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    scroll-behavior: smooth;
}

/* Сообщения */
.chat-message {
    padding: 12px 14px;
    border-radius: 16px;
    max-width: 65%;
    animation: msgAppear .35s ease forwards;
    opacity: 0;
}
@keyframes msgAppear { from { opacity:0; transform: translateY(10px) scale(.97);} to {opacity:1; transform: translateY(0) scale(1);} }

.chat-message.my { background: linear-gradient(135deg,#6a5acd,#897aff); color:#fff; margin-left:auto; border-bottom-right-radius:4px; }
.chat-message.other { background:#f4f4f8; border-bottom-left-radius:4px; }

.chat-message .sender { font-weight:600; font-size:14px; opacity:0.85; margin-bottom:6px; }
.chat-message .text { font-size:15px; line-height:1.35; }
.chat-message .time { font-size:12px; opacity:0.7; margin-top:6px; display:block; text-align:right; }

/* === Динамическое поле ввода + кнопка === */
.chat-form {
    margin-top: 18px;
    display: flex;
    gap: 8px;
    align-items: center; /* центрируем по высоте всей строки */
}

.chat-form textarea {
    flex: 1;
    min-height: 40px;
    max-height: 150px;
    padding: 10px 12px;
    border-radius: 20px;
    border: 1px solid #dcdcdc;
    resize: none;
    font-size: 15px;
    line-height: 1.4; /* важно! */
    overflow-y: auto;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    box-sizing: border-box;
}

.chat-form button {
    background: #6a5acd;
    color: #fff;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 0; /* чтобы точно не смещалось */
}


.chat-form textarea:focus {
    outline: none;
    border-color: #6a5acd;
    box-shadow: 0 0 10px rgba(106,90,205,0.3);
}

/* Кнопка отправки (стрелка) */

.chat-form button:hover {
    background: #5848d9;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(106,90,205,0.35);
}

/* скролл чат-бокса */
.chat-box::-webkit-scrollbar { width: 8px; }
.chat-box::-webkit-scrollbar-track { background: transparent; }
.chat-box::-webkit-scrollbar-thumb { background: rgba(150,150,150,0.4); border-radius: 10px; }
.chat-box::-webkit-scrollbar-thumb:hover { background: rgba(150,150,150,0.7); }

@media(max-width:600px) { .chat-message { max-width:90%; } }

</style>



</body>
</html>
