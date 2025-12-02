<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../db_config.php';

// Oturum kontrolü ve rol doğrulama
if (!isset($_SESSION['user']) || $_SESSION['user']['cari'] !== 'PLASİYER') {
    header('Location: ../');
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$orderId = $conn->real_escape_string($data['id']);
$fullname = $conn->real_escape_string($data['fullname']);
$phone = $conn->real_escape_string($data['phone']);
$email = $conn->real_escape_string($data['email']);
$address = $conn->real_escape_string($data['address']);
$message = $conn->real_escape_string($data['message']);
$paymentType = $conn->real_escape_string($data['paymentType']);
$totalPrice = $conn->real_escape_string($data['totalPrice']);
$items = $data['items'];

// Sipariş bilgilerini güncelle
$sql = "UPDATE orders SET fullname = ?, phone = ?, email = ?, address = ?, message = ?, payment_type = ?, total_price = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssi", $fullname, $phone, $email, $address, $message, $paymentType, $totalPrice, $orderId);
$stmt->execute();

// Mevcut sipariş ürünlerini sil
$sqlDelete = "DELETE FROM order_items WHERE order_id = ?";
$stmtDelete = $conn->prepare($sqlDelete);
$stmtDelete->bind_param("i", $orderId);
$stmtDelete->execute();

// Sipariş ürünlerini güncelle
foreach ($items as $item) {
    if (isset($item['deleted']) && $item['deleted']) {
        // Silinmiş ürünleri işleme
        continue;
    }
    $item_name = $conn->real_escape_string($item['item_name']);
    $item_count = $conn->real_escape_string($item['item_count']);
    $item_price = $conn->real_escape_string($item['item_price']);

    $sqlItem = "INSERT INTO order_items (order_id, item_name, item_count, item_price) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE item_count = VALUES(item_count), item_price = VALUES(item_price)";
    $stmtItem = $conn->prepare($sqlItem);
    $stmtItem->bind_param("isid", $orderId, $item_name, $item_count, $item_price);
    $stmtItem->execute();
}

echo json_encode(["success" => true]);

$conn->close();
?>
