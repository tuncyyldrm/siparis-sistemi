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

// Ürünleri bağlama işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selectedProducts'])) {
    $selectedProducts = json_decode($_POST['selectedProducts'], true);
    $productCount = count($selectedProducts);

    if ($productCount < 2) {
        $message = "En az iki ürün seçilmelidir.";
    } else {
        try {
            $conn->begin_transaction(); // Başarıyla bağlanamazsa geri al

            // Önceki bağlantıları tamamen temizle
            $deleteQuery = "DELETE FROM product_links WHERE product_stokkodu IN (SELECT stokkodu FROM product WHERE stokkodu IN (" . implode(',', array_fill(0, $productCount, '?')) . ")) OR linked_stokkodu IN (SELECT stokkodu FROM product WHERE stokkodu IN (" . implode(',', array_fill(0, $productCount, '?')) . "))";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param(str_repeat('s', $productCount) . str_repeat('s', $productCount), ...$selectedProducts, ...$selectedProducts);
            $deleteStmt->execute();

            // Yeni bağlantıları oluştur
            $query = "INSERT INTO product_links (product_stokkodu, linked_stokkodu) VALUES (?, ?)";
            $stmt = $conn->prepare($query);

            // Her ürünü diğerleriyle bağla
            for ($i = 0; $i < $productCount; $i++) {
                for ($j = $i + 1; $j < $productCount; $j++) {
                    $stmt->bind_param('ss', $selectedProducts[$i], $selectedProducts[$j]);
                    $stmt->execute();
                    $stmt->bind_param('ss', $selectedProducts[$j], $selectedProducts[$i]);
                    $stmt->execute();
                }
            }

            $conn->commit();
            $message = "Ürünler başarıyla bağlandı.";
        } catch (Exception $e) {
            $conn->rollback(); // Hata durumunda işlemleri geri al
            $message = "Ürünler bağlanırken hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

// Arama işlemi
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
}

// Ürünleri stok numarasına göre sıralayarak al
$query = "SELECT stokkodu, aciklama FROM product WHERE stokkodu LIKE ? OR aciklama LIKE ? ORDER BY stokkodu";
$stmt = $conn->prepare($query);
$searchParam = "%$searchQuery%";
$stmt->bind_param('ss', $searchParam, $searchParam);
$stmt->execute();
$results = $stmt->get_result();
$products = $results->fetch_all(MYSQLI_ASSOC);

// Bağlı ürünleri al
$query = "SELECT product_stokkodu, linked_stokkodu FROM product_links WHERE product_stokkodu IN (SELECT stokkodu FROM product) OR linked_stokkodu IN (SELECT stokkodu FROM product)";
$stmt = $conn->prepare($query);
$stmt->execute();
$linkedResults = $stmt->get_result();
$linkedProducts = [];
while ($row = $linkedResults->fetch_assoc()) {
    $linkedProducts[$row['product_stokkodu']][] = $row['linked_stokkodu'];
}

// Veritabanı bağlantısını kapat
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ürünleri Bağla</title>
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
        .product-list {
            list-style: none;
            padding: 0;
        }
        .product-list li {
            margin: 5px 0;
            cursor: pointer;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .product-list li.selected {
            background-color: #c8e6c9;
        }
        .product-list .linked {
            background-color: #dff0d8;
            border-left: 5px solid #3c763d;
        }
        .product-list .not-linked {
            background-color: #f2dede;
            border-left: 5px solid #a94442;
        }
        .linked-products {
            background-color: #e3f2fd;
            border-left: 5px solid #1e88e5;
            margin-top: 10px;
            padding: 5px;
            border-radius: 5px;
        }
        .message {
            margin-top: 20px;
            font-weight: bold;
            color: red;
        }
        .search-form {
            margin: 20px;
        }
		.search-form input {
			height: 20px;
			padding: 5px; /* İç boşluk ekleyerek input alanının içeriğiyle kenar arasındaki mesafeyi artırabilirsiniz. */
			border: 1px solid #ddd; /* Input alanına bir sınır ekleyerek daha belirgin hale getirebilirsiniz. */
			border-radius: 4px; /* Köşeleri yuvarlatır. */
		}
		.ustmenu{
		position: sticky;
		top: 0;
		background: white;
		display: flex;			
		}
    </style>
</head>
<body>
    <!-- Geri butonu -->
    <a href="../admin/" class="button">Geri</a>
    <h1>Ürünleri Bağla</h1>
<div class="ustmenu">

    <form action="" method="post" id="link-form">
        <input type="hidden" name="selectedProducts" id="selectedProducts">
        <input type="submit" name="link_products" value="Bağla" class="button">
    </form>
    <form class="search-form" action="" method="get">
        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Stok Kodu veya Açıklama ile Ara">
        <input type="submit" value="Ara">
    </form>
</div>

    <?php if (isset($message)): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <ul class="product-list" id="product-list">
        <?php foreach ($products as $product): ?>
            <li class="product-item <?php echo isset($linkedProducts[$product['stokkodu']]) ? 'linked' : 'not-linked'; ?>" 
                data-stokkodu="<?php echo htmlspecialchars($product['stokkodu']); ?>">
                <?php echo htmlspecialchars($product['stokkodu']) . ' - ' . htmlspecialchars($product['aciklama']); ?>

                <?php if (isset($linkedProducts[$product['stokkodu']])): ?>
                    <div class="linked-products">
                        Bağlı olduğu ürünler: 
                        <?php foreach ($linkedProducts[$product['stokkodu']] as $linkedProduct): ?>
                            <?php echo htmlspecialchars($linkedProduct) . ' '; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <script>
        const productList = document.querySelectorAll('.product-item');
        const selectedProducts = [];

        productList.forEach(item => {
            item.addEventListener('click', function() {
                const stokkodu = this.getAttribute('data-stokkodu');
                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    const index = selectedProducts.indexOf(stokkodu);
                    if (index > -1) {
                        selectedProducts.splice(index, 1);
                    }
                } else {
                    this.classList.add('selected');
                    selectedProducts.push(stokkodu);
                }
            });
        });

        const form = document.getElementById('link-form');
        form.addEventListener('submit', function(event) {
            document.getElementById('selectedProducts').value = JSON.stringify(selectedProducts);
        });
    </script>

</body>
</html>
