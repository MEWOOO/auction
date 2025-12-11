<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "used_shop";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

date_default_timezone_set('Europe/Moscow');
$conn->query("SET time_zone = '+03:00'");
?>
