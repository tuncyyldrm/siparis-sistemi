<?php
session_start();
include '../db_config.php';

// Hata raporlamayı etkinleştir
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user'])) {
    echo json_encode(["success" => false, "message" => "Kullanıcı giriş yapmamış."]);
    exit;
}

$username = $_SESSION['user']['username'];
$cari = $_SESSION['user']['cari'];
$user_id = $_SESSION['user']['usersId'];

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['message'])) {
    $message = $data['message'];

    if (empty($message)) {
        echo json_encode(["success" => false, "message" => "Mesaj boş olamaz."]);
        exit;
    }

    // Mesajı veritabanına kaydet
    $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message, role, username) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $receiver_id = null; // Alıcıyı null bırakıyoruz
        $role = $cari === 'PLASİYER' ? 'admin' : 'user'; // Kullanıcının rolü
        $stmt->bind_param("iisss", $user_id, $receiver_id, $message, $role, $username);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Mesaj kaydetme hatası: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Hazırlama hatası."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Mesaj eksik."]);
}

$conn->close();
?>
