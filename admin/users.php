<?php
session_start();
include '../db_config.php';

// Oturum kontrolü ve rol doğrulama
if (!isset($_SESSION['user']) || $_SESSION['user']['cari'] !== 'PLASİYER') {
    header('Location: ../');
    exit();
}

// Kullanıcı ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $cari = $_POST['cari'];
    $displayName = $_POST['displayName'];
    $phoneNumber = $_POST['phoneNumber'];
    $address = $_POST['address'];
    $role = $_POST['role'];  // Rol bilgisi de formdan alınacak

    $hashedPassword = $password; // hashleme yapmak istemiyorum, aynı kalsın

    // usersId'yi username ile dolduruyoruz
    $sql = "INSERT INTO users (username, password, cari, displayName, phoneNumber, address, usersId, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssss', $username, $hashedPassword, $cari, $displayName, $phoneNumber, $address, $username, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Yeni kullanıcı başarıyla eklendi.');</script>";
    } else {
        echo "<script>alert('Kullanıcı eklenirken hata oluştu: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// Kullanıcı güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $cari = $_POST['cari'];
    $displayName = $_POST['displayName'];
    $phoneNumber = $_POST['phoneNumber'];
    $address = $_POST['address'];
    $role = $_POST['role'];  // Role bilgisi

    if (empty($password)) {
        $sql = "UPDATE users SET cari = ?, displayName = ?, phoneNumber = ?, address = ?, role = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $cari, $displayName, $phoneNumber, $address, $role, $username);
    } else {
        $hashedPassword = $password; // hashleme yapmak istemiyorum, aynı kalsın
        $sql = "UPDATE users SET password = ?, cari = ?, displayName = ?, phoneNumber = ?, address = ?, role = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssss', $hashedPassword, $cari, $displayName, $phoneNumber, $address, $role, $username);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Kullanıcı başarıyla güncellendi.');</script>";
    } else {
        echo "<script>alert('Kullanıcı güncellenirken hata oluştu: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}


// Kullanıcı silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $username = $_POST['userId']; // Müşteri Kodu silme işlemi için alınmalı
    
    $sql = "DELETE FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    
    if ($stmt->execute()) {
        echo "<script>alert('Kullanıcı başarıyla silindi.');</script>";
    } else {
        echo "<script>alert('Kullanıcı silinirken hata oluştu: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';
$roleFilter = isset($_GET['roleFilter']) ? $_GET['roleFilter'] : '';

$sql = "SELECT * FROM users WHERE 1=1";  // Sorguyu başlatıyoruz

// Arama terimi varsa
if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR displayName LIKE ?)";
}

// Rol filtrelemesi varsa
if (!empty($roleFilter)) {
    $sql .= " AND role = ?";
}

// Sorguyu hazırlıyoruz
$stmt = $conn->prepare($sql);

// Parametreleri bağlama
if (!empty($search) && !empty($roleFilter)) {
    $stmt->bind_param('sss', $search, $search, $roleFilter);
} elseif (!empty($search)) {
    $stmt->bind_param('ss', $search, $search);
} elseif (!empty($roleFilter)) {
    $stmt->bind_param('s', $roleFilter);
}

if (!$stmt->execute()) {
    error_log("Execute hatası: " . $stmt->error);
    echo "Sunucu hatası.";
    exit();
}

$result = $stmt->get_result();


// HTML içeriği
echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Yiğit Kontak Sipariş - Kullanıcı Yönetimi</title>
    <style>
    /* Genel Stiller */
    body {
        background-color: #f4f4f4;

    }
    h1 {
        text-align: center;
        margin-top: 20px;
    }
    .button {
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-align: center;
        display: inline-block;
        text-decoration: none;
    }
    .button:hover {
        background-color: #45a049;
    }

    /* Popup Stilleri */
    .popup {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
        padding-top: 60px;
    }
    .popup-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 90%;
        max-width: 600px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .popup-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }
    .popup-close:hover,
    .popup-close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    /* Form Stilleri */
    form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    label {
        font-weight: bold;
        margin-bottom: 5px;
    }
    input[type='text'] {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    button[type='submit'] {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
    }
    button[type='submit']:hover {
        background-color: #45a049;
    }

/* Kullanıcıların bulunduğu ana konteyner */
.users {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    padding: 20px;
}

/* Her bir kullanıcı kartı */
.user {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 10px;
    width: 300px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
	display: flex;
    flex-wrap: wrap;
    flex-direction: column;
    justify-content: space-between;
}

/* Kullanıcı bilgileri */
.user strong {
    color: #333;
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.user div {
    margin-bottom: 5px;
}
    
	
	
	        @media (max-width: 600px) {

        }

</style>

    <script>
function openPopup(popupId, userData = {}) {
    const popup = document.getElementById(popupId);
    if (popupId.startsWith('updateUserPopup')) {
        document.getElementById('update_username').value = userData.username || '';
        document.getElementById('update_password').value = userData.password || '';
        document.getElementById('update_cari').value = userData.cari || '';
        document.getElementById('update_displayName').value = userData.displayName || '';
        document.getElementById('update_phoneNumber').value = userData.phoneNumber || '';
        document.getElementById('update_address').value = userData.address || '';
        document.getElementById('update_role').value = userData.role || 'user';
    }
    popup.style.display = 'block';
}


        function closePopup(popupId) {
            const popup = document.getElementById(popupId);
            popup.style.display = 'none';
        }
    </script>
</head>
<body>
	<a href='../admin/' class='button'>Geri</a>
    <button class='button' onclick='openPopup(\"addUserPopup\")'>Yeni Kullanıcı Ekle</button>
    <h1>Kullanıcı Yönetimi</h1>
    <div id='addUserPopup' class='popup'>
        <div class='popup-content'>
            <span class='popup-close' onclick='closePopup(\"addUserPopup\")'>&times;</span>
            <h2>Yeni Kullanıcı Ekle</h2>
            <form method='post'>
                <input type='hidden' name='action' value='add'>
                <label for='username'>Müşteri Kodu:</label>
                <input type='text' id='username' name='username' required>
                <label for='password'>Şifre:</label>
                <input type='text' id='password' name='password'>
                <label for='cari'>Cari:</label>
                <input type='text' id='cari' name='cari' required>
                <label for='displayName'>Görünen Ad:</label>
                <input type='text' id='displayName' name='displayName'>
                <label for='phoneNumber'>Telefon Numarası:</label>
                <input type='text' id='phoneNumber' name='phoneNumber'>
                <label for='address'>Adres:</label>
                <input type='text' id='address' name='address'>
				<label for='role'>Rol:</label>
<select id='role' name='role' required>
    <option value='ant'>ANT</option>
    <option value='genel'>GENEL</option>
    <option value='anttpt'>ANTTPT</option>
    <option value='aratpt'>ARATPT</option>
    <option value='anatpt'>ANATPT</option>
</select>

                <button type='submit'>Ekle</button>
            </form>
        </div>
    </div>

    <div id='updateUserPopup' class='popup'>
        <div class='popup-content'>
            <span class='popup-close' onclick='closePopup(\"updateUserPopup\")'>&times;</span>
            <h2>Kullanıcıyı Güncelle</h2>
            <form method='post'>
                <input type='hidden' name='action' value='update'>
                <label >Müşteri Kodu:</label>
                <input type='text' id='update_username' name='username' >
                <label for='update_password'>Şifre:</label>
                <input type='text' id='update_password' name='password'>
                <label for='update_cari'>Cari:</label>
                <input type='text' id='update_cari' name='cari'>
                <label for='update_displayName'>Görünen Ad:</label>
                <input type='text' id='update_displayName' name='displayName'>
                <label for='update_phoneNumber'>Telefon Numarası:</label>
                <input type='text' id='update_phoneNumber' name='phoneNumber'>
                <label for='update_address'>Adres:</label>
                <input type='text' id='update_address' name='address'>
				<label for='update_role'>Rol:</label>
<select id='update_role' name='role'>
    <option value='ant'>ANT</option>
    <option value='genel'>GENEL</option>
    <option value='anttpt'>ANTTPT</option>
    <option value='aratpt'>ARATPT</option>
    <option value='anatpt'>ANATPT</option>
</select>

                <button type='submit'>Güncelle</button>
            </form>
        </div>
    </div>
	

<form method='get' action=''>
    <label for='search'>Kullanıcı Ara:</label>
    <input type='text' id='search' name='search' value='" . (isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '') . "'>

    <label for='roleFilter'>Rol Filtrele:</label>
    <select id='roleFilter' name='roleFilter'>
        <option value=''>Hepsi</option>
        <option value='ant' " . (isset($_GET['roleFilter']) && $_GET['roleFilter'] == 'ANT' ? 'selected' : '') . ">ANT</option>
        <option value='genel' " . (isset($_GET['roleFilter']) && $_GET['roleFilter'] == 'GENEL' ? 'selected' : '') . ">GENEL</option>
        <option value='anttpt' " . (isset($_GET['roleFilter']) && $_GET['roleFilter'] == 'ANTTPT' ? 'selected' : '') . ">ANTTPT</option>
        <option value='aratpt' " . (isset($_GET['roleFilter']) && $_GET['roleFilter'] == 'ARATPT' ? 'selected' : '') . ">ARATPT</option>
        <option value='anatpt' " . (isset($_GET['roleFilter']) && $_GET['roleFilter'] == 'ANATPT' ? 'selected' : '') . ">ANATPT</option>
    </select>

    <button type='submit' class='button'>Ara/Filtrele</button>
</form>

<div class='users'>
";


        while ($row = $result->fetch_assoc()) {
            echo "
<div class='user'>
    <div style='padding:5px'> <strong>Müşteri Kodu</strong><div>{$row['username']}</div></div>
    <div style='padding:5px'> <strong>Şifre</strong><div>{$row['password']}</div></div>
    <div style='padding:5px'> <strong>Cari</strong><div>{$row['cari']}</div></div>
    <div style='padding:5px'> <strong>Görünen Ad</strong><div>{$row['displayName']}</div></div>
    <div style='padding:5px'> <strong>Telefon Numarası</strong><div>{$row['phoneNumber']}</div></div>
    <div style='padding:5px'> <strong>Adres</strong><div>{$row['address']}</div></div>
    <div style='padding:5px'> <strong>Rol</strong><div>{$row['role']}</div></div>
    <div style='padding:5px'> <strong>İşlemler</strong><div>
        <button class='button' onclick='openPopup(\"updateUserPopup\", " . json_encode($row) . ")'>Güncelle</button>
        <form method='post' style='display:inline;'>
            <input type='hidden' name='action' value='delete'>
            <input type='hidden' name='userId' value='{$row['username']}'>
            <button type='submit' class='button' style='background-color: red;'>Sil</button>
        </form>
    </div></div>
</div>
";

        }

echo "
</div>
</body>
</html>";

$stmt->close();
$conn->close();
?>
