<?php
session_start();
include("db.php");
include("header.php");
include("wallet_functions.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$balance = getBalance($user_id, $conn);

// Получаем историю операций
$history = $conn->query("
    SELECT * FROM wallet_transactions
    WHERE user_id = $user_id
    ORDER BY created_at DESC
");
?>
<script>
function openTopup(method) {
    document.getElementById('topupWindow').style.display = 'flex';

    document.getElementById('topupMethod').value = method;

    let title = {
        "sbp": "Пополнение через СБП",
        "card": "Пополнение картой",
        "paypal": "Пополнение через PayPal"
    };

    document.getElementById('topupTitle').innerText = title[method];
}

function closeTopup() {
    document.getElementById('topupWindow').style.display = 'none';
}
</script>

<div class="wallet-container">

    <div class="wallet-card">
        <div class="wallet-card-left">
            <img src="images/wallet-icon.png" class="wallet-big-icon">
            <div class="wallet-balance-amount">
                <span>Баланс</span>
                <h1><?= number_format($balance, 2) ?> ₽</h1>
            </div>
        </div>
    </div>

    <h2 class="section-title">Пополнение</h2>

    <div class="payment-methods">
        <div class="method-card" onclick="openTopup('sbp')">
            <img src="images/sbp.svg" alt="СБП">
            <span>СБП</span>
        </div>

        <div class="method-card" onclick="openTopup('card')">
            <img src="images/card.png" alt="Карта">
            <span>Банковская карта</span>
        </div>

        <div class="method-card" onclick="openTopup('paypal')">
            <img src="images/paypal.png" alt="PayPal">
            <span>PayPal</span>
        </div>
    </div>

    <div id="topupWindow" class="topup-window">
        <div class="topup-modal">
            <h3 id="topupTitle">Пополнение</h3>

            <form action="wallet_topup.php" method="POST">
                <input type="hidden" name="method" id="topupMethod">

                <input type="number" name="amount" min="1" step="0.01" required placeholder="Сумма">
                <button type="submit" class="btn">Пополнить</button>
            </form>

            <button class="close-btn" onclick="closeTopup()">Закрыть</button>
        </div>
    </div>

    <h2 class="section-title">История операций</h2>

<div class="transaction-list">

<?php while ($row = $history->fetch_assoc()): ?>

    <?php 
        $isDeposit = $row['type'] == "deposit";
        $amount = ($isDeposit ? "+" : "-") . $row['amount'] . " ₽";
        $icon = $isDeposit ? "⬆" : "⬇";
        $typeText = $isDeposit ? "Пополнение" : "Списание";
    ?>

    <div class="transaction-item">
        
        <div class="trans-left">
            <div class="trans-icon <?= $row['type'] ?>">
                <?= $icon ?>
            </div>

            <div class="trans-info">
                <span class="desc"><?= htmlspecialchars($row['description']) ?></span>
                <span><?= $typeText ?></span>
            </div>
        </div>

        <div class="trans-right">
            <div class="trans-amount <?= $row['type'] ?>">
                <?= $amount ?>
            </div>
            <div class="trans-date">
                <?= date("d.m.Y H:i", strtotime($row['created_at'])) ?>
            </div>
        </div>

    </div>

<?php endwhile; ?>

</div>


</div>

<?php include("footer.php"); ?>
