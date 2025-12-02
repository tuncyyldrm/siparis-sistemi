<?php
session_start();
include '../db_config.php';

// Hata raporlamayı aç
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Şu anki zamanı al
$current_time = new DateTime();
$interval = new DateInterval('PT12H');  // 60 dakika
$threshold_time = $current_time->sub($interval)->format('Y-m-d H:i:s'); // Mikro saniyeleri hariç tutar

// SQL sorgusunu hazırla
$sql = "SELECT * FROM users WHERE lastOnline > ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $threshold_time);
$stmt->execute();
$result = $stmt->get_result();

// Çevrimiçi kullanıcıları al
$online_users = [];
while ($row = $result->fetch_assoc()) {
    $online_users[] = $row;
}

// JSON formatında yanıtı döndür
echo json_encode($online_users);

$stmt->close();
$conn->close();
?>
