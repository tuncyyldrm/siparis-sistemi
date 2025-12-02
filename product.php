<?php
session_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
include 'db_config.php';

$servername = "localhost";
$username = "yigit-otomotiv_b2b";
$password = "Yigit102030";
$dbname = "yigit-otomotiv_b2b";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

// Ürün adını al
$productName = isset($_GET['productName']) ? $_GET['productName'] : '';

// Ürün bilgilerini almak için SQL sorgusunu oluştur
$productSql = "SELECT * FROM product WHERE stokkodu = ? LIMIT 1";
$productStmt = $pdo->prepare($productSql);
$productStmt->execute([$productName]);
$productResult = $productStmt->fetch(PDO::FETCH_ASSOC);

if ($productResult) {
    // Ürün bilgilerini değişkenlere al
    $productName = htmlspecialchars($productResult["stokkodu"]);
    $productPrice = floatval($productResult["ant"]);
    $productImage = htmlspecialchars($productResult["resimurl"]) ?: 'default-image-url';
    $productInfo = htmlspecialchars($productResult["aciklama"]);
    $productKmpny = htmlspecialchars($productResult["kampanya"]);
// Kampanya yüzdesine göre fiyatı hesapla (ant fiyatı üzerinden indirim uygula)
if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
    $kampanyaYuzdesi = floatval($productResult["kampanya"]);
    $productFyt = $productPrice - ($productPrice * $kampanyaYuzdesi / 100);
} else {
    $productFyt = $productPrice;
}

    $productLabel = htmlspecialchars($productResult["etiket"]);
    $productSdrum = htmlspecialchars($productResult["stok"]);
    $productMarka = mb_strtolower(htmlspecialchars($productResult["marka"] ?? '', ENT_QUOTES, 'UTF-8'), 'UTF-8');
    $productDurum = htmlspecialchars($productResult["durum"]);
	$productListe = floatval($productResult["ant"]);
	
// Oturum kontrolü ve role kontrolü
if (isset($_SESSION['user'])) {
    // Kullanıcı rolünü al
    $userRole = $_SESSION['user']['role'];
    
    // Fiyatları varsayılan olarak 'ant' ve 'kampfiyat' ile ayarla
    $productPrice = floatval($productResult["ant"]);
    // Kampanya yüzdesine göre indirimli fiyatı hesapla, aksi takdirde ant fiyatını kullan
    if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
        $kampanyaYuzdesi = floatval($productResult["kampanya"]);
        $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
    } else {
        $productFyt = $productPrice;
    }

    // Kullanıcı rolüne göre fiyatları güncelle
    if ($userRole === 'anttpt') {
        $productPrice = floatval($productResult["anttpt"]);
        $productFyt = floatval($productResult["anttpt"]);

        if (floatval($productResult["anttpt"]) == 0) {
            $productPrice = floatval($productResult["ant"]);
            // Kampanya yüzdesine göre indirimli fiyatı hesapla, aksi takdirde ant fiyatını kullan
            if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
                $kampanyaYuzdesi = floatval($productResult["kampanya"]);
                $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
            } else {
                $productFyt = $productPrice;
            }
        }
    } 
	elseif ($userRole === 'genel') {
        $productPrice = floatval($productResult["genel"]);
        $productFyt = floatval($productResult["genel"]);

        if (floatval($productResult["genel"]) == 0) {
            $productPrice = floatval($productResult["ant"]);
            // Kampanya yüzdesine göre indirimli fiyatı hesapla, aksi takdirde ant fiyatını kullan
            if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
                $kampanyaYuzdesi = floatval($productResult["kampanya"]);
                $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
            } else {
                $productFyt = $productPrice;
            }
        }
    } 
	elseif ($userRole === 'aratpt') {
        $productPrice = floatval($productResult["aratpt"]);
        $productFyt = floatval($productResult["aratpt"]);

        if (floatval($productResult["aratpt"]) == 0) {
            $productPrice = floatval($productResult["ant"]);
            // Kampanya yüzdesine göre indirimli fiyatı hesapla, aksi takdirde ant fiyatını kullan
            if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
                $kampanyaYuzdesi = floatval($productResult["kampanya"]);
                $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
            } else {
                $productFyt = $productPrice;
            }
        }
    } 
	elseif ($userRole === 'anatpt') {
        $productPrice = floatval($productResult["anatpt"]);
        $productFyt = floatval($productResult["anatpt"]);

        if (floatval($productResult["anatpt"]) == 0) {
            $productPrice = floatval($productResult["ant"]);
            // Kampanya yüzdesine göre indirimli fiyatı hesapla, aksi takdirde ant fiyatını kullan
            if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
                $kampanyaYuzdesi = floatval($productResult["kampanya"]);
                $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
            } else {
                $productFyt = $productPrice;
            }
        }
    }
}


    // OEM numarasını almak için SQL sorgusunu oluştur
    $oemSql = "SELECT * FROM oem_numbers WHERE stokkodu = ? LIMIT 1";
    $oemStmt = $pdo->prepare($oemSql);
    $oemStmt->execute([$productName]);
    $oemResult = $oemStmt->fetch(PDO::FETCH_ASSOC);

    // Eğer OEM numarası varsa
    if ($oemResult) {
        $productOemNumber = htmlspecialchars($oemResult["oem_number"]);
        $oemType = htmlspecialchars($oemResult["oem_type"]);
        $oemId = intval($oemResult["id"]);
    } else {
        $productOemNumber = '';
        $oemType = '';
        $oemId = null;
    }

    $productOemNumber = $oemResult ? htmlspecialchars($oemResult["oem_number"]) : '';

    // İndirim oranlarını belirleyin
    $discountRates = [
        "taksit" => 0.0,
        "tekcekim" => 0.10,
        "nakit" => 0.15
    ];

    // İndirimli fiyatları hesaplayın
    $discountedPriceTaksit = $productFyt * (1 - $discountRates['taksit']);
    $discountedPriceTekcekim = $productFyt * (1 - $discountRates['tekcekim']);
    $discountedPriceNakit = $productFyt * (1 - $discountRates['nakit']);
	
	
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
    //stok_not 
    function getColumnByStokkodu($pdo, $table, $column, $stokkodu) {
        $sql = "SELECT $column FROM $table WHERE stokkodu = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$stokkodu]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? htmlspecialchars($result[$column]) : '';
    }
    $productStokNot = getColumnByStokkodu($pdo, 'product', 'stok_not', $productName);

    $conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<link rel="icon" type="image/x-icon" href="icons/favicon.ico">
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
    <meta name="description" content="Web sitemiz, otomotiv ve ağır vasıta kontak yedek parçalarını B2B olarak satın alabileceğiniz kolay ve güvenilir bir platform sunar. Hızlı arama ve geniş ürün yelpazesi ile ihtiyaç duyduğunuz parçayı hemen bulun!">
 
    <!-- Open Graph Meta Tags for Social Media -->
    <meta property="og:title" content="Yiğit Kontak Sipariş - <?php echo $productName; ?>">
    <meta property="og:description" content="<?php echo $productInfo; ?> detaylarını inceleyin.">
    <meta property="og:image" content="<?php echo $productImage; ?>">
    <meta property="og:url" content="https://katalog.yigitotomotiv.com/siparis/product.php?productName=<?php echo urlencode($productName); ?>">
    <meta property="og:type" content="website">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Yiğit Kontak Sipariş - <?php echo $productName; ?>">
    <meta name="twitter:description" content="<?php echo $productInfo; ?> detaylarını inceleyin.">
    <meta name="twitter:image" content="<?php echo $productImage; ?>">

    <!-- Canonical Link -->
    <link rel="canonical" href="https://katalog.yigitotomotiv.com/siparis/product.php?productName=<?php echo urlencode($productName); ?>">

    <!-- Title -->
    <title>Yiğit Kontak Sipariş - <?php echo $productName; ?></title>
    <link rel="stylesheet" href="styles-v1.1.css">
    <script src="scripts-v1.2.js"></script>
    <script src="giris-v1.4.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
    /* Banner kapsayıcı */
    .banner-container {
    overflow: hidden;
        width: 100%;
        background-color: white;
        padding: 10px 0;
        display: flex;
        justify-content: center;
    }

    /* Banner stili */
    .banner {
        display: flex;
        flex-wrap: wrap;
        overflow-y: auto;
        scroll-behavior: smooth;
        justify-content: center;
        height: 100%;
    }

    /* Banner öğeleri */
    .banner-item {
        flex: 0 0 auto;
        margin: 5px; /* Aralarındaki boşluk */
    }

    .banner-item img {
        display: block;
        max-width: 100%;
        height: 50px;
        border: 2px solid #ddd; /* Resimlerin etrafında sınır */
        border-radius: 8px; /* Köşe yuvarlatma */
    }

    /* Mobil uyumlu tasarım */
    @media (max-width: 768px) {
        .banner-item {
            margin: 0 5px; /* Mobilde aralarındaki boşluk daha az */
        }
        .banner {

        height: 100%;
    }
        
    }

    /* Popup Arka Plan */
    .Opopup {
        display: none; /* Başlangıçta görünmez */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5); /* Hafif karartma */
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    /* Popup İçeriği */
    .Opopup-content {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 600px; /* Genişliği sınırlama */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Hafif gölge */
        position: relative;
    }

    /* Kapatma Butonu */
    .Oclose-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 24px;
        cursor: pointer;
        color: #333;
    }

    /* Form Etiketleri */
    .Opopup-content label {
        display: block;
        margin: 10px 0 5px;
        font-weight: bold;
    }

    /* Input ve Textarea Alanları */
    .Opopup-content input[type="text"],
    .Opopup-content textarea {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    /* Textarea'lar için özel stil */
    .Opopup-content textarea {
        resize: vertical; /* Kullanıcıların yüksekliği değiştirmesine izin verme */
    }

    /* Gönder Butonu */
    .Opopup-content button {
        background-color: #007bff;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }

    /* Gönder Butonu Hover Durumu */
    .Opopup-content button:hover {
        background-color: #0056b3;
    }
	</style>
</head>
<body onload="myFunction()" style="min-height: 100vh; display: flex; flex-direction: column; margin: 0;">
<div style="flex: 1;">
    <!-- Popup ekranı -->
    <div id="login-popup" class="popuplogin" style="display:none;">
        <div class="popuplogin-content">
            <div id="login-form">
                <div class="form-group">
                    <input type="text" id="username" placeholder="Müşteri Kodu">
                    <input type="password" id="password" placeholder="Şifre">
                </div>
                <div class="form-d">
                    <label for="remember-me" class="show-password-label">
                        <input type="checkbox" id="remember-me"> Beni Hatırla
                    </label>
                    <label for="show-password" class="show-password-label">
                        <input type="checkbox" id="show-password"> Şifre Göster
                    </label>
                </div>
                <div class="form-group">
                    <button class="button" onclick="login()">Giriş Yap</button>
                    <button class="button" onclick="closePopup()">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Popup HTML -->
    <div id="cartPopup" class="popup">
        <span class="popup-close" onclick="closePopupsepet()">&times;</span>
        <p id="productDetails"></p>
    </div>

    <div class="topnav">
        <div class="mmenu">
            <a class="active" href="#" onclick="goToHome()">Anasayfa</a>
            <a class="active1" style="background:red;" href="./?kampanya=1"  title="Kampanyalı ürünler">Kampanya</a>
            <a class="active" href="#" onclick="openNav()">Araç</a>
            <a class="active" href="#" onclick="goToCart()">Sepet</a>
        </div>
        <!-- Giriş butonu -->
        <button id="loginButton" class="button" onclick="openPopup()">Giriş</button>
        <div class="mmenu" id="user-info" style="display:none;"><a class="active" style="background:#e70000;" href="#" onclick="logout()">Çıkış</a></div>
    </div>

    <div id="mySidenav" class="sidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()" title="Kapat">&times;</a>
        <a class="active1" href="./?durum=yeni" style="" title="Yeni ürünler">YENİ</a>
        <a href="./?query=	RENAULT	" title="RENAULT araç modelleri">	RENAULT	</a>
        <a href="./?query=	FIAT	" title="FIAT araç modelleri">	FIAT	</a>
        <a href="./?query=	PEUGEOT	" title="PEUGEOT araç modelleri">	PEUGEOT	</a>
        <a href="./?query=	OPEL	" title="OPEL araç modelleri">	OPEL	</a>
        <a href="./?query=	CHEVROLET	" title="CHEVROLET araç modelleri">	CHEVROLET	</a>
        <a href="./?query=	VW	" title="VW araç modelleri">	VOLKSWAGEN	</a>
        <a href="./?query=	BMW	" title="BMW araç modelleri">	BMW	</a>
        <a href="./?query=	MERCEDES	" title="MERCEDES araç modelleri">	MERCEDES	</a>
        <a href="./?query=	FORD	" title="FORD araç modelleri">	FORD	</a>
        <a href="./?query=	HONDA	" title="HONDA araç modelleri">	HONDA	</a>
        <a href="./?query=	NISSAN	" title="NISSAN araç modelleri">	NISSAN	</a>
        <a href="./?query=	TOYOTA	" title="TOYOTA araç modelleri">	TOYOTA	</a>
        <a href="./?query=	HYUNDAI	" title="HYUNDAI araç modelleri">	HYUNDAI	</a>
        <a href="./?query=	MITSUBISHI	" title="MITSUBISHI araç modelleri">	MITSUBISHI	</a>
        <a href="./?query=	MAZDA	" title="MAZDA araç modelleri">	MAZDA	</a>
        <a href="./?query=	VOLVO	" title="VOLVO araç modelleri">	VOLVO	</a>
        <a href="./?query=	ISUZU	" title="ISUZU araç modelleri">	ISUZU	</a>
        <a href="./?query=	SCANIA	" title="SCANIA araç modelleri">	SCANIA	</a>
        <a href="./?query=	DAF	" title="DAF araç modelleri">	DAF	</a>
        <a href="./?query=	AĞIRVASITA+MAN	" title="MAN araç modelleri">	MAN	</a>
        <a href="./?query=	SUZUKI	" title="SUZUKI araç modelleri">	SUZUKI	</a>
        <a href="./?query=	UNIVERSAL	" title="UNIVERSAL araç modelleri">	UNIVERSAL	</a>
    </div>

    <header>
        <p>Yiğit Otomotiv Kontaklar</p>
		<div id="yonetici"></div>
    </header>
        <div class="search">
            <a class="active2" style="margin-top: 10px;" href="./?durum=YOLDA" title="Yoldaki ürünler">Yoldakiler</a>
                <form id="search-form" method="GET" action="./">
                    <input type="text" id="search-input" name="query" placeholder="Ürün ara..." value="">
                    <button type="submit">Ara</button>			
                </form>
        </div>
    <div id="loader"></div>
    <div  id="myDiv" class="animate-bottom"></div>
    <main>
<div class="left-column">
		<div id="modal" class="modal" style="display: flex;">
        <div class="modal-content">
                <div style="display: flex; justify-items: end; align-items: center; flex-wrap: nowrap; justify-content: space-between; margin: 0 10px; ">
							<a href="./?durum=özel"  title="Günün Kampanyası">Bugüne özel indirimleri kaçırma</a><br>
								<a class="siparislink" href="./?durum=yeni" <!--id="open-video"-->>Yeni gelen ürünler</a>
								<div id="video-popup" class="video-popup">
									<span class="close">&times;</span>
									<video controls>
										<source src="resim/siparis.mp4" type="video/mp4">
										Tarayıcınız video etiketini desteklemiyor.
									</video>
								</div>
							<span class="close-btna">&times;</span>
						</div>
					</div>
				</div>
		<div class="productygt">
				<div class="product">
					<span class="p-name">
				<h1 class="text-2xl font-bold"><?php echo $productName; ?></h1> </span>
				<div class="urun">

        <?php
        // HTML için güvenli hale getir
        $escapedImage = htmlspecialchars($productImage ?: '/resim/urun/kontak.jpg', ENT_QUOTES, 'UTF-8');
        $escapedName = htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
        $escapedPrice = number_format(floatval($productPrice), 2, '.', '');
        $escapedBrand = htmlspecialchars($productMarka, ENT_QUOTES, 'UTF-8');
        $escapedDurum = htmlspecialchars($productDurum, ENT_QUOTES, 'UTF-8');

        // Stok durumu renk sınıfı
        $durumClass = match ($productDurum) {
            'YENİLER' => 'bg-red-500',
            'ÖZEL'    => 'bg-green-500',
            'YOLDA'   => 'bg-blue-500',
            default   => ''
        };
        ?>

    <div class="image-container"
        onclick="openImagePopup('<?php echo $escapedImage; ?>', '<?php echo $escapedName; ?>', '<?php echo $escapedPrice; ?>', '<?php echo $escapedBrand; ?>')"
        onmouseover="changeCursor(this)"
        onmouseout="restoreCursor(this)">

        <span class="yeni <?php echo $durumClass; ?>">
            <?php echo $escapedDurum; ?>
        </span>

        <img src="<?php echo $escapedImage; ?>"
            alt="<?php echo $escapedName; ?>"
            onerror="this.onerror=null;this.src='/resim/urun/kontak.jpg';"
            class="h-50 object-cover" />

        <img loading="lazy"
            src="resim/marka/<?php echo $escapedBrand; ?>.png"
            alt="<?php echo $escapedBrand; ?>"
            class="marka"
            onerror="this.onerror=null;this.src='resim/marka/default.png';" />
    </div>
		
    <h2 class="info"><?php echo $productInfo; ?><br>
        <div class="fspet">
                        <?php if (isset($_SESSION['user'])): ?>
                            <?php if (isset($_SESSION['user']) && $_SESSION['user']['cari'] === 'PLASİYER'): ?>
                                <div id="gizlencekalan_stoknot">
                                    <span style="color:red; font-weight:800;"><?= htmlspecialchars($productStokNot ?? '') ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>	
                        <!-- HTML ve PHP kodu -->
                        <?php if (isset($_SESSION['user'])):
                        $kampanyaYuzdesi = floatval($productResult["kampanya"]);
                        $kampanyaCarpan = (100 - $kampanyaYuzdesi) / 100;

                        // Hesaplanan kampanyasız fiyatlar (kırmızı üstü çizili için)
                        $normalTaksit = round($discountedPriceTaksit / $kampanyaCarpan);
                        $normalTekcekim = round($discountedPriceTekcekim / $kampanyaCarpan);
                        $normalNakit = round($discountedPriceNakit / $kampanyaCarpan);

                        // Gerçek fiyatlar (uygulanan kampanyalı)
                        $taksit = round($discountedPriceTaksit);
                        $tekcekim = round($discountedPriceTekcekim);
                        $nakit = round($discountedPriceNakit);
                        ?>
                            <table class="w-full mt-4 border border-gray-300">
                                <thead>
                                    <tr>
                                        <th class="border px-4 py-2">Liste</th>
                                        <th class="border px-4 py-2">Taksit</th>
                                        <th class="border px-4 py-2">Tek Ç.</th>
                                        <th class="border px-4 py-2">Nakit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border px-4 py-2">
                                            <?php echo round(($productListe / 0.9) / 0.8 / $kampanyaCarpan); ?>₺
                                        </td>
                                        <td class="border px-4 py-2">
                                            <?php if ($normalTaksit !== $taksit): ?>
                                                <span class="text-red-500 line-through"><?php echo $normalTaksit; ?>₺</span><br>
                                            <?php endif; ?>
                                            <?php echo $taksit; ?>₺
                                        </td>
                                        <td class="border px-4 py-2">
                                            <?php if ($normalTekcekim !== $tekcekim): ?>
                                                <span class="text-red-500 line-through"><?php echo $normalTekcekim; ?>₺</span><br>
                                            <?php endif; ?>
                                            <?php echo $tekcekim; ?>₺
                                        </td>
                                        <td class="border px-4 py-2">
                                            <?php if ($normalNakit !== $nakit): ?>
                                                <span class="text-red-500 line-through"><?php echo $normalNakit; ?>₺</span><br>
                                            <?php endif; ?>
                                            <?php echo $nakit; ?>₺
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <!-- Oturum yoksa gösterilecek içerik -->
                            <table class="w-full mt-4 border border-gray-300">
                                <p>Fiyatlar için oturum açmanız gerekmektedir.</p>
                            </table>				
                        <?php endif; ?>
                        <div style="width:100%">
                            <!-- Kampanya ve Stok Bilgisi -->
                            <div class="flex flex-wrap items-end justify-end gap-4">
                                <?php if (!empty($productKmpny)): ?>
                                    <p class="text-red-500 font-semibold">
                                        <?php echo isset($_SESSION['user']) 
                                            ? 'İndirim: ' . htmlspecialchars(str_replace('%', '', $productKmpny), ENT_QUOTES, 'UTF-8') . '%' 
                                            : 'KAMPANYA'; ?>
                                    </p>
                                <?php endif; ?>

                                <p class="font-medium">
                                    STOK:
                                    <span class="<?php 
                                        echo $productSdrum === "VAR" 
                                            ? 'text-green-500' 
                                            : ($productSdrum === "KRİTİK" ? 'text-orange-400' : 'text-red-600'); 
                                    ?>">
                                        <?php echo htmlspecialchars($productSdrum, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </p>
                            </div>

                            <!-- Butonlar -->
                            <div class="flex flex-wrap items-end justify-end gap-4">
                                <button
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition"
                                    onclick="sharePage(
                                        '<?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>',
                                        '<?php echo htmlspecialchars($productInfo, ENT_QUOTES, 'UTF-8'); ?>'
                                    )"
                                >
                                    PAYLAŞ
                                </button>
                                
                                <button
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition"
                                    onclick="showPopup(
                                        '<?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>',
                                        <?php echo floatval($productFyt); ?>,
                                        '<?php echo htmlspecialchars($productImage, ENT_QUOTES, 'UTF-8'); ?>',
                                        <?php echo floatval($productResult['kampanya']); ?>
                                    )"
                                >
                                    SEPETE EKLE
                                </button>
                            </div>
                        </div>
                        <?php
                        // Verilerin hazırlanması
                        $productOemNumber = $productOemNumber ?? 'Yok';
                        $oemResult = $oemResult ?? false; // Örnek veri kontrolü
                        ?>
        </div>
    </h2>
				</div>

					<?php if (isset($_SESSION['user']) && $_SESSION['user']['cari'] === 'PLASİYER'): ?>
						<div id="gizlencekalan">
							<div>
								<p>OEM Numarası: <?= htmlspecialchars($productOemNumber) ?></p>
								<?php if ($oemResult): ?>
									<!-- OEM Düzenleme Butonu -->
									<button id="editOemBtn">OEM Düzenle</button>
								<?php else: ?>
									<!-- OEM Ekleme Butonu -->
									<button id="addOemBtn">OEM Ekle</button>
								<?php endif; ?>
							</div>

							<!-- Popup İçeriği -->
							<div id="oemPopup" class="Opopup">
								<div class="Opopup-content">
									<span class="Oclose-btn" id="closePopup">&times;</span>
									<?php if ($oemResult): ?>
										<!-- OEM Düzenleme Formu -->
										<form method="POST" action="">
											<input type="hidden" name="id" value="<?= htmlspecialchars($oemId) ?>">
											<label>Stok Kodu:</label>
											<input type="text" name="stokkodu" value="<?= htmlspecialchars($productName) ?>" readonly><br>
											<label>OEM Tipi:</label>
											<input type="text" name="oem_type" value="<?= htmlspecialchars($oemType) ?>"><br>
											<label>OEM Numarası:</label>
											<textarea name="oem_number" rows="4"><?= htmlspecialchars($productOemNumber) ?></textarea><br>
											<button type="submit" name="edit_oem_number">Düzenle</button>
										</form>
									<?php else: ?>
										<!-- OEM Ekleme Formu -->
										<form method="POST" action="">
											<input type="hidden" name="id" value="<?= htmlspecialchars($oemId) ?>">
											<label>Stok Kodu:</label>
											<input type="text" name="stokkodu" value="<?= htmlspecialchars($productName) ?>" readonly><br>
											<label>OEM Tipi:</label>
											<input type="text" name="oem_type" value="<?= htmlspecialchars($oemType) ?>"><br>
											<label>OEM Numarası:</label>
											<textarea name="oem_number" rows="4"><?= htmlspecialchars($productOemNumber) ?></textarea><br>
											<button type="submit" name="add_oem_number">Ekle</button>
										</form>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
		</div>
</div>

<div class="right-column">
                <aside class="shopping-cart-container">
                    <div class="shopping-cart" id="shopping-cart">
					<img alt="Yiğit Otomotiv" id="Header1_headerimg" src="	resim/ygt_oto_banner.png" style="padding: 0 10px;margin: auto;">
                                <p>Sepet</p>
                                <!-- Sepet Tablosu -->
                                <table id="cart-table" style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            <th>Ürün Adı</th>
                                            <th>Miktar</th>
                                            <th>Fiyat</th>
                                            <th>Toplam</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-items"></tbody>
                                </table>
                        <p id="total-price">Toplam Fiyat: Gizli</p>
                        <table id="payment-options">
                            <tr>
                                <th><label>Ödeme Tipi:</label></th>
                                <th>
                                    <input type="radio" id="taksit" name="paymentType" value="taksit">
                                    <label for="taksit">Taksit</label><br>

                                    <input type="radio" id="tekcekim" name="paymentType" value="tekcekim">
                                    <label for="tekcekim">Tek Çekim</label><br>

                                    <input type="radio" id="nakit" name="paymentType" value="nakit">
                                    <label for="nakit">Nakit</label>
                                </th>
                            </tr>
                        </table><br>
                        <button onclick="goToCart()">Sepete Git</button>
                        <button onclick="clearCart()">Sepeti Boşalt</button>
                        <div>5000 ₺ ve üzeri alışverişlerde kargo ücreti firmamız tarafından karşılanmaktadır. </div>
                    </div> 
                </aside>
            </div>
		</main>
	<!-- Daha fazla araç ekleyebilirsiniz 	-->
	<div class="banner-container">
        <div class="banner">
			<a href="./?query=	renault	" class="banner-item" title="renault araçlarını keşfedin">
                <img src="resim/arac/renault.png" alt="renault">
            </a>
			<a href="./?query=	dacia	" class="banner-item" title="dacia araçlarını keşfedin">
                <img src="resim/arac/dacia.png" alt="dacia">
            </a>
			<a href="./?query=	FIAT	" class="banner-item" title="FIAT araçlarını keşfedin">
                <img src="resim/arac/fiat.png" alt="FIAT">
            </a>
            <a href="./?query=	PEUGEOT	" class="banner-item" title="PEUGEOT araçlarını keşfedin">
                <img src="resim/arac/peugeot.png" alt="PEUGEOT">
            </a>
            <a href="./?query=	citroen	" class="banner-item" title="citroen araçlarını keşfedin">
                <img src="resim/arac/citroen.png" alt="citroen">
            </a>
            <a href="./?query=	OPEL	" class="banner-item" title="OPEL araçlarını keşfedin">
                <img src="resim/arac/opel.png" alt="OPEL">
            </a>
            <a href="./?query=	chevrolet	" class="banner-item" title="chevrolet araçlarını keşfedin">
                <img src="resim/arac/chevrolet.png" alt="chevrolet">
            </a>
            <a href="./?query=	VW	" class="banner-item" title="VW araçlarını keşfedin">
                <img src="resim/arac/vw.png" alt="VW">
            </a>
            <a href="./?query=	skoda	" class="banner-item" title="skoda araçlarını keşfedin">
                <img src="resim/arac/skoda.png" alt="skoda">
            </a>
            <a href="./?query=	seat	" class="banner-item" title="seat araçlarını keşfedin">
                <img src="resim/arac/seat.png" alt="seat">
            </a>
            <a href="./?query=	bmw	" class="banner-item" title="bmw araçlarını keşfedin">
                <img src="resim/arac/bmw.png" alt="bmw">
            </a>
            <a href="./?query=	MERCEDES	" class="banner-item" title="MERCEDES araçlarını keşfedin">
                <img src="resim/arac/mercedes.png" alt="MERCEDES">
            </a>
            <a href="./?query=	FORD	" class="banner-item" title="FORD araçlarını keşfedin">
                <img src="resim/arac/ford.png" alt="FORD">
            </a>
            <a href="./?query=	honda	" class="banner-item" title="honda araçlarını keşfedin">
                <img src="resim/arac/honda.png" alt="honda">
            </a>
            <a href="./?query=	nissan	" class="banner-item" title="nissan araçlarını keşfedin">
                <img src="resim/arac/nissan.png" alt="nissan">
            </a>
            <a href="./?query=	TOYOTA	" class="banner-item" title="TOYOTA araçlarını keşfedin">
                <img src="resim/arac/toyota.png" alt="TOYOTA">
            </a>
            <a href="./?query=	hyundai	" class="banner-item" title="hyundai araçlarını keşfedin">
                <img src="resim/arac/hyundai.png" alt="hyundai">
            </a>
            <a href="./?query=	kia	" class="banner-item" title="kia araçlarını keşfedin">
                <img src="resim/arac/kia.png" alt="kia">
            </a>
            <a href="./?query=	mitsubishi	" class="banner-item" title="mitsubishi araçlarını keşfedin">
                <img src="resim/arac/mitsubishi.png" alt="mitsubishi">
            </a>
            <a href="./?query=	mazda	" class="banner-item" title="mazda araçlarını keşfedin">
                <img src="resim/arac/mazda.png" alt="mazda">
            </a>
            <a href="./?query=	VOLVO	" class="banner-item" title="VOLVO araçlarını keşfedin">
                <img src="resim/arac/volvo.png" alt="VOLVO">
            </a>			
            <a href="./?query=	isuzu	" class="banner-item" title="isuzu araçlarını keşfedin">
                <img src="resim/arac/isuzu.png" alt="isuzu">
            </a>
            <a href="./?query=	scania	" class="banner-item" title="scania araçlarını keşfedin">
                <img src="resim/arac/scania.png" alt="scania">
            </a>
            <a href="./?query=	AĞIRVASITA+MAN	" class="banner-item" title="man araçlarını keşfedin">
                <img src="resim/arac/man.png" alt="man">
            </a>
            <a href="./?query=	suzuki	" class="banner-item" title="suzuki araçlarını keşfedin">
                <img src="resim/arac/suzuki.png" alt="suzuki">
            </a><!---->
        </div>
    </div> 
        <!-- WhatsApp Canlı Destek Butonu 
        <button class="open-button" onclick="openForm()">Destek</button> -->

        <div class="chat-popup" id="myFormWhatsapp">
            <form class="form-container" onsubmit="sendMessage(event)">
                <p style="text-align: center;">WhatsApp Destek</p>
                <label for="msg"><b>Mesajınız</b></label>
                <textarea placeholder="Mesajınızı buraya yazın..." name="msg" id="msg" required></textarea>
                <button type="submit" class="btn">Gönder</button>
                <button type="button" class="btn cancel" onclick="closeForm()">Kapat</button>
            </form>
        </div>
        <div class="containermessagez">
            <div id="messagez" class="messagez">Ürün sepete eklendi</div>
        </div>
	</div>

<footer style="background-color: #333333;color: white;text-align: center;padding: 15px 0;position: relative;width: 100%;font-size: 14px;box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);">
  © 2022 - <span id="currentYear"></span> <a target="_blank" style="color: #ffcc00;" href="https://tuncyyldrm.vercel.app" >Tuncay YILDIRIM</a>
</footer>

    <!-- JavaScript -->
    <script>
    function toggleContactPopup() {
        const popup = document.getElementById("contact-popup");
        popup.style.display = (popup.style.display === "none" ? "block" : "none");
    }

    // Dinamik olarak güncel yılı gösterme
    document.getElementById('currentYear').textContent = new Date().getFullYear();

    // Service Worker kaydı
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker
        .register('/service-worker.js', { scope: '/' })
        .then(() => console.log('Ana dizin için Service Worker kaydedildi.'))
        .catch(err => console.error('Service Worker kaydı başarısız:', err));
    }
    </script>

    <script>
        // Popup'ı açma ve kapama işlemleri
        document.addEventListener('DOMContentLoaded', () => {
            const editOemBtn = document.getElementById('editOemBtn');
            const addOemBtn = document.getElementById('addOemBtn');
            const oemPopup = document.getElementById('oemPopup');
            const closePopup = document.getElementById('closePopup');

            if (editOemBtn) {
                editOemBtn.addEventListener('click', () => {
                    oemPopup.style.display = 'flex';
                });
            }

            if (addOemBtn) {
                addOemBtn.addEventListener('click', () => {
                    oemPopup.style.display = 'flex';
                });
            }

            closePopup.addEventListener('click', () => {
                oemPopup.style.display = 'none';
            });

            // Popup'ın dışında bir yere tıklanırsa kapat
            window.addEventListener('click', (event) => {
                if (event.target === oemPopup) {
                    oemPopup.style.display = 'none';
                }
            });
        });
    </script>
    <!-- JavaScript fonksiyonunu body'nin sonunda tanımlayın -->
<script>
function sharePage(productName, productInfo) {
    const rawUrl = window.location.href;
    const pageUrl = rawUrl.replace(/ /g, '%20'); // Boşlukları kodla
    const shareText = `${productName} - Ürün açıklaması: ${productInfo}\n${pageUrl}`;

    copyToClipboard(shareText);
    alert("Bağlantı panoya kopyalandı.\nDilediğiniz yere yapıştırarak paylaşabilirsiniz.");
}

function copyToClipboard(text) {
    const tempInput = document.createElement("textarea");
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
}
</script>

<script>
function openImagePopup(imageSrc, imageName, productPrice, productMarka) {
    const popup = document.createElement('div');
    popup.classList.add('image-popup');

    popup.innerHTML = `
        <div class="popup-content">		
            <span class="close-btn" onclick="closeImagePopup()">&times;</span>    
            <h3 style="position: absolute; top: 5px; margin: 0; font-size: 20px; font-weight: bolder;">
                ${imageName}
            </h3>   

            <img 
                style="top: 25px; right: 40px; width: 60px;" 
                loading="lazy" 
                src="resim/marka/${productMarka}.png" 
                class="marka"
                alt="${productMarka}"
                onerror="this.onerror=null;this.src='resim/marka/default.png';"
            >	

            <img 
                loading="lazy" 
                src="${imageSrc}" 
                alt="${imageName}" 
                onerror="this.onerror=null;this.src='/resim/urun/kontak.jpg';"
                style="max-width: 100%; max-height: 80vh; margin-top: 60px; border-radius: 8px;"
            >			
        </div>
    `;

    document.body.appendChild(popup);
    document.body.style.overflow = 'hidden';

    popup.addEventListener('click', function(event) {
        if (event.target === popup) {
            closeImagePopup();
        }
    });
}

function closeImagePopup() {
    const popup = document.querySelector('.image-popup');
    if (popup) {
        popup.remove();
        document.body.style.overflow = 'auto';
    }
}
</script>

<script>
    const openVideoLink = document.getElementById('open-video');
    const videoPopup = document.getElementById('video-popup');
    const closeVideo = videoPopup.querySelector('.close');

    openVideoLink.addEventListener('click', (event) => {
        event.preventDefault();
        videoPopup.classList.add('show');
        });

        closeVideo.addEventListener('click', () => {
            videoPopup.classList.remove('show');
        });

        videoPopup.addEventListener('click', (event) => {
            if (event.target === videoPopup) {
                videoPopup.classList.remove('show');
            }
        });
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Arama kutusuna URL parametresini yerleştir
    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('query') || '';
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.value = query;
    }

    // Görsel tıklama ile popup gösterme
    const popup = document.getElementById("imagePopup");
    const popupImage = document.getElementById("popupImage");
    const captionText = document.getElementById("caption");
    const images = document.getElementsByClassName("clickable-image");
    const span = document.getElementsByClassName("close")[0];

    for (let i = 0; i < images.length; i++) {
        images[i].onclick = function() {
            popup.style.display = "block";
            popupImage.src = this.src;
            captionText.innerHTML = this.alt;
        }
    }

    // Kapatma tuşuna basınca kapat
    if (span) {
        span.onclick = function() {
            popup.style.display = "none";
        };
    }

    // Popup dışına tıklanınca kapat
    window.onclick = function(event) {
        if (event.target === popup) {
            popup.style.display = "none";
        }
    };
});
</script>

    <?php
    } else {
            // 404 statü kodu gönder
    header("HTTP/1.0 404 Not Found");
    // 404 sayfasına yönlendir
    header("Location: ./404.html");
    exit();
    }
    // Bağlantıyı kapat
    $pdo = null;
    ?>
</body>
</html>