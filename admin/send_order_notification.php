<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// PHPMailer sınıflarını dahil et
require '../vendor/autoload.php'; // Yolunuzu kontrol edin
function sendOrderNotification($to, $orderDetails) {
    $mail = new PHPMailer(true);

    try {
        // Sunucu ayarları
        $mail->isSMTP();
        $mail->Host = 'smtp.yandex.com'; // Yandex SMTP sunucusu
        $mail->SMTPAuth = true;
        $mail->Username = 'tuncyyldrm@yandex.com'; // Yandex e-posta adresiniz
        $mail->Password = 'Yildirim32'; // Yandex e-posta şifreniz
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL kullanımı
        $mail->Port = 465; // Port numarası

        // Alıcılar
        $mail->setFrom('tuncyyldrm@gmail.com', 'Sipariş Bildirimi'); // Gönderen bilgileri
        $mail->addAddress($to); // Alıcı e-posta adresi

        // İçerik
        $mail->isHTML(true);
        $mail->Subject = 'Yeni Sipariş Bildirimi';
        $mail->Body    = "<h1>Yeni Sipariş Alındı</h1><p>Detaylar: $orderDetails</p>";
        
        $mail->send();
        echo 'Sipariş bildirimi başarıyla gönderildi.';
    } catch (Exception $e) {
        echo "E-posta gönderilemedi. Hata: {$mail->ErrorInfo}";
    }
}

// Sipariş detaylarını gönder
$orderDetails = "Sipariş Numarası: 12345<br>Müşteri: Ahmet Yılmaz<br>Toplam Tutar: 100 TL";
sendOrderNotification('tuncyyldrm@gmail.com', $orderDetails); // Alıcı e-posta adresini değiştirin
?>
