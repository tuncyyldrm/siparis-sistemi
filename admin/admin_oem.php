<?php
// Hata raporlamayı aç
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../db_config.php'; // Veritabanı bağlantısını içeren dosya

// Veritabanı bağlantısında UTF-8 kodlamasını ayarlayın
$conn->set_charset("utf8mb4");

// Oturum kontrolü ve rol doğrulama
if (!isset($_SESSION['user']) || $_SESSION['user']['cari'] !== 'PLASİYER') {
    header('Location: ../'); // Yetkisiz erişim veya oturum açmamış kullanıcıyı anasayfaya yönlendir
    exit();
}

// Oem ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_oem_number'])) {
    $stokkodu = trim($_POST['stokkodu']);
    $oem_number = trim($_POST['oem_number']);
    $oem_type = trim($_POST['oem_type']); // Eklenen alan

    if (!empty($stokkodu) && !empty($oem_number) && !empty($oem_type)) {
        try {
            $sql = "INSERT INTO oem_numbers (stokkodu, oem_number, oem_type) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $stokkodu, $oem_number, $oem_type);
            $stmt->execute();
            $message = "Oem başarıyla eklendi.";
        } catch (Exception $e) {
            $message = "Oem eklenirken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } else {
        $message = "Stok Kodu, Oem ve Oem Tipi boş olamaz.";
    }
}

// Oem düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_oem_number'])) {
    $id = intval($_POST['id']);
    $stokkodu = trim($_POST['stokkodu']);
    $oem_number = trim($_POST['oem_number']);
    $oem_type = trim($_POST['oem_type']); // Eklenen alan

    if (!empty($stokkodu) && !empty($oem_number) && !empty($oem_type)) {
        try {
            $sql = "UPDATE oem_numbers SET stokkodu = ?, oem_number = ?, oem_type = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssi', $stokkodu, $oem_number, $oem_type, $id);
            $stmt->execute();
            $message = "Oem başarıyla güncellendi.";
        } catch (Exception $e) {
            $message = "Oem güncellenirken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } else {
        $message = "Stok Kodu, Oem ve Oem Tipi boş olamaz.";
    }
}

// Oem silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_oem_number'])) {
    $id = intval($_POST['id']);

    if ($id > 0) {
        try {
            $sql = "DELETE FROM oem_numbers WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $message = "Oem başarıyla silindi.";
        } catch (Exception $e) {
            $message = "Oem silinirken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } else {
        $message = "Geçersiz ID.";
    }
}

// Toplu veri ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_bulk'])) {
    $bulk_data = trim($_POST['bulk_data']);
    $rows = explode("\n", $bulk_data);

    try {
        foreach ($rows as $row) {
            $parts = explode(",", trim($row));
            if (count($parts) === 3) { // Üç parça olmalı (stokkodu, oem_number, oem_type)
                $stokkodu = trim($parts[0]);
                $oem_number = trim($parts[1]);
                $oem_type = trim($parts[2]);

                if (!empty($stokkodu) && !empty($oem_number) && !empty($oem_type)) {
                    $sql = "INSERT INTO oem_numbers (stokkodu, oem_number, oem_type) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('sss', $stokkodu, $oem_number, $oem_type);
                    $stmt->execute();
                }
            }
        }
        $message = "Toplu veri başarıyla eklendi.";
    } catch (Exception $e) {
        $message = "Toplu veri eklenirken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Oemları listeleme
$oem_numbers = [];
try {
    $sql = "SELECT * FROM oem_numbers ORDER BY id DESC"; // veya DESC eğer tersine sıralama istiyorsanız
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $oem_numbers[] = $row;
        }
    }
} catch (Exception $e) {
    $message = "Veriler alınırken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}


$conn->close();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Oem Stok Koduler</title>
    <style>
        /* Stil ayarları */
        .button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            display: inline-block;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #45a049;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .message {
            color: green;
            margin: 10px 0;
        }
        .error {
            color: red;
            margin: 10px 0;
        }
        .oem_number-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            position: relative;
        }
        .oem_number-card div {
            margin-bottom: 5px;
        }
        .actions {
            margin-top: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Geri butonu -->
    <a href="../admin/" class="button">Geri</a>
    <h1>Stok Kodu Oem Yönetimi</h1>

    <!-- Oem ekleme ve toplu veri ekleme butonları -->
    <button class="button" onclick="openModal('add-modal')">Oem Ekle</button>
    <button class="button" onclick="openModal('bulk-modal')">Toplu Oem Ekle</button>

    <!-- Oemları listele -->
    <h2>Oem</h2>
	<!-- Arama Kutu ve Sonuçları -->
<div class="form-group">
    <label for="search">Arama</label>
    <input type="text" id="search" onkeyup="searchoem_numbers()" placeholder="Stok Kodu veya Oem girin...">
</div>
<div id="oem_numbers-container">
    <?php if (!empty($oem_numbers)): ?>
        <?php foreach ($oem_numbers as $oem_number): ?>
            <div class="oem_number-card">
                <div class="stokkodu"><strong>Stok Kodu:</strong> <?= htmlspecialchars($oem_number['stokkodu'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="oem_number"><strong>Oem:</strong> <?= htmlspecialchars($oem_number['oem_number'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="oem_type"><strong>Oem Tipi:</strong> <?= htmlspecialchars($oem_number['oem_type'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="actions">
                    <!-- Silme Butonu -->
                    <button class="button" onclick="openModal('delete-modal', <?= htmlspecialchars($oem_number['id'], ENT_QUOTES, 'UTF-8'); ?>)">Sil</button>
                    <!-- Düzenleme Butonu -->
                    <button class="button" onclick="openEditModal('edit-modal', <?= htmlspecialchars($oem_number['id'], ENT_QUOTES, 'UTF-8'); ?>, '<?= htmlspecialchars($oem_number['stokkodu'], ENT_QUOTES, 'UTF-8'); ?>', '<?= htmlspecialchars($oem_number['oem_number'], ENT_QUOTES, 'UTF-8'); ?>', '<?= htmlspecialchars($oem_number['oem_type'], ENT_QUOTES, 'UTF-8'); ?>')">Düzenle</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Veritabanında Oem Stok Kodu bulunmamaktadır.</p>
    <?php endif; ?>
</div>


    <!-- Oem ekleme Modalı -->
    <div id="add-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('add-modal')">&times;</span>
            <h2>Oem Ekle</h2>
			
            <form method="post" action="">
                <div class="form-group">
                    <label for="stokkodu">Stok Kodu</label>
                    <input type="text" id="stokkodu" name="stokkodu" required>
                </div>
                <div class="form-group">
                    <label for="oem_number">Oem</label>
                    <input type="text" id="oem_number" name="oem_number" required>
                </div>
                <div class="form-group">
                    <label for="oem_number">Oem Tipi</label>
                    <input type="text" id="oem_type" name="oem_type" required>
                </div>
                <button type="submit" name="add_oem_number" class="button">Ekle</button>
            </form>
            <?php if (isset($message)): ?>
                <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toplu veri ekleme Modalı -->
    <div id="bulk-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('bulk-modal')">&times;</span>
            <h2>Toplu Veri Ekle</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="bulk_data">Verileri Yapıştırın (Her satıra bir Oem çift):</label>
                    <textarea id="bulk_data" name="bulk_data" placeholder="Her satıra bir Oem Stok Kodu çiftini yazın (örneğin: ÇİFT, CIFT)" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" name="upload_bulk" class="button">Toplu Ekle</button>
                </div>
                <?php if (isset($message)): ?>
                    <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Silme Modalı -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('delete-modal')">&times;</span>
            <h2>Silme Onayı</h2>
            <p>Bu Oem Stok Koduyi silmek istediğinizden emin misiniz?</p>
            <form method="post" action="">
                <input type="hidden" id="delete-id" name="id">
                <button type="submit" name="delete_oem_number" class="button">Sil</button>
                <button type="button" class="button" onclick="closeModal('delete-modal')">İptal</button>
            </form>
        </div>
    </div>

    <!-- Düzenleme Modalı -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('edit-modal')">&times;</span>
            <h2>Düzenleme</h2>
            <form method="post" action="">
                <input type="hidden" id="edit-id" name="id">
                <div class="form-group">
                    <label for="edit-stokkodu">Stok Kodu</label>
                    <input type="text" id="edit-stokkodu" name="stokkodu" required>
                </div>
                <div class="form-group">
                    <label for="edit-oem_number">Oem</label>
                    <input type="text" id="edit-oem_number" name="oem_number" required>
                </div>
                <div class="form-group">
                    <label for="edit-oem_number">Oem Tipi</label>
                    <input type="text" id="edit-oem_type" name="oem_type" required>
                </div>
                <button type="submit" name="edit_oem_number" class="button">Güncelle</button>
                <button type="button" class="button" onclick="closeModal('edit-modal')">İptal</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId, id) {
            document.getElementById(modalId).style.display = "block";
            if (modalId === 'delete-modal') {
                document.getElementById('delete-id').value = id;
            }
        }

function openEditModal(modalId, id, stokkodu, oem_number, oem_type) {
    document.getElementById(modalId).style.display = "block";
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-stokkodu').value = stokkodu;
    document.getElementById('edit-oem_number').value = oem_number;
    document.getElementById('edit-oem_type').value = oem_type; // Add this line
}
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }
function searchoem_numbers() {
    var input = document.getElementById('search').value.toLowerCase();
    var cards = document.querySelectorAll('.oem_number-card');
    
    cards.forEach(function(card) {
        var stokkodu = card.querySelector('.stokkodu').innerText.toLowerCase();
        var oem_number = card.querySelector('.oem_number').innerText.toLowerCase();
        var oem_type = card.querySelector('.oem_type').innerText.toLowerCase(); // Add this line
        
        if (stokkodu.includes(input) || oem_number.includes(input) || oem_type.includes(input)) { // Include oem_type in the condition
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
    </script>
</body>
</html>