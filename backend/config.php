<?php
session_start();
$host = 'localhost';
$dbname = 'finance_tracker';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

$categories = [
    'expense' => ['อาหาร', 'ลงทุน' , 'เดินทาง', 'ช้อปปิ้ง', 'บิล', 'สุขภาพ', 'บันเทิง', 'คืน' , 'อื่นๆ'],
    'income' => ['เงินรายวัน', 'เงินเดือน', 'ธุรกิจ', 'ลงทุน', 'ยืม' , 'อื่นๆ']
];
?>