document.addEventListener('DOMContentLoaded', function() {
    fetchOrders();

    function fetchOrders() {
        fetch('orders.php')
            .then(response => response.json())
            .then(data => displayOrders(data))
            .catch(error => console.error('Siparişleri getirirken hata oluştu:', error));
    }

function displayOrders(orders) {
    const container = document.getElementById('orders-list');
    let html = `
        <div class="orders-container">
            ${orders.map(order => {
                const createdAt = new Date(order.created_at);
                const formattedDate = createdAt.toLocaleDateString('tr-TR');
                const formattedTime = createdAt.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
                return `
                    <div class="order-card">
                        <div class="order-info">
                            <span><strong>ID:</strong> ${order.id}</span>
                            <span><strong>Müşteri Kodu:</strong> ${order.user_id}</span>
                            <span><strong>Ad Soyad:</strong> ${order.fullname}</span>
                            <span><strong>Toplam Fiyat:</strong> ${order.total_price}</span>
                            <span><strong>Tarih:</strong> ${formattedDate} ${formattedTime}</span>
                        </div>
                        <div class="order-actions">
                            <button onclick="editOrder(${order.id})">Detay</button>
                            <button onclick="deleteOrder(${order.id})" class="delete-btn">Sil</button>
                        </div>
                    </div>`;
            }).join('')}
        </div>`;
    container.innerHTML = html;
}


    window.editOrder = function(orderId) {
        fetch(`order_details.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.order) {
                    document.getElementById('edit-order-id').value = data.order.id;
                    document.getElementById('edit-user_id').value = data.order.user_id;
                    document.getElementById('edit-fullname').value = data.order.fullname;
                    document.getElementById('edit-phone').value = data.order.phone;
                    document.getElementById('edit-email').value = data.order.email;
                    document.getElementById('edit-address').value = data.order.address;
                    document.getElementById('edit-message').value = data.order.message;
                    document.getElementById('edit-payment-type').value = data.order.payment_type;

                    const itemsContainer = document.getElementById('edit-items-container');
                    itemsContainer.innerHTML = data.items.map(item => `
                        <div class="item-row">
                            <input type="text" value="${item.item_name}" class="item-name" readonly />
                            <input type="number" value="${item.item_count}" class="item-count" />
                            <input type="number" value="${item.item_price}" class="item-price" />
                        </div>`).join('');
                    updateTotalPrice();

                    itemsContainer.addEventListener('input', updateTotalPrice);

                    document.getElementById('orders-container').style.display = 'none';
                    document.getElementById('edit-order-container').style.display = 'block';
                }
            })
            .catch(error => console.error('Sipariş düzenleme detayları getirilirken hata oluştu:', error));
    };

    function updateTotalPrice() {
        const itemRows = document.querySelectorAll('.item-row');
        let totalPrice = Array.from(itemRows).reduce((sum, row) => {
            const count = parseFloat(row.querySelector('.item-count').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            return sum + count * price;
        }, 0);
        document.getElementById('edit-total-price').value = totalPrice.toFixed(2);
    }

    window.saveOrderChanges = function() {
        const orderId = document.getElementById('edit-order-id').value;
        const fullname = document.getElementById('edit-fullname').value;
        const phone = document.getElementById('edit-phone').value;
        const email = document.getElementById('edit-email').value;
        const address = document.getElementById('edit-address').value;
        const message = document.getElementById('edit-message').value;
        const paymentType = document.getElementById('edit-payment-type').value;
        const totalPrice = document.getElementById('edit-total-price').value;

        fetch('update_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: orderId,
                fullname: fullname,
                phone: phone,
                email: email,
                address: address,
                message: message,
                paymentType: paymentType,
                totalPrice: totalPrice
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sipariş başarıyla güncellendi.');
                fetchOrders();
                document.getElementById('edit-order-container').style.display = 'none';
                document.getElementById('orders-container').style.display = 'block';
            } else {
                alert('Sipariş güncellenirken hata oluştu.');
            }
        })
        .catch(error => console.error('Sipariş güncellenirken hata oluştu:', error));
    };

    window.deleteOrder = function(orderId) {
        if (confirm('Bu siparişi silmek istediğinize emin misiniz?')) {
            fetch('delete_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: orderId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Sipariş başarıyla silindi.');
                    fetchOrders();
                } else {
                    alert('Sipariş silinirken hata oluştu.');
                }
            })
            .catch(error => console.error('Sipariş silinirken hata oluştu:', error));
        }
    };

    window.goBack = function() {
        document.getElementById('edit-order-container').style.display = 'none';
        document.getElementById('orders-container').style.display = 'block';
    };
});


document.addEventListener('DOMContentLoaded', () => {
    // Kopyala butonuna tıklama olayını dinleyin
    document.querySelectorAll('.copy-button').forEach(button => {
        button.addEventListener('click', () => {
            copyAllItems();
        });
    });
});

function copyAllItems() {
 // Tüm item-row öğelerini al
    const itemRows = document.querySelectorAll('#edit-items-container .item-row');

    // Kopyalanacak veriyi toplamak için bir dizi oluştur
    let copyText = '';

    itemRows.forEach(row => {
        // Her bir satırın item-name, item-count ve item-price değerlerini al
        const itemName = row.querySelector('.item-name').value;
        const itemCount = row.querySelector('.item-count').value;
         const itemPrice = parseFloat(row.querySelector('.item-price').value); // Değeri sayıya çevir

        // Veriyi diziye ekle
        copyText += `${itemName}\t${itemCount}\t${itemPrice}\n`; // Tab ile ayırarak her satır için yeni bir satır ekle
    });

    // Geçici bir input oluşturup veriyi içine yaz
    const tempInput = document.createElement('textarea');
    tempInput.value = copyText;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy'); // Veriyi panoya kopyala
    document.body.removeChild(tempInput);

    // Kullanıcıya bilgi ver
    alert('Verileri kopyalandı!');
}


document.addEventListener('DOMContentLoaded', (event) => {
    fetchOnlineUsers();
    setInterval(fetchOnlineUsers, 60000); // Her dakika güncelle
});
// Çevrimiçi kullanıcıları al ve tabloya ekle
function fetchOnlineUsers() {
    fetch('get_online_users.php') // PHP dosyanızın yolu
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('online-users-list');
            tableBody.innerHTML = ''; // Mevcut tabloyu temizle

            // Kullanıcıları tarihe göre sıralama
            data.sort((a, b) => new Date(b.lastOnline) - new Date(a.lastOnline));

            data.forEach(user => {
                const row = document.createElement('tr');
                const usernameCell = document.createElement('td');
                const cariCell = document.createElement('td');
                const lastOnlineCell = document.createElement('td');
                const rolecell = document.createElement('td');

                usernameCell.textContent = user.username;
                cariCell.textContent = user.cari;
                lastOnlineCell.textContent = user.lastOnline;
                rolecell.textContent = user.role;

                row.appendChild(usernameCell);
                row.appendChild(cariCell);
                row.appendChild(lastOnlineCell);
                row.appendChild(rolecell);
                tableBody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Çevrimiçi kullanıcıları alırken hata:', error);
        });
}

// Sayfa yüklendiğinde çevrimiçi kullanıcıları çek
document.addEventListener('DOMContentLoaded', fetchOnlineUsers);



