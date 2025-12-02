<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../db_config.php';

// Oturum kontrolü ve rol doğrulama
if (!isset($_SESSION['user']) || $_SESSION['user']['cari'] !== 'PLASİYER') {
    header('Location: ../'); // Yetkisiz erişim veya oturum açmamış kullanıcıyı login sayfasına yönlendir
    exit();
}

// Ödeme türüne göre indirim oranlarını tanımla
$paymentDiscounts = [
    "taksit" => 0.0,
    "tekcekim" => 0.10,
    "nakit" => 0.15
];

// Veritabanından sepet verilerini çek
function fetchCartSummary($conn) {
    global $paymentDiscounts; // Global değişkeni kullan

    $query = "
        SELECT 
            carts.user_id, 
            users.username, 
            users.cari,  /* Cari bilgisi eklendi */
            carts.payment_type,
            MAX(carts.added_at) AS last_updated, 
            SUM(carts.product_price * carts.quantity) AS total
        FROM carts
        JOIN users ON carts.user_id = users.usersId
        GROUP BY carts.user_id, users.username, users.cari, carts.payment_type
        ORDER BY MAX(carts.added_at) DESC
    ";
    $result = $conn->query($query);

    if (!$result) {
        error_log("Sorgu hatası: " . $conn->error);
        return [];
    }

    $cartSummaries = [];
    while ($row = $result->fetch_assoc()) {
        $paymentType = $row['payment_type'];
        $discount = isset($paymentDiscounts[$paymentType]) ? $paymentDiscounts[$paymentType] : 0.0;
        $totalWithDiscount = floatval($row['total']) * (1 - $discount);
        
        $cartSummaries[] = [
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'cari' => $row['cari'],  // Cari bilgisi eklendi
            'last_updated' => $row['last_updated'],
            'total' => number_format(floor($totalWithDiscount), 2, ',', '.'), // Rakam formatlaması
            'payment_type' => $paymentType
        ];
    }

    return $cartSummaries;
}



// Veritabanından sepet detaylarını çek
function fetchCartDetails($conn, $user_id) {
    global $paymentDiscounts; // Global değişkeni kullan

    // Kullanıcının ödeme türünü almak için sorgu
    $paymentTypeQuery = "SELECT payment_type FROM carts WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($paymentTypeQuery);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $paymentTypeResult = $stmt->get_result();
    
    if ($paymentTypeResult && $paymentTypeResult->num_rows > 0) {
        $paymentTypeRow = $paymentTypeResult->fetch_assoc();
        $paymentType = $paymentTypeRow['payment_type'];
    } else {
        $paymentType = 'taksit'; // Varsayılan ödeme türü
    }

    $stmt->close();

    // Sepet detaylarını çeken sorgu
    $query = "
        SELECT 
            product_name, 
            product_price, 
            quantity, 
            added_at
        FROM carts
        WHERE user_id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        error_log("Sorgu hatası: " . $conn->error);
        return [];
    }

    $cartItems = [];
    $discount = isset($paymentDiscounts[$paymentType]) ? $paymentDiscounts[$paymentType] : 0.0;

    while ($row = $result->fetch_assoc()) {
        $priceWithDiscount = floatval($row['product_price']) * (1 - $discount);
        $cartItems[] = [
            'name' => $row['product_name'],
            'price' => number_format(floor($priceWithDiscount), 2, ',', '.'),
            'quantity' => intval($row['quantity']),
            'added_at' => $row['added_at'],
            'payment_type' => $paymentType
        ];
    }

    $stmt->close();
    return $cartItems;
}

$cartSummaries = fetchCartSummary($conn);

if (isset($_GET['user_id'])) {
    $selected_user_id = $_GET['user_id'];
    $cartDetails = fetchCartDetails($conn, $selected_user_id);
    echo json_encode($cartDetails);
    exit();
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.7">
    <title>Müşteri Sepetleri</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 18px;
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
        .button {
            padding: 10px 5px;
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
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 16px;
            text-align: left;
        }
        .details-table th, .details-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .details-table th {
            background-color: #f2f2f2;
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
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 10px;
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
<a href="../admin/" class="button">Geri</a>
    <h1>Müşteri Sepetleri</h1>

    <!-- Sepet özet tablosu -->
<table id="cartSummaryTable">
    <thead>
        <tr>
            <th style="width: 30%; cursor: pointer;" onclick="sortTable(0)">M. Kodu</th>
			<th style="width: 40%; cursor: pointer;" onclick="sortTable(1)">Cari</th>
            <th style="width: 10%; cursor: pointer;" onclick="sortTable(2)">Tarih</th>
            <th style="width: 10%; cursor: pointer;" onclick="sortTable(3)">Toplam Tutar</th>
            <th style="width: 5%; cursor: pointer;" onclick="sortTable(4)">Ödeme Tipi</th>
            <th style="width: 5%;">Detay</th>
        </tr>
    </thead>
    <tbody id="tableBody">
        <?php if (!empty($cartSummaries)): ?>
            <?php foreach ($cartSummaries as $summary): ?>
                <tr>
                    <td><?php echo htmlspecialchars($summary['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($summary['cari'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($summary['last_updated'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo $summary['total']; ?> TL</td>
                    <td><?php echo htmlspecialchars($summary['payment_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
					
                     <button class="button" onclick="openCartDetails('<?php echo htmlspecialchars($summary['user_id'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($summary['username'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($summary['last_updated'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $summary['total']; ?>', '<?php echo htmlspecialchars($summary['payment_type'], ENT_QUOTES, 'UTF-8'); ?>')">Detaylar</button> 
						
					</td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">Hiç sepet verisi bulunamadı.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div id="cartDetailsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Sepet Detayları</h2>
		<span id=""></span>
		<div style="
    display: grid;
    grid-template-columns: repeat(4, 0.3fr);
    gap: 0;
    margin-bottom: 0px;
"><strong>Kullanıcı Adı:</strong> <span id="modalUsername"></span>
<strong>Güncelleme Tarihi:</strong> <span id="modalLastUpdated"></span>
<strong>Toplam Tutar:</strong> <span id="modalTotal"></span>
<strong>Ödeme Tipi:</strong> <span id="modalPaymentType"></span></div>

        <table class="details-table">
		
            <thead>
                <tr>
                    <th>Ürün Adı</th>
                    <th>Fiyat</th>
                    <th>Miktar</th>
                    <th>Eklenme Tarihi</th>
                    <th>Ödeme Tipi</th>
                </tr>
            </thead>
            <tbody id="cartDetailsTableBody">
                <!-- Detaylar burada dinamik olarak yüklenecek -->
            </tbody>
        </table>
    </div>
</div>

	
	<script>
	// Modal'ı aç ve detayları yükle
function openCartDetails(user_id, username, lastUpdated, total, paymentType) {
    var modal = document.getElementById("cartDetailsModal");
    var tableBody = document.getElementById("cartDetailsTableBody");

    // Sepet özet bilgilerini modal'a ekle
    document.getElementById("modalUsername").innerText = username;
    document.getElementById("modalLastUpdated").innerText = lastUpdated;
    document.getElementById("modalTotal").innerText = total + ' TL';
    document.getElementById("modalPaymentType").innerText = paymentType;

    // Eski verileri temizle
    tableBody.innerHTML = '';

    // AJAX ile sepet detaylarını al
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "admin_cart.php?user_id=" + user_id, true);

    xhr.onload = function () {
        if (xhr.status === 200) {
            var cartDetails = JSON.parse(xhr.responseText);

            // Tabloya verileri ekle
            cartDetails.forEach(function(item) {
                var row = tableBody.insertRow();
                row.insertCell(0).innerHTML = item.name;
                row.insertCell(1).innerHTML = item.price + ' TL';
                row.insertCell(2).innerHTML = item.quantity;
                row.insertCell(3).innerHTML = item.added_at;
                row.insertCell(4).innerHTML = item.payment_type;
            });

            modal.style.display = "block";
        }
    };
    xhr.send();
}


// Modal'ı kapat
var modal = document.getElementById("cartDetailsModal");
var span = document.getElementsByClassName("close")[0];

span.onclick = function() {
    modal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Sıralama yönlerini ve hangi sütunun sıralandığını tutan değişkenler
var sortDirection = {}; // Her sütun için sıralama yönünü saklayan obje
var currentSortColumn = null; // Şu anda sıralanan sütun indeksini saklar

// Tabloyu sıralama fonksiyonu
function sortTable(columnIndex) {
    var table = document.getElementById("cartSummaryTable");
    var rows = Array.from(table.getElementsByTagName("tbody")[0].getElementsByTagName("tr"));

    // Eğer yeni bir sütuna tıklanmışsa veya ilk tıklama ise
    if (currentSortColumn !== columnIndex) {
        sortDirection = {}; // Diğer sütunların sıralama yönlerini sıfırla
        currentSortColumn = columnIndex; // Şu anda sıralanan sütunu güncelle
    }

    // İlk tıklamada varsayılan olarak artan sıralama yapacak, tekrar tıkladığında ters sıralama
    if (!sortDirection[columnIndex]) {
        sortDirection[columnIndex] = true; // İlk tıklamada artan sıralama
    } else {
        sortDirection[columnIndex] = !sortDirection[columnIndex]; // Sıralama yönünü tersine çevir
    }

    var sortedRows = rows.sort(function(a, b) {
        var aText = a.getElementsByTagName("td")[columnIndex].innerText.trim();
        var bText = b.getElementsByTagName("td")[columnIndex].innerText.trim();

        // Eğer sayı ise (örneğin toplam tutar), sayısal sıralama yap
        if (!isNaN(parseFloat(aText.replace(/[^0-9.,]/g, ''))) && !isNaN(parseFloat(bText.replace(/[^0-9.,]/g, '')))) {
            return sortDirection[columnIndex] 
                ? parseFloat(aText.replace(/[^0-9.,]/g, '').replace(',', '.')) - parseFloat(bText.replace(/[^0-9.,]/g, '').replace(',', '.')) 
                : parseFloat(bText.replace(/[^0-9.,]/g, '').replace(',', '.')) - parseFloat(aText.replace(/[^0-9.,]/g, '').replace(',', '.'));
        }

        // Tarih sütunu için özel sıralama (tarih formatını uygun hale getiriyoruz)
        if (columnIndex == 2) {
            var dateA = new Date(aText);
            var dateB = new Date(bText);
            return sortDirection[columnIndex] ? dateA - dateB : dateB - dateA;
        }

        // Diğer sütunlar için alfabetik sıralama
        return sortDirection[columnIndex] ? aText.localeCompare(bText) : bText.localeCompare(aText);
    });

    // Sıralanmış satırları tabloya tekrar ekle
    var tbody = table.getElementsByTagName("tbody")[0];
    tbody.innerHTML = ""; // Mevcut satırları temizle
    sortedRows.forEach(function(row) {
        tbody.appendChild(row); // Sıralanmış satırları ekle
    });
}
	</script>
</body>
</html>