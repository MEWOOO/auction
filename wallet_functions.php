<?php

if (!function_exists('getBalance')) {

    function getBalance($user_id, $conn) {
        $q = $conn->query("SELECT balance FROM wallet WHERE user_id = $user_id");
        return $q->fetch_assoc()['balance'];
    }

    function deposit($user_id, $amount, $description, $conn) {
        if ($amount <= 0) return false;

        $conn->query("UPDATE wallet SET balance = balance + $amount WHERE user_id = $user_id");

        $conn->query("
            INSERT INTO wallet_transactions (user_id, amount, type, description)
            VALUES ($user_id, $amount, 'deposit', '$description')
        ");

        return true;
    }

    function withdraw($user_id, $amount, $description, $conn) {
        if ($amount <= 0) return false;

        $q = $conn->query("SELECT balance FROM wallet WHERE user_id = $user_id");
        $balance = $q->fetch_assoc()['balance'];

        if ($balance < $amount) return false;

        $conn->query("UPDATE wallet SET balance = balance - $amount WHERE user_id = $user_id");

        $conn->query("
            INSERT INTO wallet_transactions (user_id, amount, type, description)
            VALUES ($user_id, $amount, 'withdraw', '$description')
        ");

        return true;
    }

}
?>
