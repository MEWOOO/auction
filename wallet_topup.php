<?php
session_start();
include("db.php");
include("wallet_functions.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$amount = floatval($_POST['amount']);

if ($amount > 0) {
    deposit($user_id, $amount, "Пополнение баланса", $conn);
}

header("Location: wallet.php");
exit;
?>
