<?php
session_start();
include '../db_config.php';

if (!isset($_SESSION['user'])) {
    http_response_code(403); // 403 Forbidden
    echo json_encode(["success" => false, "message" => "Kullanıcı giriş yapmamış."]);
    exit;
}

// Kullanıcı bilgilerini al
$username = $_SESSION['user']['username'];
$cari = $_SESSION['user']['cari'];
$user_id = $_SESSION['user']['usersId']; // Kullanıcı ID'sini oturumdan al

$messages = [];

// Yönetici (PLASİYER) ise, tüm mesajları al
if ($cari === 'PLASİYER') {
    $query = "SELECT sender_id, receiver_id, message, created_at, role, username FROM chat_messages 
              WHERE receiver_id IS NULL OR receiver_id = ? 
              ORDER BY created_at ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id); // Yöneticinin ID'siyle mesajları al
} else {
    // Normal kullanıcı için yalnızca kendi mesajlarını ve admin mesajlarını al
    $query = "SELECT sender_id, receiver_id, message, created_at, role, username FROM chat_messages 
              WHERE sender_id = ? OR (receiver_id IS NULL AND role = 'admin') 
              ORDER BY created_at ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id); // Kullanıcının ID'siyle mesajları al
}

$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($messages, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
