<?php
session_start();
include 'db_config.php';

// Hata gösterimi kapalı ve sadece günlüğe yazma
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', '/path/to/your/error.log'); // Günlük dosyasını belirleyin


// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["error" => "Geçersiz veri."]);
    exit;
}

$userId = $conn->real_escape_string($data['username']);
$cari = $conn->real_escape_string($data['cari']);
$fullname = $conn->real_escape_string($data['fullname']);
$phone = $conn->real_escape_string($data['phone']);
$email = $conn->real_escape_string($data['email']);
$address = $conn->real_escape_string($data['address']);
$message = $conn->real_escape_string($data['message']);
$paymentType = $conn->real_escape_string($data['paymentType']);
$totalPrice = $conn->real_escape_string($data['totalPrice']);

$sql = "INSERT INTO orders (user_id, cari, fullname, phone, email, address, message, payment_type, total_price, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 3 HOUR))";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Hazırlama hatası: " . $conn->error]);
    exit;
}

$stmt->bind_param("sssssssss", $userId, $cari, $fullname, $phone, $email, $address, $message, $paymentType, $totalPrice);

if ($stmt->execute()) {
    $orderId = $stmt->insert_id;

    $sqlItem = "INSERT INTO order_items (order_id, item_name, item_count, item_price) VALUES (?, ?, ?, ?)";
    $stmtItem = $conn->prepare($sqlItem);
    if (!$stmtItem) {
        echo json_encode(["error" => "Hazırlama hatası: " . $conn->error]);
        exit;
    }

    foreach ($data['cartItems'] as $item) {
        if (isset($item['name'], $item['count'], $item['price'])) {
            $itemName = $conn->real_escape_string($item['name']);
            $itemCount = $conn->real_escape_string($item['count']);
            $itemPrice = $conn->real_escape_string($item['price']);

            $stmtItem->bind_param("isid", $orderId, $itemName, $itemCount, $itemPrice);
            $stmtItem->execute();
        } else {
            echo json_encode(["error" => "Eksik ürün verisi."]);
            exit;
        }
    }

    echo json_encode(["success" => "Sipariş başarıyla kaydedildi."]);
} else {
    echo json_encode(["error" => "Sipariş kaydedilemedi: " . $stmt->error]);
}



$stmt->close();
$stmtItem->close();
$conn->close();
?>
