<?php
// Oturum ayarları
ini_set('session.gc_maxlifetime', 86400); // Oturumu 24 saat (86400 saniye) boyunca geçerli kıl
session_set_cookie_params(86400); // Çerez süresini de 24 saate ayarla
session_start(); // Oturumu başlat

include 'db_config.php';

// Zaman dilimini ayarla
date_default_timezone_set('Europe/Istanbul');
$current_time = date('Y-m-d H:i:s');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // SQL sorgusunu hazırla ve çalıştır
    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Kullanıcı bulundu
        $user = $result->fetch_assoc();
            // Kullanıcı bulundu ve şifre doğru
            $_SESSION['user'] = $user;
			
            // Son çevrimiçi tarih ve saatini güncelle
            $current_time = (new DateTime())->format('Y-m-d H:i:s');
            $update_sql = "UPDATE users SET lastOnline = ? WHERE username = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $current_time, $username);
            $update_stmt->execute();
            $update_stmt->close();
        
        // Çerez ayarla
        setcookie("user", json_encode($user, JSON_UNESCAPED_UNICODE), time() + (1 * 24 * 60 * 60), "/"); // 1 gün

        echo json_encode($_SESSION['user']);
    } else {
        echo json_encode(["error" => "Kullanıcı adı veya şifre yanlış."]);
    }

    $stmt->close();
    $conn->close();
}
?>