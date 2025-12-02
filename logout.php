<?php
session_start();
session_unset();
session_destroy();

// Çerezi sil
setcookie('user', '', time() - 3600, '/');
echo 'Çıkış yapıldı.';
?>
