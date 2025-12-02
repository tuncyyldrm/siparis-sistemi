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

$data = json_decode(file_get_contents('php://input'), true);

$orderId = $conn->real_escape_string($data['id']);

$sql = "DELETE FROM orders WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);

if ($stmt->execute()) {
    echo json_encode(["success" => "Sipariş başarıyla silindi."]);
} else {
    echo json_encode(["error" => "Sipariş silinirken hata oluştu."]);
}

$stmt->close();
$conn->close();
?>
