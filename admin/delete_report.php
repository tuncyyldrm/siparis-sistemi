<?php
// Çıkış tamponlamayı başlat
ob_start();

// Hata raporlamayı aç
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../db_config.php'; // Veritabanı bağlantısını içeren dosya

// Oturum kontrolü ve rol doğrulama
if (!isset($_SESSION['user']) || $_SESSION['user']['cari'] !== 'PLASİYER') {
    header('Location: ../'); // Yetkisiz erişim veya oturum açmamış kullanıcıyı anasayfaya yönlendir
    exit();
}

// Silme işlemi için searchId'yi al
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['searchId'])) {
    $searchId = $conn->real_escape_string($_POST['searchId']);

    // SQL sorgusu
    $sql = "DELETE FROM searches WHERE searchId = '$searchId'";

    if ($conn->query($sql) === TRUE) {
        // Silme başarılı
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        // Hata ile karşılaşırsa, hata mesajı
        $error = "Hata: " . $conn->error;
    }
} else {
    // Geçersiz istek
    $error = "Geçersiz istek.";
}

// Veritabanı bağlantısını kapat
$conn->close();

// Hata mesajlarını ekrana yazdır
if (isset($error)) {
    echo $error;
}

// Çıkış tamponlamayı bitir ve gönder
ob_end_flush();
?>
