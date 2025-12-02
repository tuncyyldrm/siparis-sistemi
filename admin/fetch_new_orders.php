<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../db_config.php';

// Son 5 dakika içinde eklenen siparişleri getir
$sql = "SELECT * FROM orders WHERE created_at >= NOW() - INTERVAL 5 MINUTE";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => "Database query failed: " . $conn->error]);
    $conn->close();
    exit();
}


header('Content-Type: application/json');
echo json_encode($newOrders);

$conn->close();
?>
