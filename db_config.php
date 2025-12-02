<?php
// Hata raporlamayı aç
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Zaman dilimini ayarla
date_default_timezone_set('Europe/Istanbul');

// Veritabanı bağlantı bilgileri
$servername = "localhost";
$username = "yigit-otomotiv_b2b";
$password = "Yigit102030";
$dbname = "yigit-otomotiv_b2b";

// Veritabanı bağlantısını oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantı kontrolü
if ($conn->connect_error) {
    die("Bağlantı başarısız: " . $conn->connect_error);
}

// Karakter setini utf8mb4 olarak ayarla
if (!$conn->set_charset("utf8mb4")) {
    printf("Karakter seti ayarlanamadı: %s\n", $conn->error);
    exit();
}

// Bağlantı başarıyla kurulduktan sonra işlemler devam eder
?>
