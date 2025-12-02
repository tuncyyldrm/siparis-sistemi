<?php
session_start();
include 'db_config.php';

// Hata gösterimi kapalı ve sadece günlüğe yazma
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', '/path/to/your/error.log'); // Günlük dosyasını belirleyin

if (!isset($_SESSION['user'])) {
    $error_message = "Kullanıcı giriş yapmamış.";
    error_log($error_message);
    // Hata mesajını kullanıcıya göndermeyi geçici olarak kapalı bırakıyoruz.
    // header('Content-Type: application/json');
    // echo json_encode(["error" => $error_message]);
    exit;
}

$user_id = $_SESSION['user']['usersId'] ?? null;

if ($user_id === null) {
    $error_message = "Kullanıcı ID'si mevcut değil.";
    error_log($error_message);
    // Hata mesajını kullanıcıya göndermeyi geçici olarak kapalı bırakıyoruz.
    // header('Content-Type: application/json');
    // echo json_encode(["error" => $error_message]);
    exit;
}

$input = file_get_contents('php://input');
$cart = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $error_message = "Geçersiz JSON formatı: " . json_last_error_msg();
    error_log($error_message);
    // Hata mesajını kullanıcıya göndermeyi geçici olarak kapalı bırakıyoruz.
    // header('Content-Type: application/json');
    // echo json_encode(["error" => $error_message]);
    exit;
}

function clearCart($conn, $user_id) {
    $stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("s", $user_id);
        if (!$stmt->execute()) {
            $error_message = "Sepet temizleme hatası: " . $stmt->error;
            error_log($error_message);
            // Hata mesajını kullanıcıya göndermeyi geçici olarak kapalı bırakıyoruz.
            // header('Content-Type: application/json');
            // echo json_encode(["error" => $error_message]);
            exit;
        }
        $stmt->close();
    } else {
        $error_message = "Hazırlama hatası: " . $conn->error;
        error_log($error_message);
        // Hata mesajını kullanıcıya göndermeyi geçici olarak kapalı bırakıyoruz.
        // header('Content-Type: application/json');
        // echo json_encode(["error" => $error_message]);
        exit;
    }
}

function addToCart($conn, $user_id, $cart) {
    $stmt = $conn->prepare("INSERT INTO carts (user_id, product_name, product_price, quantity, payment_type, added_at) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        foreach ($cart as $item) {
            if (isset($item['name'], $item['price'], $item['quantity'], $item['payment_type'], $item['addedAt'])) {
                $stmt->bind_param("ssdiss", $user_id, $item['name'], $item['price'], $item['quantity'], $item['payment_type'], $item['addedAt']);

                if (!$stmt->execute()) {
                    $error_message = "Sepet ekleme hatası: " . $stmt->error;
                    error_log($error_message);
                    // Hata mesajını kullanıcıya göndermeyi geçici olarak kapalı bırakıyoruz.
                    // header('Content-Type: application/json');
                    // echo json_encode(["error" => $error_message]);
                    exit;
                }
            } else {
                $error_message = "Eksik ürün verisi.";
                error_log($error_message);
                // Hata mesajını kullanıcıya göndermeyi geçici olarak kapalı bırakıyoruz.
                // header('Content-Type: application/json');
                // echo json_encode(["error" => $error_message]);
                exit;
            }
        }
        $stmt->close();
    } else {
        $error_message = "Hazırlama hatası: " . $conn->error;
        error_log($error_message);
        // Hata mesajını kullanıcıya göndermeyi geçici olarak kapalı bırakıyoruz.
        // header('Content-Type: application/json');
        // echo json_encode(["error" => $error_message]);
        exit;
    }
}

clearCart($conn, $user_id);
addToCart($conn, $user_id, $cart);

header('Content-Type: application/json');
echo json_encode(["success" => true]);
$conn->close();
?>
