<?php
session_start();
include '../db_config.php';

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Kullanıcı giriş yapmamış."]);
    exit;
}

$cari = $_SESSION['user']['cari'];
$username = $_SESSION['user']['username'];
$after_id = isset($_GET['after_id']) ? (int)$_GET['after_id'] : 0;
$before_id = isset($_GET['before_id']) ? (int)$_GET['before_id'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;

$messages = [];

if ($cari === 'PLASİYER') {
    // Admin: sadece seçili kullanıcı ile mesajları getir
    if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $selected_user = $_GET['user_id'];

        if ($after_id > 0) {
            // Yeni mesajlar
            $stmt = $conn->prepare(
                "SELECT * FROM chat_messages
                 WHERE ((sender_id = ? AND receiver_id = 'ADMIN') 
                    OR (sender_id = 'ADMIN' AND receiver_id = ?))
                 AND id > ?
                 ORDER BY id ASC"
            );
            $stmt->bind_param("ssi", $selected_user, $selected_user, $after_id);

        } elseif ($before_id > 0) {
            // Eski mesajlar
            $stmt = $conn->prepare(
                "SELECT * FROM (
                    SELECT * FROM chat_messages
                    WHERE ((sender_id = ? AND receiver_id = 'ADMIN') 
                        OR (sender_id = 'ADMIN' AND receiver_id = ?))
                    AND id < ?
                    ORDER BY id DESC
                    LIMIT ?
                ) sub
                ORDER BY id ASC"
            );
            $stmt->bind_param("ssii", $selected_user, $selected_user, $before_id, $limit);

        } else {
            // İlk yükleme: son $limit mesaj
            $stmt = $conn->prepare(
                "SELECT * FROM (
                    SELECT * FROM chat_messages
                    WHERE ((sender_id = ? AND receiver_id = 'ADMIN') 
                        OR (sender_id = 'ADMIN' AND receiver_id = ?))
                    ORDER BY id DESC
                    LIMIT ?
                ) sub
                ORDER BY id ASC"
            );
            $stmt->bind_param("ssi", $selected_user, $selected_user, $limit);
        }
    } else {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }
} else {
    // Normal kullanıcı
    if ($after_id > 0) {
        $stmt = $conn->prepare(
            "SELECT * FROM chat_messages
             WHERE ((sender_id = ? AND receiver_id = 'ADMIN') 
                OR (sender_id = 'ADMIN' AND receiver_id = ?))
             AND id > ?
             ORDER BY id ASC"
        );
        $stmt->bind_param("ssi", $cari, $cari, $after_id);

    } elseif ($before_id > 0) {
        $stmt = $conn->prepare(
            "SELECT * FROM (
                SELECT * FROM chat_messages
                WHERE ((sender_id = ? AND receiver_id = 'ADMIN') 
                    OR (sender_id = 'ADMIN' AND receiver_id = ?))
                AND id < ?
                ORDER BY id DESC
                LIMIT ?
            ) sub
            ORDER BY id ASC"
        );
        $stmt->bind_param("ssii", $cari, $cari, $before_id, $limit);

    } else {
        $stmt = $conn->prepare(
            "SELECT * FROM (
                SELECT * FROM chat_messages
                WHERE ((sender_id = ? AND receiver_id = 'ADMIN') 
                    OR (sender_id = 'ADMIN' AND receiver_id = ?))
                ORDER BY id DESC
                LIMIT ?
            ) sub
            ORDER BY id ASC"
        );
        $stmt->bind_param("ssi", $cari, $cari, $limit);
    }
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $sender_name = !empty($row['username']) ? $row['username'] : ($row['role'] === 'admin' ? 'ADMIN' : $row['sender_id']);
    $messages[] = [
        "id" => $row['id'],
        "sender" => $sender_name,
        "role" => $row['role'],
        "message" => $row['message'],
        "file_url" => !empty($row['file_url']) ? $row['file_url'] : null,
        "created_at" => $row['created_at']
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($messages, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
