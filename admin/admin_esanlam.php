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

// Eş anlamlı ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_synonym'])) {
    $word = trim($_POST['word']);
    $synonym = trim($_POST['synonym']);

    if (!empty($word) && !empty($synonym)) {
        try {
            $sql = "INSERT INTO synonyms (word, synonym) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $word, $synonym);
            $stmt->execute();
            $message = "Eş anlamlı başarıyla eklendi.";
        } catch (Exception $e) {
            $message = "Eş anlamlı eklenirken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } else {
        $message = "Kelime ve eş anlamlısı boş olamaz.";
    }
}

// Eş anlamlı düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_synonym'])) {
    $id = intval($_POST['id']);
    $word = trim($_POST['word']);
    $synonym = trim($_POST['synonym']);

    if (!empty($word) && !empty($synonym)) {
        try {
            $sql = "UPDATE synonyms SET word = ?, synonym = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssi', $word, $synonym, $id);
            $stmt->execute();
            $message = "Eş anlamlı başarıyla güncellendi.";
        } catch (Exception $e) {
            $message = "Eş anlamlı güncellenirken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } else {
        $message = "Kelime ve eş anlamlısı boş olamaz.";
    }
}

// Eş anlamlı silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_synonym'])) {
    $id = intval($_POST['id']);

    if ($id > 0) {
        try {
            $sql = "DELETE FROM synonyms WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $message = "Eş anlamlı başarıyla silindi.";
        } catch (Exception $e) {
            $message = "Eş anlamlı silinirken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
            if (count($parts) === 2) {
                $word = trim($parts[0]);
                $synonym = trim($parts[1]);

                if (!empty($word) && !empty($synonym)) {
                    $sql = "INSERT INTO synonyms (word, synonym) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ss', $word, $synonym);
                    $stmt->execute();
                }
            }
        }
        $message = "Toplu veri başarıyla eklendi.";
    } catch (Exception $e) {
        $message = "Toplu veri eklenirken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Eş anlamlıları listeleme
$synonyms = [];
try {
    $sql = "SELECT * FROM synonyms ORDER BY id DESC"; // veya DESC eğer tersine sıralama istiyorsanız
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $synonyms[] = $row;
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
    <title>Admin Paneli - Eş Anlamlı Kelimeler</title>
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
        .synonym-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            position: relative;
        }
        .synonym-card div {
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
    <h1>Eş Anlamlı Kelimeler Yönetimi</h1>

    <!-- Eş anlamlı ekleme ve toplu veri ekleme butonları -->
    <button class="button" onclick="openModal('add-modal')">Eş Anlamlı Ekle</button>
    <button class="button" onclick="openModal('bulk-modal')">Toplu Veri Ekle</button>

    <!-- Eş anlamlıları listele -->
    <h2>Eş Anlamlılar</h2>
	<!-- Arama Kutu ve Sonuçları -->
<div class="form-group">
    <label for="search">Arama</label>
    <input type="text" id="search" onkeyup="searchSynonyms()" placeholder="Kelime veya eş anlamlı girin...">
</div>
<div id="synonyms-container">
    <?php if (!empty($synonyms)): ?>
        <?php foreach ($synonyms as $synonym): ?>
            <div class="synonym-card">
                <div class="word"><strong>Kelime:</strong> <?= htmlspecialchars($synonym['word'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="synonym"><strong>Eş Anlamlı:</strong> <?= htmlspecialchars($synonym['synonym'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="actions">
                    <!-- Silme Butonu -->
                    <button class="button" onclick="openModal('delete-modal', <?= htmlspecialchars($synonym['id'], ENT_QUOTES, 'UTF-8'); ?>)">Sil</button>
                    <!-- Düzenleme Butonu -->
                    <button class="button" onclick="openEditModal('edit-modal', <?= htmlspecialchars($synonym['id'], ENT_QUOTES, 'UTF-8'); ?>, '<?= htmlspecialchars($synonym['word'], ENT_QUOTES, 'UTF-8'); ?>', '<?= htmlspecialchars($synonym['synonym'], ENT_QUOTES, 'UTF-8'); ?>')">Düzenle</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Veritabanında eş anlamlı kelime bulunmamaktadır.</p>
    <?php endif; ?>
</div>


    <!-- Eş anlamlı ekleme Modalı -->
    <div id="add-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('add-modal')">&times;</span>
            <h2>Eş Anlamlı Ekle</h2>
			
            <form method="post" action="">
                <div class="form-group">
                    <label for="word">Kelime</label>
                    <input type="text" id="word" name="word" required>
                </div>
                <div class="form-group">
                    <label for="synonym">Eş Anlamlı</label>
                    <input type="text" id="synonym" name="synonym" required>
                </div>
                <button type="submit" name="add_synonym" class="button">Ekle</button>
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
                    <label for="bulk_data">Verileri Yapıştırın (Her satıra bir eş anlamlı çift):</label>
                    <textarea id="bulk_data" name="bulk_data" placeholder="Her satıra bir eş anlamlı kelime çiftini yazın (örneğin: ÇİFT, CIFT)" required></textarea>
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
            <p>Bu eş anlamlı kelimeyi silmek istediğinizden emin misiniz?</p>
            <form method="post" action="">
                <input type="hidden" id="delete-id" name="id">
                <button type="submit" name="delete_synonym" class="button">Sil</button>
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
                    <label for="edit-word">Kelime</label>
                    <input type="text" id="edit-word" name="word" required>
                </div>
                <div class="form-group">
                    <label for="edit-synonym">Eş Anlamlı</label>
                    <input type="text" id="edit-synonym" name="synonym" required>
                </div>
                <button type="submit" name="edit_synonym" class="button">Güncelle</button>
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

        function openEditModal(modalId, id, word, synonym) {
            document.getElementById(modalId).style.display = "block";
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-word').value = word;
            document.getElementById('edit-synonym').value = synonym;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }
		
		
		
		function searchSynonyms() {
    var input = document.getElementById('search').value.toLowerCase();
    var synonymsContainer = document.getElementById('synonyms-container');
    
    // İstemci tarafı filtreleme
    var cards = document.querySelectorAll('.synonym-card');
    cards.forEach(function(card) {
        var word = card.querySelector('.word').innerText.toLowerCase();
        var synonym = card.querySelector('.synonym').innerText.toLowerCase();
        if (word.includes(input) || synonym.includes(input)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

    </script>
</body>
</html>