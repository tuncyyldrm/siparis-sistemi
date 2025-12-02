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

if (isset($_GET['id'])) {
    $orderId = intval($_GET['id']);

    // Sipariş bilgilerini al
    $sqlOrder = "SELECT * FROM orders WHERE id = ?";
    $stmtOrder = $conn->prepare($sqlOrder);
    $stmtOrder->bind_param("i", $orderId);
    $stmtOrder->execute();
    $order = $stmtOrder->get_result()->fetch_assoc();

    // Sipariş içindeki ürünleri al
    $sqlItems = "SELECT * FROM order_items WHERE order_id = ?";
    $stmtItems = $conn->prepare($sqlItems);
    $stmtItems->bind_param("i", $orderId);
    $stmtItems->execute();
    $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['order' => $order, 'items' => $items]);

    $stmtOrder->close();
    $stmtItems->close();
}

$conn->close();
?>
