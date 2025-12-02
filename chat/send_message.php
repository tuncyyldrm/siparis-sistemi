<?php
session_start();
include '../db_config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(["success" => false, "message" => "Kullanıcı giriş yapmamış."]);
    exit;
}

$username = $_SESSION['user']['username'];
$cari     = $_SESSION['user']['cari'];

// Gönderici ve alıcı belirleme
if ($cari === 'PLASİYER') {
    $sender_id   = 'ADMIN';
    $receiver_id = $_POST['receiver_id'] ?? null;
    $role        = 'admin';
} else {
    $sender_id   = $cari;
    $receiver_id = 'ADMIN';
    $role        = 'user';
}

$message = $_POST['message'] ?? '';
$file_url = null;

// Dosya yükleme
$file_url = null;
if(isset($_FILES['file']) && $_FILES['file']['error']===0){
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if(in_array($ext, $allowed)){
        $uploadDir = 'uploads/'; // PUBLIC erişilebilir
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $newName = $uploadDir . uniqid() . '.' . $ext;
        if(move_uploaded_file($_FILES['file']['tmp_name'], $newName)){
            $file_url = $newName;
        } else {
            echo json_encode(["success"=>false,"message"=>"Dosya yüklenemedi."]);
            exit;
        }
    } else {
        echo json_encode(["success"=>false,"message"=>"Dosya türü izinli değil."]);
        exit;
    }
}

$stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message, file_url, role, username) 
                        VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $sender_id, $receiver_id, $message, $file_url, $role, $username);

if ($stmt->execute()) {
    echo json_encode([
        "success"     => true,
        "message_id"  => $conn->insert_id,
        "file_url"    => $file_url,
        "created_at"  => date("Y-m-d H:i:s")
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Mesaj kaydetme hatası: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
