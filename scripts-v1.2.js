// 1244
///////////////////////////////////////////
document.addEventListener('DOMContentLoaded', () => {
    // Ödeme türü radyo düğmeleri için dinleyiciler
    const paymentTypeRadios = document.querySelectorAll('input[name="paymentType"]');
    paymentTypeRadios.forEach(radio => {
        radio.addEventListener('change', updateTotalPrice);
    });

    const userLoggedIn = isLoggedIn();
    
    if (userLoggedIn) {
        loadCartFromServer()
            .then(serverCart => {
                let localCart = JSON.parse(localStorage.getItem('cart')) || [];
                
                if (localCart.length === 0 && serverCart.length > 0) {
                    // LocalStorage'da sepet boşsa ve sunucudan gelen sepet doluysa, sunucudan gelen sepeti kullan
                    saveCartToLocalStorage(serverCart);
                } else if (localCart.length > 0) {
                    // LocalStorage'da sepet varsa, iki sepeti birleştir
                    localCart = mergeCarts(localCart, serverCart);
                    saveCartToLocalStorage(localCart);
                    saveCartToServer(); // Güncellenmiş sepeti sunucuya kaydet
                }
                
                updateCartUI();
            })
            .catch(error => {
                console.error('Sunucudan sepet yüklenirken hata oluştu:', error);
                loadCartFromLocalStorage(); // Sunucudan yüklenemezse LocalStorage'dan yükle
                updateCartUI();
            });
    } else {
        loadCartFromLocalStorage();
        updateCartUI();
    }
});


function mergeCarts(localCart, serverCart) {
    const mergedCart = [...localCart];

    serverCart.forEach(serverItem => {
        const existingItemIndex = mergedCart.findIndex(localItem => localItem.name === serverItem.name);
        
        if (existingItemIndex > -1) {
            mergedCart[existingItemIndex].quantity += serverItem.quantity;
        } else {
            mergedCart.push(serverItem);
        }
    });

    return mergedCart;
}
// Kullanıcı giriş durumunu kontrol eden fonksiyon
function isLoggedIn() {
    return getCookie('user') !== null;
}

function setLastPaymentType(paymentType) {
    localStorage.setItem('lastPaymentType', paymentType);
	saveCartToServer();
}

function getLastPaymentType() {
    return localStorage.getItem('lastPaymentType');
}


function updateCartUI() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartItemsContainer = document.getElementById('cart-items');
    const totalPriceElement = document.getElementById('total-price');

    cartItemsContainer.innerHTML = '';
    let totalPrice = 0;

    const lastPaymentType = getLastPaymentType() || 'taksit';
    document.querySelector(`input[name="paymentType"][value="${lastPaymentType}"]`).checked = true;
    const discountRate = discountRates[lastPaymentType];

    const userLoggedIn = isLoggedIn(); // Kullanıcı giriş durumunu kontrol et

    cart.forEach((item, index) => {
        const discountedPrice = item.price * (1 - discountRate);
        const itemTotalPrice = discountedPrice.toFixed(0) * item.quantity;
        totalPrice += userLoggedIn ? itemTotalPrice : 0;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td><input type="number" value="${item.quantity}" min="1" onchange="updateQuantity(${index}, this.value)" /></td>
            <td>${userLoggedIn ? discountedPrice.toFixed(0) + ' ₺' : 'Gizli'}</td>
            <td>${userLoggedIn ? itemTotalPrice.toFixed(0) + ' ₺' : 'Gizli'}</td>
            <td><button onclick="removeFromCart(${index})">Sil</button></td>
        `;
        cartItemsContainer.appendChild(row);
    });

    totalPriceElement.textContent = `Toplam Fiyat: ${userLoggedIn ? totalPrice.toFixed(0) + ' ₺' : 'Gizli'}`;
}


function updateQuantity(index, newQuantity) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    if (newQuantity > 0) {
        cart[index].quantity = parseInt(newQuantity, 10);
    } else {
        cart.splice(index, 1);
    }
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
	saveCartToServer();
}

function removeFromCart(index) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart.splice(index, 1);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
	saveCartToServer();
}

function clearCart() {
    localStorage.removeItem('cart');
    updateCartUI();
	saveCartToServer();
}

function updateTotalPrice() {
    const paymentType = document.querySelector('input[name="paymentType"]:checked').value;
    setLastPaymentType(paymentType);
    updateCartUI();
}

// Sepete ürün eklemek için fonksiyon
function addToCart(productName, productPrice, quantity) {
    if (isNaN(productPrice) || isNaN(quantity) || quantity <= 0) {
        alert('Geçersiz fiyat veya miktar.');
        return;
    }

    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const productIndex = cart.findIndex(item => item.name === productName);

    if (productIndex > -1) {
        cart[productIndex].quantity += parseInt(quantity, 10);
    } else {
        cart.push({ name: productName, price: productPrice, quantity: parseInt(quantity, 10) });
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
	saveCartToServer();
    const userLoggedIn = isLoggedIn();
    const priceDisplay = userLoggedIn ? `${productPrice} ₺` : 'Gizli';
    alert(`Ürün: ${productName}, Miktar: ${quantity} sepete eklendi!`);
}

// Function to save cart data to the server
function saveCartToServer() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const paymentType = getLastPaymentType(); // Ödeme tipini alın
    
  // Her ürünün içerisine ödeme tipini ve ekleme tarihini saati ekleyin
    const cartWithDetails = cart.map(item => ({
        ...item,
        payment_type: paymentType,
        addedAt: getTurkeyLocalTimeISO() // Tarih ve saat bilgisi
    }));

    
    fetch('save_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(cartWithDetails) // Güncellenmiş cart objesini gönderin
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Ağ yanıtı başarılı değil: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            // alert(data.error);
        } else {
            // alert('Sepet başarıyla kaydedildi.');
        }
    })
    .catch(error => {
        // console.error('Sepet kaydetme hatası:', error);
        // alert('Sepet kaydetme sırasında bir hata oluştu. Lütfen tekrar deneyin.');
    });
}


// Call saveCartToServer when needed
// saveCartToServer();

function saveCartToLocalStorage(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Diğer fonksiyonlarınız
function loadCartFromServer() {
    fetch('get_cart.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Ağ yanıtı başarılı değil: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Sunucudan dönen veri:', data);
        if (data.cart && Array.isArray(data.cart) && data.cart.length > 0) {
            // Veritabanından sepet verileri mevcut, bu verileri localStorage'a kaydedin
            saveCartToLocalStorage(data.cart);
            console.log('Veritabanından gelen sepet verileri başarıyla localStorage\'a yüklendi.');
        } else {
            console.log('Veritabanında sepet verisi bulunamadı. LocalStorage\'daki veriler kullanılacak.');
            // Veritabanında veri yoksa, localStorage'daki verileri yükleyin
            loadCartFromLocalStorage();
        }
        updateCartUI(); // Veriler yüklendikten sonra UI'yi güncelle
    })
    .catch(error => {
        console.error('Sepet yükleme hatası:', error);
    });
}

function loadCartFromLocalStorage() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    if (cart.length > 0) {
        updateCartUI();
    }
}

/////////////////////////////////////////////////////////////


function openModal() {
    document.getElementById('modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.close-btna').addEventListener('click', closeModal);
});

document.addEventListener("DOMContentLoaded", function() {
    var modal = document.getElementById("modal");
    var closeBtn = document.querySelector(".close-btna");

    var modalClosedTime = localStorage.getItem('modalClosedTime');
    var currentTime = new Date().getTime();

    if (!modalClosedTime || (currentTime - modalClosedTime) > 3 * 60 * 60 * 1000) {
        modal.style.display = "flex";

        openModal();
    }

    closeBtn.addEventListener("click", function() {
        modal.style.display = "none";
        localStorage.setItem('modalClosedTime', new Date().getTime());
        closeModal();
    });

    window.addEventListener("click", function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
            localStorage.setItem('modalClosedTime', new Date().getTime());
            closeModal();
        }
    });
});

// Mouse imlecinin değiştirilmesi
function changeCursor(element) {
    element.style.cursor = 'pointer';
}

// Mouse imlecinin geri alınması
function restoreCursor(element) {
    element.style.cursor = 'default';
}


function goToHome() {
    window.location.href = './';
}

function goToCart() {
    window.location.href = 'cart.html';
}

function openNav() {
    document.getElementById("mySidenav").style.width = "250px";
}

function closeNav() {
    document.getElementById("mySidenav").style.width = "0";
}

const discountRates = {
    taksit: 0.0,
    tekcekim: 0.10,
    nakit: 0.15
};
// Popup'ı göstermek için fonksiyon
function showPopup(productName, productPrice, productImage, kampanyaOrani = 0) {
    currentProductName = productName;
    currentProductPrice = productPrice;
    currentProductImage = productImage || 'default-image-url';

    const userLoggedIn = isLoggedIn();
    const kampanyaVarMi = kampanyaOrani > 0;

    let priceDisplay;

    if (userLoggedIn) {
        const taksit = Math.round(productPrice);
        const tekCekim = Math.round(productPrice * 0.90);
        const nakit = Math.round(productPrice * 0.85);

        if (kampanyaVarMi) {
            const kampanyaCarpan = (100 - kampanyaOrani) / 100;

            const eskiTaksit = Math.round(taksit / kampanyaCarpan);
            const eskiTek = Math.round(tekCekim / kampanyaCarpan);
            const eskiNakit = Math.round(nakit / kampanyaCarpan);

            priceDisplay = `
				<div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-start; font-size: 16px; margin-top: 8px;">

				  <div style="display: flex; flex-direction: column; align-items: center;">
					<span style="font-weight: 600; color: #333;">Taksit</span>
					<div>
					  <strong class="line-through" style="color: darkred; margin-right: 4px;">${eskiTaksit}₺</strong>
					  <strong style="color: darkgreen;">${taksit}₺</strong>
					</div>
				  </div>

				  <div style="display: flex; flex-direction: column; align-items: center;">
					<span style="font-weight: 600; color: #333;">Tek Ç.</span>
					<div>
					  <strong class="line-through" style="color: darkred; margin-right: 4px;">${eskiTek}₺</strong>
					  <strong style="color: darkgreen;">${tekCekim}₺</strong>
					</div>
				  </div>

				  <div style="display: flex; flex-direction: column; align-items: center;">
					<span style="font-weight: 600; color: #333;">Nakit</span>
					<div>
					  <strong class="line-through" style="color: darkred; margin-right: 4px;">${eskiNakit}₺</strong>
					  <strong style="color: darkgreen;">${nakit}₺</strong>
					</div>
				  </div>
				</div>
            `;
        } else {
            priceDisplay = `
                <p>Taksit: <strong>${taksit} ₺</strong>
                Tek Ç.: <strong>${tekCekim} ₺</strong>
                Nakit: <strong>${nakit} ₺</strong></p>
            `;
        }
    } else {
        priceDisplay = 'Gizli';
    }

    document.getElementById('productDetails').innerHTML = `
        <h3 class="text-2xl font-bold">${productName}</h3>
        <p><img src="${productImage}" alt="${productName}" style="max-width: 100%; height: auto;"></p>
        <div class="mt-2">${priceDisplay}</div>
        <div style="display:flex;justify-content: space-between;align-items:center;margin-top:10px;">
            <p>Adet:</p> <p><input  type="number"  id="quantityInput"  min="1"  value="1"  class="w-20 px-3 py-2 border border-gray-300 rounded-lg shadow-sm text-center text-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition"/></p>
            <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600" onclick="addToCartFromPopup()">Sepete Ekle</button>
        </div>
    `;

    document.getElementById('cartPopup').classList.add('active');
}

// Popup'ı kapatmak için fonksiyon
function closePopupsepet() {
    console.log("Popup kapanıyor"); // Debug amaçlı
    document.getElementById('cartPopup').classList.remove('active');
}
// Popup'tan sepete ürün eklemek için fonksiyon
function addToCartFromPopup() {
    const quantity = parseInt(document.getElementById('quantityInput').value, 10);
    if (quantity > 0) {
        addToCart(currentProductName, currentProductPrice, quantity);
        closePopupsepet(); // Sepete ürün ekledikten sonra popup'ı kapat
    } else {
        alert("Geçerli bir miktar girin.");
    }
}
// Function to get the current time in Turkey's time zone
function getTurkeyLocalTimeISO() {
    const date = new Date();
    const options = { timeZone: 'Europe/Istanbul', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', timeZoneName: 'short' };
    const formatter = new Intl.DateTimeFormat([], options);
    const parts = formatter.formatToParts(date);
    
    // Create a new Date object from the formatted string
    const isoString = `${parts.find(p => p.type === 'year').value}-${parts.find(p => p.type === 'month').value}-${parts.find(p => p.type === 'day').value}T${parts.find(p => p.type === 'hour').value}:${parts.find(p => p.type === 'minute').value}:${parts.find(p => p.type === 'second').value}+03:00`;
    
    return isoString;
}

function getUserId() {
    const userData = getCookie('user');
    
    if (userData) {
        try {
            const parsedData = JSON.parse(userData); // JSON verisini ayrıştır
            if (parsedData && parsedData.usersId) {
                return parsedData.usersId; // Kullanıcı ID'sini döndür
            } else {
                console.log("Kullanıcı ID'si bulunamadı.");
                return null;
            }
        } catch (e) {
            console.log("Veriyi ayrıştırırken bir hata oluştu:", e);
            return null;
        }
    } else {
        console.log("Giriş yapmış kullanıcı bilgisi bulunamadı.");
        return null;
    }
}

async function sendOrder(event) {
    event.preventDefault();

    if (!isLoggedIn()) {
        alert("Lütfen önce giriş yapın.");
        return;
    }

    const username = getUserId();
    const cari = document.getElementById("cari").value;
    const fullname = document.getElementById("displayName").value;
    const phone = document.getElementById("phoneNumber").value;
    const email = document.getElementById("email").value;
    const address = document.getElementById("address").value;
    const message = document.getElementById("message").value;

    const cartItemsRows = document.querySelectorAll("#cart-items tr");
    let cartItems = [];
    let totalPrice = 0;

    cartItemsRows.forEach(row => {
        const itemName = row.querySelector('td:nth-child(1)').textContent.trim();
        const itemCount = row.querySelector('td:nth-child(2) input').value.trim();
        const itemPriceText = row.querySelector('td:nth-child(3)').textContent.trim();
        const itemPrice = parseFloat(itemPriceText.replace(/\./g, '').replace(',', '.'));

        totalPrice += itemPrice * itemCount;
        cartItems.push({
            name: itemName,
            count: itemCount,
            price: itemPrice
        });
    });

    if (cartItems.length === 0) {
        alert("Sepetiniz boş. Lütfen ürün ekleyin.");
        return;
    }

    let selectedPaymentType = '';
    document.getElementsByName("paymentType").forEach(el => {
        if (el.checked) selectedPaymentType = el.value;
    });

    try {
        const response = await fetch('save_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username, cari, fullname, phone, email, address, message,
                paymentType: selectedPaymentType,
                cartItems, totalPrice
            })
        });

        const data = await response.json();

        if (data.error) {
            alert("Sipariş veritabanına kaydedilirken hata oluştu.");
            console.error(data.error);
            return;
        }

        // Kullanıcıya WhatsApp üzerinden de sipariş gönderilsin mi sorusu
        const confirmWhatsapp = confirm("Siparişiniz başarıyla kaydedildi.\nWhatsApp üzerinden de göndermek ister misiniz?");

		if (confirmWhatsapp) {
			let whatsappMessage =
				`*Yeni Sipariş Bilgisi*\n` +
				`─────────────────────\n` +
				`*Cari Kod:* ${username}\n` +
				`*Cari:* ${cari}\n` +
				`*Ad Soyad:* ${fullname}\n` +
				`*Ödeme Tipi:* ${selectedPaymentType}\n` +
				`*Not:* ${message || "-"}\n\n` +
				`*Sipariş Listesi:*\n`;

			cartItems.forEach((item, index) => {
				whatsappMessage += `  ${index + 1}. ${item.name} x ${item.count} adet → ${item.price.toFixed(2)}₺\n`;
			});

			whatsappMessage += `\n *Toplam Tutar:* ${totalPrice.toFixed(2)}₺\n`;
			whatsappMessage += `\n Bu mesaj otomatik olarak oluşturulmuştur.`;

			const whatsappUrl = `https://wa.me/905522463676?text=${encodeURIComponent(whatsappMessage)}`;
			window.open(whatsappUrl, '_blank');
			clearCart(); // buraya taşı
		}

        alert("Siparişiniz başarıyla alındı.");
        clearCart();

        // Yönlendirme
        window.location.href = `order-confirmation.html?username=${encodeURIComponent(username)}&cari=${encodeURIComponent(cari)}&fullname=${encodeURIComponent(fullname)}&phone=${encodeURIComponent(phone)}&email=${encodeURIComponent(email)}&address=${encodeURIComponent(address)}&message=${encodeURIComponent(message)}&paymentType=${encodeURIComponent(selectedPaymentType)}&cartItems=${encodeURIComponent(JSON.stringify(cartItems))}&totalPrice=${encodeURIComponent(totalPrice)}`;

    } catch (error) {
        console.error("Sipariş gönderme hatası:", error);
        alert("Sipariş gönderilirken teknik bir hata oluştu.");
    }
    closeForm();
}
