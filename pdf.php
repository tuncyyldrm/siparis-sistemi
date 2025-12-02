
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
 
    <title>Yiğit Kontak PDF</title>
    <link rel="stylesheet" href="styles-v1.1.css">
    <script src="scripts-v1.2.js"></script>
    <script src="giris-v1.4.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <link rel="manifest" href="/siparis/manifest.json">
    <!-- Service Worker script YOK -->

	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<link rel="apple-touch-icon" href="/icons/kontaksiparisicon-192x192.png">

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
    .product {
        position: relative;
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
	</style>
</head>
<body style="background-color: #ffffffff;">
	<div style="flex: 1;">
        <div style="display:none;"><header>
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
        </div>

        <section style="grid-template-columns: repeat(2, 49%); grid-gap: 70px 1%; padding: 0 5mm; width: 210mm; margin: 0 auto;" class="product-catalog" id="urunlist"></section>
                <!-- Sayfalama bölgesi -->
	</div>
<!-- JavaScript -->
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker
        .register('/siparis/service-worker.js', { scope: '/siparis/' })
        .then(() => console.log('Sipariş dizini için Service Worker kaydedildi.'))
        .catch(err => console.error('Service Worker kaydı başarısız:', err));
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('query') || '';
    const durum = urlParams.get('durum') || ''; // Durum parametresini al
    const kampanya = urlParams.get('kampanya') || ''; // kampanya parametresini al
    const page = parseInt(urlParams.get('page')) || 1;

    // Arama kutusuna URL parametresini yerleştirin
    document.getElementById('search-input').value = query;

    // Ürünleri ve sayfalama bağlantılarını yükle
    fetchProducts(query, durum, kampanya, page);
});

function fetchProducts(query = '', durum = '', kampanya = '', page = 1) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `pdf_getProducts.php?query=${encodeURIComponent(query)}&durum=${encodeURIComponent(durum)}&kampanya=${encodeURIComponent(kampanya)}&page=${page}`, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('urunlist').innerHTML = xhr.responseText;
            
            // Toplam sayfa sayısını alın ve sayfalama bağlantılarını oluşturun
            const totalPages = parseInt(xhr.getResponseHeader('X-Total-Pages')) || 1;
            createPagination(totalPages, page);
        }
    };
    xhr.send();
}

function createPagination(totalPages, currentPage) {
    const paginationContainer = document.getElementById('pagination');
    paginationContainer.innerHTML = '';

    // Önceki sayfa bağlantısı
    if (currentPage > 1) {
        const prevLink = document.createElement('a');
        prevLink.href = 'javascript:void(0);';
        prevLink.textContent = 'Önceki';
        prevLink.onclick = function() { changePage(currentPage - 1); };
        paginationContainer.appendChild(prevLink);
    }

    // Sayfa numaraları
    for (let i = 1; i <= totalPages; i++) {
        const pageLink = document.createElement('a');
        pageLink.href = 'javascript:void(0);';
        pageLink.textContent = i;
        if (i === currentPage) {
            pageLink.classList.add('active');
        }
        pageLink.onclick = function() { changePage(i); };
        paginationContainer.appendChild(pageLink);
    }

    // Sonraki sayfa bağlantısı
    if (currentPage < totalPages) {
        const nextLink = document.createElement('a');
        nextLink.href = 'javascript:void(0);';
        nextLink.textContent = 'Sonraki';
        nextLink.onclick = function() { changePage(currentPage + 1); };
        paginationContainer.appendChild(nextLink);
    }
}

function changePage(page) {
    const query = document.getElementById('search-input').value;
    const durum = new URLSearchParams(window.location.search).get('durum') || ''; // Durum parametresini al
    const kampanya = new URLSearchParams(window.location.search).get('kampanya') || ''; // kampanya parametresini al

    // URL'yi güncelle
    const newUrl = `?query=${encodeURIComponent(query)}&durum=${encodeURIComponent(durum)}&kampanya=${encodeURIComponent(kampanya)}&page=${page}`;
    window.history.pushState({ path: newUrl }, '', newUrl);

    // Ürünleri ve sayfalama bağlantılarını yükle
    fetchProducts(query, durum, kampanya, page);

    // Sayfanın başına kaydır (animasyonsuz)
    window.scrollTo(0, 0);
}

</script>
</body>
</html>
