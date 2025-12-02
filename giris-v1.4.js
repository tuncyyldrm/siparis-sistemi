// Popup açma ve kapama fonksiyonları
function openPopup() {
    document.getElementById('login-popup').style.display = 'flex';
}

function closePopup() {
    document.getElementById('login-popup').style.display = 'none';
}


function checkLoginStatus() {
    const user = getCookie('user');
    if (user) {
        const userData = JSON.parse(user);

        document.getElementById('login-form').style.display = 'none';
        document.getElementById('login-popup').style.display = 'none';
        document.getElementById('loginButton').style.display = 'none';
        document.getElementById('user-info').style.display = 'block';
        document.getElementById('displayName').value = userData.displayName || '';
        document.getElementById('email').value = userData.email || '';
        document.getElementById('phoneNumber').value = userData.phoneNumber || '';
        document.getElementById('address').value = userData.address || '';
        document.getElementById('cari').value = userData.cari || '';
        document.getElementById('discountRate').value = userData.discountRate || '';
    } else {
        document.getElementById('user-info').style.display = 'none';
        document.getElementById('login-form').style.display = 'block';
        document.getElementById('loginButton').style.display = 'block';
    }
}

// Çerez ayarlama fonksiyonu
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "expires=" + date.toUTCString(); // UTC formatında ayarlayın
    }
    document.cookie = name + "=" + encodeURIComponent(value) + ";" + expires + ";path=/";
}


// Çerez alma fonksiyonu
function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i].trim();
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length));
    }
    return null;
}

// Giriş yapma işlevi
function login() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('remember-me').checked;

    fetch('login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            'username': username,
            'password': password
        })
    })
    .then(response => response.text())
    .then(text => {
        console.log('Sunucudan dönen yanıt:', text);
        try {
            const data = JSON.parse(text); // Yanıtı JSON olarak işleyin
            if (data.error) {
                alert(data.error);
            } else {
                // Beni Hatırla seçiliyse kullanıcı adı ve şifreyi çerezde sakla
                if (rememberMe) {
                    setCookie('savedUsername', username, 90);
                    setCookie('savedPassword', password, 90);
                } else {
                    setCookie('savedUsername', '', -1);
                    setCookie('savedPassword', '', -1);
                }
                setCookie('user', JSON.stringify(data), 1); // Kullanıcı verisini çerezde sakla (1 gün)
                window.location.reload(); // Sayfayı yeniden yükle
            }
        } catch (e) {
            console.error('Yanıt işlenirken hata:', e);
            alert('Giriş sırasında bir hata oluştu. Lütfen tekrar deneyin.');
        }
    })
    .catch(error => {
        console.error('Giriş hatası:', error);
        alert('Giriş sırasında bir hata oluştu. Lütfen tekrar deneyin.');
    });
}


// Çıkış fonksiyonu
function logout() {
    fetch('logout.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    })
    .then(response => {
        if (response.ok) {
            setCookie('user', '', -1); // Çerezi sil
            window.location.reload(); // Sayfayı yeniden yükle
        } else {
            console.error('Çıkış işlemi başarısız.');
        }
    })
    .catch(error => console.error('Bir hata oluştu:', error));
}

// Sayfa yüklendiğinde giriş durumunu kontrol et
document.addEventListener('DOMContentLoaded', checkLoginStatus);

function checkSessionStatus() {
    fetch('session_check.php')
        .then(response => response.json())
        .then(data => {
            if (!data.loggedIn) {
                alert('Oturum süreniz dolmuştur. Lütfen giriş yapın.');
                window.location.reload(); // Sayfayı yeniden yükle
            }
        })
        .catch(error => console.error('Hata:', error));
}

// Modal pencere kapama
var modal = document.getElementById('id01');

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Şifre gösterme/gizleme işlevi
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById("show-password").addEventListener("change", function () {
        var passwordInput = document.getElementById("password");
        if (this.checked) {
            passwordInput.type = "text";
        } else {
            passwordInput.type = "password";
        }
    });

    // Sayfa yüklendiğinde müşteri kodunu ve şifreyi çerezden al
    const savedUsername = getCookie('savedUsername');
    const savedPassword = getCookie('savedPassword');
    if (savedUsername !== "" && savedPassword !== "") {
        document.getElementById('username').value = savedUsername;
        document.getElementById('password').value = savedPassword;
        document.getElementById('remember-me').checked = true;
    }
});

// Çerezden kullanıcı verisini al
document.addEventListener('DOMContentLoaded', function() {
    var userCookie = getCookie('user');
    if (userCookie) {
        var user;
        try {
            user = JSON.parse(userCookie);
        } catch (e) {
            console.error('Çerez verisi JSON formatında değil:', e);
        }

        if (user) {
            var bodyElement = document.getElementById('yonetici');
            if (bodyElement) {
                // Rol kontrolü yapın ve gerekli linki oluşturun
                if (user.cari === 'PLASİYER') {
                    bodyElement.innerHTML = '<p>Yönetici Sayfasına erişim için <a href="admin" style="color:red;">tıklayın</a>.</p>';
                } else {
                    bodyElement.innerHTML = '<p><span style="color:red;">Hoşgeldiniz</span></p>';
                }
            } else {
                console.log("yonetici öğesi bulunamadı.");
            }
        } else {
            console.log("Kullanıcı verisi JSON formatında değil.");
        }
    } else {
        console.log("Kullanıcı verisi bulunamadı.");
    }
});