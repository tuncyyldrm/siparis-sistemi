<?php
session_start();
header('Content-Type: application/json');

$response = ['loggedIn' => false];

if (isset($_SESSION['user'])) {
    // Son etkileşim zamanı
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $_SESSION['expire']) {
        // Oturum süresi dolmuşsa
        session_unset();     // Oturumu sıfırla
        session_destroy();   // Oturumu yok et
    } else {
        // Kullanıcı oturumda
        $_SESSION['LAST_ACTIVITY'] = time(); // Son etkileşim zamanını güncelle
        $response['loggedIn'] = true;
    }
}

// JSON yanıtını döndür
echo json_encode($response);

?>
