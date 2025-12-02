// Arama raporlarını getir
function fetchSearchReports($pdo) {
    $sql = "SELECT * FROM searches ORDER BY searchDate DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Admin panelinde arama raporlarını listele
$searchReports = fetchSearchReports($pdo);

if ($searchReports) {
    echo "<table class='table-auto w-full text-left'>";
    echo "<thead><tr><th>Arama Terimi</th><th>Kullanıcı ID</th><th>Tarih</th></tr></thead>";
    echo "<tbody>";
    foreach ($searchReports as $report) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($report['searchQuery'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td>" . htmlspecialchars($report['userId'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td>" . htmlspecialchars($report['searchDate'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "Hiç arama raporu yok.";
}
