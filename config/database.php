
<?php

$host = "localhost";
$port = 3306;
$username = "root";
$password = "YOUR_DB_PASSWORD"; 
$database = "freechat";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

if ($conn->connect_error) {
    die("Lỗi kết nối database: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

