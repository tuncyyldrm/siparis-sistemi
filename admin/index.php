<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../db_config.php';

// Oturum kontrolü ve rol doğrulama
if (!isset($_SESSION['user']) || $_SESSION['user']['cari'] !== 'PLASİYER') {
    header('Location: ../'); // Yetkisiz erişim veya oturum açmamış kullanıcıyı anasayfaya yönlendir
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-3FW3CNT4XK"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-3FW3CNT4XK');
    </script>
    <meta name="google" content="notranslate">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Admin</title>
    <script src="admin_dashboard_v0.5.js" defer></script>
    <link rel="stylesheet" href="admin_styles.css">
    <style>
.button {
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-align: center;
        display: inline-block;
        font-size: 16px;
        text-decoration: none;
        margin: 5px 0;
    }

    .button:hover {
        background-color: #45a049;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 16px;
        text-align: left;
    }

    th, td {
        padding: 6px;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: #f5f5f5;
    }

    /* Mobil uyumlu tasarım */
    @media (max-width: 768px) {
        .button {
            padding: 8px 16px;
            font-size: 14px;
        }

        table {
            font-size: 14px;
        }

        th, td {
            padding: 10px;
        }

        /* Sipariş detayları ve düzenleme bölümleri için düzenlemeler */
        #order-details-container, #edit-order-container {
            padding: 15px;
        }

        #order-details-container input, #order-details-container textarea {
            width: 100%;
            box-sizing: border-box;
        }

        /* Diğer stil düzenlemeleri */
        body {
            padding: 10px;
        }
    }

    @media (max-width: 480px) {
        .button {
            padding: 6px 12px;
            font-size: 12px;
        }

        table {
            font-size: 12px;
        }

        th, td {
            padding: 8px;
        }

        #order-details-container, #edit-order-container {
            padding: 10px;
        }

        #order-details-container input, #order-details-container textarea {
            font-size: 14px;
        }
    }
	.orders-container {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.order-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 16px;
    width: 49%;
    //max-width: 400px;
    box-sizing: border-box;
	display: flex;
    justify-content: space-between;
}

.order-info {
    margin-bottom: 0;
}

.order-info span {
    display: grid;
	grid-template-columns: 1fr 1fr; /* 2 sütunlu grid */
    margin-bottom: 4px;
}

.order-actions {
    display: flex;
    gap: 8px;
    flex-direction: column;
    justify-content: space-between;
}

.order-actions button {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.delete-btn {
    background: red;
    color: white;
}

@media (max-width: 768px) {
    .orders-container {
        flex-direction: column;
        align-items: center;
    }

    .order-card {
        width: 100%;
    }
}

		
    </style>
</head>
<body>

	
    <div id="orders-container">
    <a href="../" class="button">Ana Satış Sayfası</a>
	<a href="users.php" class="button">Kullanıcı Listesine Git</a>
	<a href="admin_search_reports.php" class="button">Arama Raporlarını Görüntüle</a>
	<a href="admin_cart.php" class="button">Müşteri Sepetleri</a>
	<a href="admin_esanlam.php" class="button">Eş Anlamlı Kelimeler</a>
	<a href="admin_birles.php" class="button">Stok Birleştir</a>
	<a href="admin_oem.php" class="button">Stok Oem No</a>

    <!-- Çevrimiçi kullanıcılar bölümü -->
    <div id="online-users-container">
        <h1>Oturum Açmış Kullanıcılar</h1>
        <table id="online-users-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Kullanıcı Adı</th>
                    <th style="width: 50%;">Cari</th>
                    <th style="width: 25%;">Son Giriş</th>
                    <th style="width: 5%;">Grup</th>
                </tr>
            </thead>
            <tbody id="online-users-list"></tbody>
        </table>
    </div>
        <h1>Siparişler</h1>
        <div id="orders-list"></div>
    </div>

    <div id="order-details-container" style="display: none;">
        <h1>Sipariş Detayları</h1>
        <div id="order-details"></div>
        <button onclick="goBack()">Geri</button>
    </div>

    <div id="edit-order-container" style="display: none;">
        <h1>        <button onclick="goBack()">Geri</button>Sipariş Düzenle</h1>
        <input type="hidden" id="edit-order-id">
        <p><label>Müşteri Kodu: <input type="text" id="edit-user_id" disabled></label></p>
        <p><label>Cari: <input type="text" id="edit-cari" disabled></label></p>
        <p><label>Ad Soyad: <input type="text" id="edit-fullname" disabled></label></p>
        <p><label>Telefon: <input type="text" id="edit-phone"></label></p>
        <p><label>E-posta: <input type="email" id="edit-email"></label></p>
        <p><label>Adres: <input type="text" id="edit-address"></label></p>
        <p><label>Mesaj: <textarea id="edit-message"></textarea></label></p>
        <p><label>Ödeme Tipi: <input type="text" id="edit-payment-type" disabled></label></p>
        <p><label>Toplam Fiyat: <input type="text" id="edit-total-price" disabled></label></p>
        <div id="edit-items-container"></div>
		<div id="add-item-form" style="display:none;">
    <h3>Yeni Ürün Ekle</h3>
    <label for="item-name">Ürün Adı:</label>
    <input type="text" id="item-name" />
    <label for="item-count">Miktar:</label>
    <input type="number" id="item-count" />
    <label for="item-price">Fiyat:</label>
    <input type="number" id="item-price" />
    <button type="button" id="add-item-button">Ekle</button>
    <button type="button" id="cancel-add-item-button">İptal</button>
</div>
		 <button class="copy-button">Kopyala</button>
        <button onclick="saveOrderChanges()">Değişiklikleri Kaydet</button>

    </div>


</body>
</html>
