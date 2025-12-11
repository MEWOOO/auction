<?php
session_start();
include("db.php");
include("header.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем все уникальные диалоги пользователя с данными собеседника
$dialogs = $conn->query("
    SELECT 
        m.product_id,
        p.title AS product_title,
        
        CASE 
            WHEN m.sender_id = $user_id THEN m.receiver_id
            ELSE m.sender_id
        END AS companion_id,
        
        (SELECT username FROM users WHERE id = 
            CASE 
                WHEN m.sender_id = $user_id THEN m.receiver_id
                ELSE m.sender_id
            END
        ) AS companion_name,

        (SELECT avatar FROM users WHERE id = 
            CASE 
                WHEN m.sender_id = $user_id THEN m.receiver_id
                ELSE m.sender_id
            END
        ) AS companion_avatar,
        
        (SELECT message FROM messages 
         WHERE 
            (sender_id = $user_id AND receiver_id = companion_id AND product_id = m.product_id)
            OR
            (receiver_id = $user_id AND sender_id = companion_id AND product_id = m.product_id)
         ORDER BY created_at DESC LIMIT 1
        ) AS last_message,
        
        (SELECT created_at FROM messages 
         WHERE 
            (sender_id = $user_id AND receiver_id = companion_id AND product_id = m.product_id)
            OR
            (receiver_id = $user_id AND sender_id = companion_id AND product_id = m.product_id)
         ORDER BY created_at DESC LIMIT 1
        ) AS last_date
        
    FROM messages m
    JOIN products p ON p.id = m.product_id
    WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
    GROUP BY companion_id, m.product_id
    ORDER BY last_date DESC
");

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Диалоги</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="dialogs-page">
    <div class="container">
        <h2>Мои диалоги</h2>

        <?php if ($dialogs->num_rows == 0): ?>
            <p>У вас пока нет сообщений.</p>
        <?php else: ?>

            <div class="dialogs-list">
                <?php while ($d = $dialogs->fetch_assoc()): ?>
                    <div class="dialog-card">
                        <!-- Аватар собеседника -->
                        <img 
                            src="<?= htmlspecialchars($d['companion_avatar'] ?: 'images/default-avatar.png') ?>" 
                            class="dialog-avatar" 
                            alt="Avatar"
                        >

                        <div class="dialog-info">
                            <!-- Имя собеседника -->
                            <h3><?= htmlspecialchars($d['companion_name']) ?></h3>

                            <!-- Лот -->
                            <p class="product-name">
                                Лот: 
                                <a href="auction.php?id=<?= $d['product_id'] ?>">
                                    <?= htmlspecialchars($d['product_title']) ?>
                                </a>
                            </p>

                            <!-- Последнее сообщение -->
                            <p class="last-message">
                                <?= htmlspecialchars(mb_strimwidth($d['last_message'], 0, 80, "...")) ?>
                            </p>

                            <!-- Дата последнего сообщения -->
                            <span class="date">
                                <?= date("d.m H:i", strtotime($d['last_date'])) ?>
                            </span>
                        </div>

                        <!-- Кнопка открытия чата -->
                        <div class="dialog-actions">
                            <a href="chat.php?product=<?= $d['product_id'] ?>&to=<?= $d['companion_id'] ?>" class="btn small">
                                Открыть
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php endif; ?>
    </div>
</main>

</body>
</html>
