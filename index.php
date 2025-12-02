
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
 
    <title>Yiğit Kontak Sipariş</title>
    
<script>
if ('serviceWorker' in navigator) {
navigator.serviceWorker.register('/siparis/service-worker.js', {
    scope: '/siparis/'
})
.then(() => console.log('Sipariş Service Worker kaydedildi'))
.catch(err => console.error('Sipariş SW kayıt hatası:', err));
}
</script>

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
    aspect-ratio: 1 / 1; /* Veya gerçek görsel oranı, ör: 288 / 200 */
    object-fit: cover;
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


        <main>
            <div class="left-column">
				<div id="modal" class="modal">
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
				<section class="product-catalog" id="urunlist"></section>
<!-- Sayfalama bölgesi -->
<section id="pagination-container" class="pagination-container">
    <div class="pagination" id="pagination">
        <!-- Sayfalama bağlantıları buraya eklenecek -->
    </div>
</section>



            </div>

	<div class="right-column">
<!-- Sepet -->
<aside class="shopping-cart-container">
    <div class="shopping-cart" id="shopping-cart">
        <img alt="Yiğit Otomotiv" id="Header1_headerimg" src="resim/ygt_oto_banner.png" style="padding: 0 10px;margin: auto;">
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
        <div>5000 ₺ ve üzeri alışverişlerde kargo ücreti firmamız tarafından karşılanmaktadır.</div>
		
</div>

</aside>

	</div>
           
        </main>

			<!-- Daha fazla araç ekleyebilirsiniz 	-->
	<div class="banner-container">
        <div class="banner">
			<a href="./?query=	renault	" class="banner-item" title="renault araçlarını keşfedin">
                <img src="resim/arac/renault.png" alt="renault" loading="lazy">
            </a>
			<a href="./?query=	dacia	" class="banner-item" title="dacia araçlarını keşfedin">
                <img src="resim/arac/dacia.png" alt="dacia" loading="lazy">
            </a>
			<a href="./?query=	FIAT	" class="banner-item" title="FIAT araçlarını keşfedin">
                <img src="resim/arac/fiat.png" alt="FIAT" loading="lazy">
            </a>
            <a href="./?query=	PEUGEOT	" class="banner-item" title="PEUGEOT araçlarını keşfedin">
                <img src="resim/arac/peugeot.png" alt="PEUGEOT" loading="lazy">
            </a>
            <a href="./?query=	citroen	" class="banner-item" title="citroen araçlarını keşfedin">
                <img src="resim/arac/citroen.png" alt="citroen" loading="lazy">
            </a>
            <a href="./?query=	OPEL	" class="banner-item" title="OPEL araçlarını keşfedin">
                <img src="resim/arac/opel.png" alt="OPEL" loading="lazy">
            </a>
            <a href="./?query=	chevrolet	" class="banner-item" title="chevrolet araçlarını keşfedin">
                <img src="resim/arac/chevrolet.png" alt="chevrolet" loading="lazy">
            </a>
            <a href="./?query=	VW	" class="banner-item" title="VW araçlarını keşfedin">
                <img src="resim/arac/vw.png" alt="VW" loading="lazy">
            </a>
            <a href="./?query=	skoda	" class="banner-item" title="skoda araçlarını keşfedin">
                <img src="resim/arac/skoda.png" alt="skoda" loading="lazy">
            </a>
            <a href="./?query=	seat	" class="banner-item" title="seat araçlarını keşfedin">
                <img src="resim/arac/seat.png" alt="seat" loading="lazy">
            </a>
            <a href="./?query=	bmw	" class="banner-item" title="bmw araçlarını keşfedin">
                <img src="resim/arac/bmw.png" alt="bmw" loading="lazy">
            </a>
            <a href="./?query=	MERCEDES	" class="banner-item" title="MERCEDES araçlarını keşfedin">
                <img src="resim/arac/mercedes.png" alt="MERCEDES" loading="lazy">
            </a>
            <a href="./?query=	FORD	" class="banner-item" title="FORD araçlarını keşfedin">
                <img src="resim/arac/ford.png" alt="FORD" loading="lazy">
            </a>
            <a href="./?query=	honda	" class="banner-item" title="honda araçlarını keşfedin">
                <img src="resim/arac/honda.png" alt="honda" loading="lazy">
            </a>
            <a href="./?query=	nissan	" class="banner-item" title="nissan araçlarını keşfedin">
                <img src="resim/arac/nissan.png" alt="nissan" loading="lazy">
            </a>
            <a href="./?query=	TOYOTA	" class="banner-item" title="TOYOTA araçlarını keşfedin">
                <img src="resim/arac/toyota.png" alt="TOYOTA" loading="lazy">
            </a>
            <a href="./?query=	hyundai	" class="banner-item" title="hyundai araçlarını keşfedin">
                <img src="resim/arac/hyundai.png" alt="hyundai" loading="lazy">
            </a>
            <a href="./?query=	kia	" class="banner-item" title="kia araçlarını keşfedin">
                <img src="resim/arac/kia.png" alt="kia" loading="lazy">
            </a>
            <a href="./?query=	mitsubishi	" class="banner-item" title="mitsubishi araçlarını keşfedin">
                <img src="resim/arac/mitsubishi.png" alt="mitsubishi" loading="lazy">
            </a>
            <a href="./?query=	mazda	" class="banner-item" title="mazda araçlarını keşfedin">
                <img src="resim/arac/mazda.png" alt="mazda" loading="lazy">
            </a>
            <a href="./?query=	VOLVO	" class="banner-item" title="VOLVO araçlarını keşfedin">
                <img src="resim/arac/volvo.png" alt="VOLVO" loading="lazy">
            </a>			
            <a href="./?query=	isuzu	" class="banner-item" title="isuzu araçlarını keşfedin">
                <img src="resim/arac/isuzu.png" alt="isuzu" loading="lazy">
            </a>
            <a href="./?query=	scania	" class="banner-item" title="scania araçlarını keşfedin">
                <img src="resim/arac/scania.png" alt="scania" loading="lazy">
            </a>
            <a href="./?query=	AĞIRVASITA+MAN	" class="banner-item" title="man araçlarını keşfedin">
                <img src="resim/arac/man.png" alt="man" loading="lazy">
            </a>
            <a href="./?query=	suzuki	" class="banner-item" title="suzuki araçlarını keşfedin">
                <img src="resim/arac/suzuki.png" alt="suzuki" loading="lazy">
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

</script>



<!------- --------->

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker
        .register('/siparis/service-worker.js', { scope: '/siparis/' })
        .then(() => console.log('Sipariş dizini için Service Worker kaydedildi.'))
        .catch(err => console.error('Service Worker kaydı başarısız:', err));
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
    xhr.open('GET', `getProducts.php?query=${encodeURIComponent(query)}&durum=${encodeURIComponent(durum)}&kampanya=${encodeURIComponent(kampanya)}&page=${page}`, true);
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
