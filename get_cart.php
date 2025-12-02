<?php
session_start();
include 'db_config.php';

// Hata gösterimi kapalı ve sadece günlüğe yazma
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', '/path/to/your/error.log'); // Günlük dosyasını belirleyin

header('Content-Type: application/json');

// Kullanıcı ID'sini al (oturum veya kimlik doğrulama ile)
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['usersId'])) {
    $error_message = "Kullanıcı giriş yapmamış veya geçersiz kullanıcı ID'si.";
    error_log($error_message);
    echo json_encode(['error' => $error_message]);
    exit;
}

$user_id = $_SESSION['user']['usersId'];

// Sepeti al
$query = "SELECT product_name, product_price, quantity FROM carts WHERE user_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    $error_message = "Sorgu hazırlama hatası: " . $conn->error;
    error_log($error_message);
    echo json_encode(['error' => $error_message]);
    exit;
}

$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
while ($row = $result->fetch_assoc()) {
    $cartItems[] = [
        'name' => $row['product_name'],
        'price' => floatval($row['product_price']),
        'quantity' => intval($row['quantity'])
    ];
}

// JSON olarak döndür
echo json_encode(['cart' => $cartItems]);

$stmt->close();
$conn->close();
?>
