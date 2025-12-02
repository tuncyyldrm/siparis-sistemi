<?php
// Hata raporlamayı aç
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../db_config.php'; // Veritabanı bağlantısını içeren dosya

// Oturum kontrolü ve rol doğrulama
if (!isset($_SESSION['user']) || $_SESSION['user']['cari'] !== 'PLASİYER') {
    header('Location: ../'); // Yetkisiz erişim veya oturum açmamış kullanıcıyı anasayfaya yönlendir
    exit();
}

// Sayfalama değişkenlerini ayarla
$records_per_page = 30; // Sayfa başına gösterilecek kayıt sayısı
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Filtreleme ve sıralama
$searchQuery = isset($_GET['searchQuery']) ? $conn->real_escape_string($_GET['searchQuery']) : '';
$customerCode = isset($_GET['customerCode']) ? $conn->real_escape_string($_GET['customerCode']) : '';
$excludePlasiyer = isset($_GET['excludePlasiyer']) ? true : false;
$startDate = isset($_GET['startDate']) ? $conn->real_escape_string($_GET['startDate']) : '';
$endDate = isset($_GET['endDate']) ? $conn->real_escape_string($_GET['endDate']) : '';
$sortBy = isset($_GET['sortBy']) ? $conn->real_escape_string($_GET['sortBy']) : 'searchDate_DESC';

$orderBy = $sortBy === 'searchDate_ASC' ? 's.searchDate ASC' : 's.searchDate DESC';

// Toplam kayıt sayısını hesapla
$total_sql = "SELECT COUNT(*) as total FROM searches s
    LEFT JOIN users u ON s.userId = u.usersId
    WHERE s.searchQuery LIKE '%$searchQuery%'
";
if ($customerCode) {
    $total_sql .= " AND s.userId LIKE '%$customerCode%'";
}
if ($excludePlasiyer) {
    $total_sql .= " AND COALESCE(u.cari, 'Misafir') != 'PLASİYER'";
}
if ($startDate && $endDate) {
    $total_sql .= " AND s.searchDate BETWEEN '$startDate' AND '$endDate'";
} elseif ($startDate) {
    $total_sql .= " AND s.searchDate >= '$startDate'";
} elseif ($endDate) {
    $total_sql .= " AND s.searchDate <= '$endDate'";
}
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Arama raporlarını getir (limit ve offset kullanarak)
function fetchSearchReports($conn, $offset, $records_per_page, $searchQuery, $customerCode, $excludePlasiyer, $startDate, $endDate, $orderBy) {
    $sql = "
        SELECT 
            s.searchId, 
            s.searchQuery, 
            s.userId, 
            s.searchDate, 
            COALESCE(u.cari, 'Misafir') AS cari
        FROM searches s
        LEFT JOIN users u ON s.userId = u.usersId
        WHERE s.searchQuery LIKE '%$searchQuery%'
    ";
    if ($customerCode) {
        $sql .= " AND s.userId LIKE '%$customerCode%'";
    }
    if ($excludePlasiyer) {
        $sql .= " AND COALESCE(u.cari, 'Misafir') != 'PLASİYER'";
    }
    if ($startDate && $endDate) {
        $sql .= " AND s.searchDate BETWEEN '$startDate' AND '$endDate'";
    } elseif ($startDate) {
        $sql .= " AND s.searchDate >= '$startDate'";
    } elseif ($endDate) {
        $sql .= " AND s.searchDate <= '$endDate'";
    }
    $sql .= " ORDER BY $orderBy
        LIMIT $offset, $records_per_page
    ";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        return [];
    }
}

// Admin panelinde arama raporlarını listele
$searchReports = fetchSearchReports($conn, $offset, $records_per_page, $searchQuery, $customerCode, $excludePlasiyer, $startDate, $endDate, $orderBy);

// Filtreleme ve sıralama için URL parametrelerini oluştur
$filterQuery = '';
if ($searchQuery) {
    $filterQuery .= '&searchQuery=' . urlencode($searchQuery);
}
if ($customerCode) {
    $filterQuery .= '&customerCode=' . urlencode($customerCode);
}
if ($excludePlasiyer) {
    $filterQuery .= '&excludePlasiyer=1';
}
if ($startDate) {
    $filterQuery .= '&startDate=' . urlencode($startDate);
}
if ($endDate) {
    $filterQuery .= '&endDate=' . urlencode($endDate);
}
if ($sortBy) {
    $filterQuery .= '&sortBy=' . urlencode($sortBy);
}
?>



<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Raporları</title>
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

        .report-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            background-color: #f9f9f9;
        }
		.a1{			
			width:100%;
		}
		.a2{			
			width:100%;
		}

        .report-card div {
            flex: 1;
            padding: 5px;
        }

        .report-card .actions {
            text-align: right;
        }

        .report-card .actions button {
            background-color: #f44336;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }

        .report-card .actions button:hover {
            background-color: #c62828;
        }

        @media (max-width: 600px) {
            .report-card {
                flex-direction: column;
            }
            .report-card div {
                flex: none;
                width: 100%;
            }
			        .report-card .actions button {
            background-color: #f44336;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        }

    </style>
</head>
<body>


<div class="container">
<!-- Geri butonu -->
<a href="../admin/" class="button">Geri</a>
<h1>Arama Raporları</h1>


<!-- Filtreleme ve sıralama formu -->
<form class="filter-form" method="GET" action="">
<div>
    <label for="searchQuery">Arama Terimi:</label>
    <input type="text" id="searchQuery" name="searchQuery" value="<?php echo htmlspecialchars($_GET['searchQuery'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
</div>
<div>
    <label for="customerCode">Müşteri Kodu:</label>
    <input type="text" id="customerCode" name="customerCode" value="<?php echo htmlspecialchars($_GET['customerCode'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
</div>
<div>
    <label for="excludePlasiyer">Plasiyer Hariç:</label>
    <input type="checkbox" id="excludePlasiyer" name="excludePlasiyer" <?php echo isset($_GET['excludePlasiyer']) ? 'checked' : ''; ?>>
</div>
<div>
    <label for="startDate">Başlangıç Tarihi:</label>
    <input type="date" id="startDate" name="startDate" value="<?php echo htmlspecialchars($_GET['startDate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

    <label for="endDate">Bitiş Tarihi:</label>
    <input type="date" id="endDate" name="endDate" value="<?php echo htmlspecialchars($_GET['endDate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

    <label for="sortBy">Sıralama:</label>
    <select id="sortBy" name="sortBy">
        <option value="searchDate_DESC" <?php echo (isset($_GET['sortBy']) && $_GET['sortBy'] === 'searchDate_DESC') ? 'selected' : ''; ?>>Tarihe Göre Azalan</option>
        <option value="searchDate_ASC" <?php echo (isset($_GET['sortBy']) && $_GET['sortBy'] === 'searchDate_ASC') ? 'selected' : ''; ?>>Tarihe Göre Artan</option>
    </select>
</div>
    <button type="submit" class="button">Filtrele</button>
</form>


    <?php if ($searchReports): ?>
        <?php foreach ($searchReports as $report): ?>
            <div class="report-card">
                <div><strong class="a1">Arama Terimi:</strong> <div class="a2"><?php echo htmlspecialchars($report['searchQuery'], ENT_QUOTES, 'UTF-8'); ?></div></div>
                <div><strong class="a1">Cari:</strong> <div class="a2"><?php echo htmlspecialchars($report['cari'], ENT_QUOTES, 'UTF-8'); ?></div></div>
                <div><strong class="a1">Tarih:</strong> <div class="a2"><?php echo htmlspecialchars($report['searchDate'], ENT_QUOTES, 'UTF-8'); ?></div></div>
                <div><strong class="a1">Müşteri Kodu:</strong> <div class="a2"><?php echo htmlspecialchars($report['userId'] ?? 'Misafir', ENT_QUOTES, 'UTF-8'); ?></div></div>
                <div class="actions">
                    <!-- Silme formu -->
                    <form action="delete_report.php" method="POST" style="display:inline;">
                        <input type="hidden" name="searchId" value="<?php echo htmlspecialchars($report['searchId'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" onclick="return confirm('Bu arama raporunu silmek istediğinize emin misiniz?');">Sil</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="report-card">
            <div>Hiç arama raporu yok.</div>
        </div>
    <?php endif; ?>

    <!-- Sayfalama -->
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?><?php echo $filterQuery; ?>">&laquo; Önceki</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $filterQuery; ?>" class="<?php if ($i == $current_page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?><?php echo $filterQuery; ?>">Sonraki &raquo;</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
<?php
// Veritabanı bağlantısını kapat
$conn->close();
?>
