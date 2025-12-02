<?php
session_start();
header("Content-Type: text/html; charset=UTF-8");   // <-- ekle
mb_internal_encoding("UTF-8");    

session_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
include 'db_config.php';
// Zaman dilimini ayarla
date_default_timezone_set('Europe/Istanbul');
$current_time = date('Y-m-d H:i:s');

// PDO kullanarak veritabanına bağlan
try {
    $pdo = new PDO("mysql:host=localhost;dbname=yigit-otomotiv_b2b;charset=utf8mb4", "yigit-otomotiv_b2b", "Yigit102030");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Hata detaylarını log dosyasına yaz
    error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
    // Kullanıcıya genel bir mesaj göster
    die("Sistem geçici olarak hizmet veremiyor. Lütfen daha sonra tekrar deneyin.");
}
function getColumnByStokkodu($pdo, $stokkodu, $column = 'stok_not') {
    $sql = "SELECT $column FROM product WHERE stokkodu = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$stokkodu]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result[$column] ?? '';
}
// Oturum süresini kontrol et
$sessionTimeout = 1 * 24 * 60 * 60; // 1 gün
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $sessionTimeout) {
    session_unset(); // Tüm oturum değişkenlerini temizle
    session_destroy(); // Oturumu yok et
    setcookie("user", "", time() - 3600, "/"); // Çerezi sil
}
$_SESSION['last_activity'] = time(); // Son etkinlik zamanını güncelle

// Arama terimini al
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// 'durum' parametresini al
$durum = isset($_GET['durum']) ? trim($_GET['durum']) : '';

// Kampanya filtresini al (parametre adını 'kamp' olarak değiştirdiğiniz için)
$kampanya = isset($_GET['kampanya']) ? trim($_GET['kampanya']) : '';

// Arama terimlerini kelime kelime ayır
$words = preg_split('/\s+/', $query);
$conditions = [];
$params = [];

// Durum filtresini ekle (bağımsız çalışacak)
if (!empty($durum)) {
    $conditions[] = "LOWER(product.durum) = LOWER(?)";
    $params[] = $durum;
}
// Kampanya filtresini ekle (bağımsız çalışacak)
if (!empty($kampanya) && $kampanya === '1') {
    $conditions[] = "product.kampanya IS NOT NULL AND TRIM(product.kampanya) != ''";
}

// Arama raporunu veritabanına kaydet
if (!empty($query)) {
    try {
        $userId = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : 'misafir';
        $sql = "INSERT INTO searches (searchQuery, userId, searchDate) VALUES (:searchQuery, :userId, :searchDate)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':searchQuery' => $query,
            ':userId' => $userId,
            ':searchDate' => $current_time,
        ]);
    } catch (PDOException $e) {
        error_log("Arama raporu kaydedilemedi: " . $e->getMessage());
    }
}

function generateTurkishCharacterVariants($text) {
    $search = ['ı', 'İ', 'ş', 'Ş', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç'];
    $replace = ['i', 'i', 's', 's', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'];
    return [
        $text,
        str_replace($search, $replace, $text)
    ];
}

// Arama terimi varsa normal arama koşullarını ekle
if (!empty($query)) {
    foreach ($words as $word) {
        if (!empty(trim($word))) {
            // Synonym ve Türkçe karakter işlemleri...
            $synonymsQuery = "SELECT synonym FROM synonyms WHERE LOWER(word) = LOWER(?)";
            $synonymsStmt = $pdo->prepare($synonymsQuery);
            $synonymsStmt->execute([$word]);
            $synonyms = generateTurkishCharacterVariants($word);

            while ($row = $synonymsStmt->fetch(PDO::FETCH_ASSOC)) {
                $synonyms[] = $row['synonym'];
                $synonyms = array_merge($synonyms, generateTurkishCharacterVariants($row['synonym']));
            }

            $likeClauses = [];
            foreach ($synonyms as $syn) {
                $variantWords = generateTurkishCharacterVariants($syn);
                foreach ($variantWords as $variant) {
                    $likeClauses[] = "(LOWER(product.stokkodu) COLLATE utf8mb4_general_ci LIKE ? 
                                     OR LOWER(product.aciklama) COLLATE utf8mb4_general_ci LIKE ? 
                                     OR LOWER(product.marka) COLLATE utf8mb4_general_ci LIKE ? 
                                     OR LOWER(product.etiket) COLLATE utf8mb4_general_ci LIKE ? 
                                     OR LOWER(product.kampanya) COLLATE utf8mb4_general_ci LIKE ? 
                                     OR LOWER(product.durum) COLLATE utf8mb4_general_ci LIKE ? 
                                     OR LOWER(oem_numbers.oem_number) COLLATE utf8mb4_general_ci LIKE ?)";
                    $params = array_merge($params, array_fill(0, 7, "%$variant%"));
                }
            }

            $conditions[] = "(" . implode(" OR ", $likeClauses) . ")";
        }
    }
}

$itemsPerPage = 30; // Sayfa başına ürün sayısı
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $itemsPerPage;

// Toplam ana ürün sayısını al (bağlı ürünler dahil değil)
$totalSql = "SELECT COUNT(DISTINCT product.stokkodu) FROM product 
             LEFT JOIN oem_numbers ON product.stokkodu = oem_numbers.stokkodu
             LEFT JOIN product_links ON product.stokkodu = product_links.product_stokkodu";

if (!empty($conditions)) {
    $totalSql .= " WHERE " . implode(' AND ', $conditions);
}
$totalStmt = $pdo->prepare($totalSql);
$totalStmt->execute($params);
$totalItems = $totalStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);
header('X-Total-Pages: ' . $totalPages);

// Ana ürünleri getir (limit + offset ile)
$sql = "SELECT DISTINCT product.*, product.siralama 
        FROM product 
        LEFT JOIN oem_numbers ON product.stokkodu = oem_numbers.stokkodu
        LEFT JOIN product_links ON product.stokkodu = product_links.product_stokkodu";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}
$sql .= " ORDER BY product.siralama ASC LIMIT $itemsPerPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sonuçları organize et
$finalResults = [];
$totalDisplayedItems = 0;

// Ana ürünleri ekle
foreach ($results as $product) {
    $stokkodu = $product['stokkodu'];
    if (!isset($finalResults[$stokkodu])) {
        $finalResults[$stokkodu] = $product;
        $totalDisplayedItems++;
    }
}
// Durum filtresi boşsa ve kampanya parametresi boş veya 1 değilse bağlı ürünleri ekle
if (empty($durum) && (!isset($_GET['kampanya']) || $_GET['kampanya'] !== '1')) {
    foreach ($finalResults as $mainProduct) {
        if ($totalDisplayedItems >= $itemsPerPage) break;

        $linkedProductsStmt = $pdo->prepare("
            SELECT p.*, p.siralama 
            FROM product_links pl
            INNER JOIN product p ON pl.linked_stokkodu = p.stokkodu
            WHERE pl.product_stokkodu = ?
            ORDER BY p.siralama ASC
        ");
        $linkedProductsStmt->execute([$mainProduct['stokkodu']]);
        $linkedProducts = $linkedProductsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($linkedProducts as $linked) {
            if ($totalDisplayedItems >= $itemsPerPage) break;

            $linkedStokkodu = $linked['stokkodu'];
            if (!isset($finalResults[$linkedStokkodu])) {
                $finalResults[$linkedStokkodu] = $linked;
                $totalDisplayedItems++;
            }
        }
    }
}

// Son olarak dizinin anahtarlarını sıfırla
$finalResults = array_values($finalResults);

// İstersen burada sıralama yapabilirsin, genelde gerekli değil çünkü veritabanından siralama ile geliyor
// usort($finalResults, fn($a, $b) => $a['siralama'] <=> $b['siralama']);


if ($finalResults) {
    foreach ($finalResults as $row) {

// Ürün bilgilerini değişkenlere al
$productName = htmlspecialchars($row["stokkodu"] ?? '', ENT_QUOTES, 'UTF-8');
if (empty($productName)) continue; // Boş ürün ismini atla
$productPrice = floatval($row["ant"]);
$productImage = htmlspecialchars($row["resimurl"] ?? 'default-image-url', ENT_QUOTES, 'UTF-8');
$productInfo = htmlspecialchars($row["aciklama"] ?? '', ENT_QUOTES, 'UTF-8');
$productKmpny = htmlspecialchars($row["kampanya"] ?? '', ENT_QUOTES, 'UTF-8');
if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
    $kampanyaYuzdesi = floatval($productResult["kampanya"]);
    $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
} else {
    $productFyt = $productPrice;
}

$productLabel = htmlspecialchars($row["etiket"] ?? '', ENT_QUOTES, 'UTF-8');
$productSdrum = htmlspecialchars($row["stok"] ?? '', ENT_QUOTES, 'UTF-8');
$productMarka = mb_strtolower(htmlspecialchars($row["marka"] ?? '', ENT_QUOTES, 'UTF-8'), 'UTF-8');
$productDurum = htmlspecialchars($row["durum"] ?? '', ENT_QUOTES, 'UTF-8');
$productListe = floatval($row["ant"]);

// Karakter sınırını belirleyin
$charLimit = 85;
$truncatedProductInfo = mb_strlen($productInfo, 'UTF-8') > $charLimit 
    ? mb_substr($productInfo, 0, $charLimit, 'UTF-8') . '...' 
    : $productInfo;

        // İndirim oranlarını belirleyin
        $discountRates = [
            "taksit" => 0.0,
            "tekcekim" => 0.10,
            "nakit" => 0.15
        ];

// Oturum kontrolü ve role kontrolü
if (isset($_SESSION['user'])) {
    // Kullanıcı rolünü belirle
    $userRole = $_SESSION['user']['role'];

    // Rol 'anttpt' ise
    if ($userRole === 'anttpt') {
        $productPrice = floatval($row["anttpt"]);
        $productFyt = floatval($row["anttpt"]);

        // 'anttpt' değeri 0 ise, 'ant' ve 'kampfiyat' kullan
        if ($productPrice == 0) {
            $productPrice = floatval($row["ant"]);
            if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
    $kampanyaYuzdesi = floatval($productResult["kampanya"]);
    $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
} else {
    $productFyt = $productPrice;
}

        }
    }
    // Rol 'genel' ise
    elseif ($userRole === 'genel') {
        $productPrice = floatval($row["genel"]);
        $productFyt = floatval($row["genel"]);

        // 'genel' değeri 0 ise, 'ant' ve 'kampfiyat' kullan
        if ($productPrice == 0) {
            $productPrice = floatval($row["ant"]);
            if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
    $kampanyaYuzdesi = floatval($productResult["kampanya"]);
    $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
} else {
    $productFyt = $productPrice;
}

        }
    }
    // Rol 'aratpt' ise
    elseif ($userRole === 'aratpt') {
        $productPrice = floatval($row["aratpt"]);
        $productFyt = floatval($row["aratpt"]);

        // 'aratpt' değeri 0 ise, 'ant' ve 'kampfiyat' kullan
        if ($productPrice == 0) {
            $productPrice = floatval($row["ant"]);
            if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
    $kampanyaYuzdesi = floatval($productResult["kampanya"]);
    $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
} else {
    $productFyt = $productPrice;
}

        }
    }
    // Rol 'anatpt' ise
    elseif ($userRole === 'anatpt') {
        $productPrice = floatval($row["anatpt"]);
        $productFyt = floatval($row["anatpt"]);

        // 'anatpt' değeri 0 ise, 'ant' ve 'kampfiyat' kullan
        if ($productPrice == 0) {
            $productPrice = floatval($row["ant"]);
            if (!empty($productResult["kampanya"]) && is_numeric($productResult["kampanya"])) {
    $kampanyaYuzdesi = floatval($productResult["kampanya"]);
    $productFyt = $productPrice * (1 - $kampanyaYuzdesi / 100);
} else {
    $productFyt = $productPrice;
}

        }
    }
    // Diğer roller için bir işlem ekleyebilirsiniz
}
        // İndirimli fiyatları hesaplayın
        $discountedPriceTaksit = $productFyt * (1 - $discountRates['taksit']);
        $discountedPriceTekcekim = $productFyt * (1 - $discountRates['tekcekim']);
        $discountedPriceNakit = $productFyt * (1 - $discountRates['nakit']);

        // Ürün bilgilerini HTML olarak yazdır
        echo "<div class='product'>";
        echo "<div class='flex flex-col justify-center items-center bg-gray-50 '>";
        echo "<div class='bg-white shadow-md hover:scale-105 hover:shadow-xl duration-500'>";
        echo "<a href='product.php?productName={$productName}'>";
        echo "<span class='yeni " . 
            ($productDurum === 'YENİ' ? 'bg-red-500' : 
            ($productDurum === 'ÖZEL' ? 'bg-green-500' : 
            ($productDurum === 'YOLDA' ? 'bg-blue-500' : ''))) . 
            "'>{$productDurum}</span>";

        $productMarka = !empty($productMarka) ? $productMarka : 'ygt'; // default-marka-image.png varsayılan resim olabilir
		echo "<img loading='lazy' src='resim/marka/{$productMarka}.png' class='marka' />";

		
        $imageToShow = htmlspecialchars($row['resimurl'] ?? '', ENT_QUOTES, 'UTF-8');
		$defaultImage = '/resim/urun/kontak.jpg';

		echo "<img 
			style='width:100%;' 
			loading='lazy' 
			src='{$imageToShow}' 
			alt='{$productName}' 
            width='288' height='200'          
            fetchpriority='high' 
            loading='eager' 
            decoding='async'
            class='h-50 w-72 object-cover'
			onerror=\"this.onerror=null;this.src='{$defaultImage}';\" 
		/>";

        echo "<div class='px-4 py-3 h-50 object-cover'>";

        $productStokNot = '';
        if (isset($_SESSION['user']) && $_SESSION['user']['cari'] === 'PLASİYER') {
            $productStokNot = getColumnByStokkodu($pdo, $row['stokkodu']);
        }
        // Oturum kontrolü
        if (isset($_SESSION['user'])) {
            echo "<h3 style='background: red; color: white; position: absolute; font-size: 13px; margin: 0; right: 10px;'>$productKmpny</h3>";
        } else {
            if (!empty($productKmpny)) {
                echo "<h3 style='background: red; color: white; position: absolute; font-size: 13px; margin: 0; right: 10px;'>KAMPANYA</h3>";
            }
        }

        echo "<span class='text-lg font-bold text-black truncate block capitalize'>{$productName}</span>";
        echo "<span class='text-gray-400 mr-3 uppercase text-xs' style='height:45px; display: block;'>" .
    htmlspecialchars($truncatedProductInfo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
    "</span>";

        // Stok Notu sadece PLASİYER'e göster
        echo "<div style='color:red; font-size:16px; font-weight:bold; margin-top:2px; height:15px;'>$productStokNot</div>";
        echo "</a>";
if (isset($_SESSION['user'])) {
    $kampanyaYuzdesi = floatval($row["kampanya"]);
    $kampanyaCarpan = (100 - $kampanyaYuzdesi) / 100;

    echo "<div style='display: flex; flex-wrap: wrap; overflow: hidden; margin-top: 2px; height: 85px;    align-items: flex-start;    align-content: flex-start;	font-size: 85%;'>";

    // Başlıklar
    $headers = ['Liste', 'Taksit', 'Tek Ç.', 'Nakit'];
    foreach ($headers as $header) {
        echo "<div style='flex: 1; font-weight: bold; text-align: center; padding: 4px; '>$header</div>";
    }

    // Satır boşluğu için satır altı
    echo "<div style='flex-basis: 100%; height: 1px; background-color: #ccc;'></div>";

    // Fiyatlar
    if ($kampanyaYuzdesi > 0) {
        // Kampanyalı: eski fiyat çizili, yeni fiyat kalın ve kırmızı
        $listeEski = round(($productListe / 0.9) / 0.8 / $kampanyaCarpan);
        $listeYeni = round(($productListe / 0.9) / 0.8);

        $taksitEski = round($discountedPriceTaksit / $kampanyaCarpan);
        $tekcekimEski = round($discountedPriceTekcekim / $kampanyaCarpan);
        $nakitEski = round($discountedPriceNakit / $kampanyaCarpan);

        $taksitYeni = round($discountedPriceTaksit);
        $tekcekimYeni = round($discountedPriceTekcekim);
        $nakitYeni = round($discountedPriceNakit);

        $prices = [
            [$listeEski, $listeYeni],
            [$taksitEski, $taksitYeni],
            [$tekcekimEski, $tekcekimYeni],
            [$nakitEski, $nakitYeni]
        ];

        foreach ($prices as $pair) {
            echo "<div style='flex: 1; text-align: center; padding: 4px; font-size: 18px;'>";
            echo "<del style='color: darkred; font-weight: bold;'>{$pair[0]}₺</del> ";
            echo "<span style='color: darkgreen; font-weight: bold;'>{$pair[1]}₺</span>";
            echo "</div>";
        }
    } else {
        // Kampanyasız: sadece fiyat
        $prices = [
            round(($productListe / 0.9) / 0.8),
            round($discountedPriceTaksit),
            round($discountedPriceTekcekim),
            round($discountedPriceNakit)
        ];

        foreach ($prices as $fiyat) {
            echo "<div style='flex: 1; text-align: center; padding: 4px; font-size: 18px;'>";
            echo "<span style='font-weight: bold;'>{$fiyat}₺</span>";
            echo "</div>";
        }
    }

    echo "</div>";
}

        echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";

        // STOK durumunu belirle
        $stockClass = '';
        $stockText = '';

        if ($productSdrum === "VAR") {
            $stockClass = 'text-green-500';
            $stockText = $productSdrum;
        } elseif ($productSdrum === "KRİTİK") {
            $stockClass = 'text-orange-400';
            $stockText = $productSdrum;
        } else {
            $stockClass = 'text-red-600';
            $stockText = $productSdrum;
        }

        // HTML çıktısını oluştur
        echo "<p>STOK: <span class='font-bold $stockClass'>$stockText</span></p>";
        echo "<p class='ml-auto'>";

        // Ürün adını ve fiyatını JavaScript'e geçir
        $productNameEsc = htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
        $productFytEsc = htmlspecialchars($productFyt, ENT_QUOTES, 'UTF-8');
		$productKmpny = str_replace('%', '', $productKmpny);
        $kampanyaYuzdesi = htmlspecialchars($productKmpny, ENT_QUOTES, 'UTF-8');
		
        // Kullanıcı oturum durumuna göre buton rengini ayarla
        $buttonColor = isset($_SESSION['user']) ? 'green' : 'gray';
        $buttonText = 'SEPET';

        echo "<button style='color:white;background:$buttonColor;' onclick='showPopup(\"$productNameEsc\", $productFytEsc, \"$productImage\", $kampanyaYuzdesi)'>$buttonText</button>";
        echo "</p>";
        echo "</div></div></div></div></div>";
    }
} else {
    echo "0 sonuç";
}
// Oturum kontrolü ve rol doğrulama
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'anttpt') { 
    exit();
}
// Bağlantıyı kapat
$pdo = null;
?>
