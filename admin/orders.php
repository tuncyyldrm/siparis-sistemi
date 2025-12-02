<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../db_config.php';

// Oturum kontrolü ve rol doğrulama
if (!isset($_SESSION['user']) || $_SESSION['user']['cari'] !== 'PLASİYER') {
    header('Location: ../'); // Yetkisiz erişim veya oturum açmamış kullanıcıyı login sayfasına yönlendir
    exit();
}

$sql = "SELECT * FROM orders ORDER BY created_at DESC";
$result = $conn->query($sql);

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

header('Content-Type: application/json');
echo json_encode($orders);

$conn->close();
?>
