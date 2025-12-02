<?php session_start(); ?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canlı Destek Sohbeti</title>
<style>
body { font-family:'Segoe UI', Tahoma, sans-serif; margin:0; padding:0; background:#efeae2; }
h1 { text-align:center; padding:15px 0; color:#333; font-size:18px; }

#container { display:flex; flex-direction:column; gap:10px; max-width:100%; margin:auto; padding:10px; }
#userList { width:100%; background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); height:200px; overflow-y:auto; }
#chatbox-container { width:100%; display:flex; flex-direction:column; height:calc(100vh - 300px); }
#chatbox { flex:1; background:#ece5dd; border-radius:10px; padding:15px; overflow-y:auto; display:flex; flex-direction:column; }

.message-bubble { max-width:70%; padding:10px 14px; margin:6px 0; border-radius:18px; word-wrap:break-word; position:relative; font-size:14px; display:flex; flex-direction:column; box-shadow:0 1px 3px rgba(0,0,0,0.1);}
.user-message { background:#fff; align-self:flex-start; border-bottom-left-radius:5px; }
.admin-message { background:#dcf8c6; align-self:flex-end; border-bottom-right-radius:5px; }
.message-time { font-size:11px; color:gray; margin-top:4px; text-align:right; }

.message-bubble img { max-width:250px; max-height:200px; object-fit:cover; border-radius:12px; margin-top:6px; cursor:pointer; transition:0.2s; }
.message-bubble img:hover { transform:scale(1.05); box-shadow:0 8px 20px rgba(0,0,0,0.3); }

.img-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); align-items:center; justify-content:center; z-index:9999; }
.img-overlay img { max-width:90vw; max-height:90vh; border-radius:12px; }

#inputArea { display:flex; align-items:center; gap:8px; margin-top:5px; padding:6px 8px; background:#f0f0f0; border-radius:30px; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
#messageInput { flex:1; padding:10px 15px; border-radius:20px; border:1px solid #ccc; font-size:14px; outline:none; }
#messageInput:focus { border-color:#4CAF50; box-shadow:0 0 5px rgba(76,175,80,0.3); }
#sendButton { padding:10px 15px; border:none; background:#4CAF50; color:#fff; border-radius:50%; cursor:pointer; font-weight:bold; width:40px; height:40px; display:flex; align-items:center; justify-content:center; transition:0.2s; }
#sendButton:hover { background:#45a049; transform:scale(1.1); }

#fileInput { display:none; }
#fileLabel { width:35px; height:35px; background:#e0e0e0; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:0.2s; }
#fileLabel:hover { background:#d0d0d0; }

.user-item { padding:10px 15px; border-bottom:1px solid #eee; cursor:pointer; display:flex; justify-content:space-between; align-items:center; border-radius:6px; margin:4px 0; }
.user-item.active { background:#e0f7fa; font-weight:bold; }
.unread-count { background:#ff5252; color:#fff; font-size:12px; padding:2px 6px; border-radius:12px; }

#chatbox::-webkit-scrollbar, #userList::-webkit-scrollbar { width:6px; }
#chatbox::-webkit-scrollbar-thumb, #userList::-webkit-scrollbar-thumb { background:#bbb; border-radius:4px; }
#chatbox::-webkit-scrollbar-track, #userList::-webkit-scrollbar-track { background:#f5f5f5; }

@media(min-width:769px){ #container { flex-direction:row; gap:15px; max-width:1200px; padding:15px; } #userList { width:25%; height:500px; } #chatbox-container { width:73%; height:500px; } }
</style>
</head>
<body>
<h1>Canlı Destek Sohbeti</h1>
<div id="container">
  <div id="userList" style="display:none;"></div>
  <div id="chatbox-container">
    <div id="chatbox" aria-live="polite"></div>
    <div id="inputArea">
      <label for="fileInput" id="fileLabel">📎</label>
      <input type="file" id="fileInput">
      <input type="text" id="messageInput" placeholder="Mesajınızı yazın...">
      <button id="sendButton">➤</button>
    </div>
  </div>
</div>

<div id="imgOverlay" class="img-overlay" onclick="this.style.display='none'"><img src=""></div>
<div id="error-message" style="text-align:center; color:red;"></div>

<script>

const userSession = <?php echo json_encode($_SESSION['user']); ?>;
const isAdmin = userSession.cari === 'PLASİYER';
let selectedUserId = isAdmin ? null : 'ADMIN';
const chatbox = document.getElementById('chatbox');
const userListDiv = document.getElementById('userList');
const messageInput = document.getElementById('messageInput');
const sendButton = document.getElementById('sendButton');
const errorMessage = document.getElementById('error-message');
let lastMessageId = 0;
let firstMessageId = 0; // eski mesaj yüklemek için
const MESSAGE_LIMIT = 30;

// Admin ise kullanıcı listesini göster
if (isAdmin) userListDiv.style.display = 'block', loadUsers();

// Enter tuşu ile mesaj gönderme
sendButton.addEventListener('click', sendMessage);
messageInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// URL algılama
function linkify(text){
    return text.replace(/(https?:\/\/[^\s]+)/g, url => `<a href="${url}" target="_blank">${url}</a>`);
}

const fileInput = document.getElementById('fileInput');

// Mesaj gönderme
function sendMessage(){
    const message = messageInput.value.trim();
    const file = fileInput.files[0];
    if (!message && !file) { errorMessage.textContent = "Mesaj veya resim gerekli!"; return; }

    sendButton.disabled = true;
    const formData = new FormData();
    formData.append('message', message);
    formData.append('receiver_id', isAdmin ? selectedUserId : 'ADMIN');
    if(file) formData.append('file', file);

    fetch('send_message.php', { method:'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            sendButton.disabled = false;
            if(data.success){
                addMessage({id: data.message_id, message, file_url: data.file_url ?? null, role: isAdmin ? 'admin' : 'user', created_at: data.created_at}, true);
                messageInput.value = '';
                fileInput.value = '';
            } else errorMessage.textContent = data.message;
        })
        .catch(()=>{ errorMessage.textContent = 'Mesaj gönderilirken hata oluştu!'; sendButton.disabled = false; });
}

// Mesaj ekleme
const imgOverlay = document.getElementById('imgOverlay');
const overlayImg = imgOverlay.querySelector('img');

// Mesaja eklenen resimlere tıklayınca overlay aç
function addMessage(msg, scrollToBottom = false, prepend = false){
    if(msg.id > lastMessageId) lastMessageId = msg.id;
    if(firstMessageId === 0 || msg.id < firstMessageId) firstMessageId = msg.id;

    const div = document.createElement('div');
    div.classList.add('message-bubble', msg.role==='admin'?'admin-message':'user-message');

    if(msg.message){
        const span = document.createElement('span');
        span.innerHTML = linkify(msg.message);
        div.appendChild(span);
    }

    if(msg.file_url){
        const img = document.createElement('img');
        img.src = msg.file_url;
        img.onload = () => { if(scrollToBottom) chatbox.scrollTop = chatbox.scrollHeight; };
        img.onclick = () => { overlayImg.src = msg.file_url; imgOverlay.style.display = 'flex'; };
        div.appendChild(img);
    }

    const timeSpan = document.createElement('div');
    timeSpan.classList.add('message-time');

    const msgDate = new Date(msg.created_at);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    let timeText = msgDate.toLocaleTimeString('tr-TR', { hour:'2-digit', minute:'2-digit' });

    if (msgDate.toDateString() === today.toDateString()) {
        timeSpan.textContent = timeText; // Bugün
    } else if (msgDate.toDateString() === yesterday.toDateString()) {
        timeSpan.textContent = `Dün ${timeText}`; // Dün
    } else {
        timeSpan.textContent = msgDate.toLocaleString('tr-TR', {
            day:'2-digit', month:'2-digit', year:'numeric',
            hour:'2-digit', minute:'2-digit'
        });
    }

    div.appendChild(timeSpan);

    if(prepend){
        const prevScrollHeight = chatbox.scrollHeight;
        chatbox.prepend(div);
        // Scroll pozisyonunu koru
        chatbox.scrollTop = chatbox.scrollHeight - prevScrollHeight;
    } else {
        chatbox.appendChild(div);
        if(scrollToBottom) chatbox.scrollTop = chatbox.scrollHeight;
    }
}

// Overlay kapatma
imgOverlay.addEventListener('click', () => {
    imgOverlay.style.display = 'none';
    overlayImg.src = '';
});

// Mesajları yükleme
function loadMessages(after=true){
    if(selectedUserId === null) return;
    let url = 'get_messages.php' + (isAdmin ? `?user_id=${selectedUserId}` : '');
    if(after){
        url += `${url.includes('?') ? '&' : '?'}after_id=${lastMessageId}`;
    } else {
        url += `${url.includes('?') ? '&' : '?'}before_id=${firstMessageId}&limit=${MESSAGE_LIMIT}`;
    }

    fetch(url)
        .then(res=>res.json())
        .then(msgs=>{
            if(msgs.length === 0) return;
            msgs.forEach(m => addMessage(m, after, !after)); // eski mesaj prepend, yeni mesaj append
        })
        .catch(()=>{});
}

// Kullanıcı listesini yükleme (admin)
function loadUsers(){
    fetch('get_users.php').then(res=>res.json()).then(users=>{
        if(users.length === 0){ userListDiv.innerHTML='<div style="padding:10px;">Mesajlaşmış kullanıcı yok.</div>'; return; }
        users.sort((a,b)=>b.unread_count-a.unread_count);
        userListDiv.innerHTML='';
        if(selectedUserId===null) selectedUserId = users[0].cari;

        users.forEach(u=>{
            const div = document.createElement('div');
            div.classList.add('user-item');
            div.dataset.id = u.cari;
            if(u.cari===selectedUserId) div.classList.add('active');

            const nameWrapper = document.createElement('div');
            nameWrapper.style.display='flex';
            nameWrapper.style.flexDirection='column';

            const nameSpan = document.createElement('span'); nameSpan.textContent=u.username; nameWrapper.appendChild(nameSpan);
            const idSpan = document.createElement('span'); idSpan.textContent=`ID: ${u.cari}`; idSpan.style.fontSize='11px'; idSpan.style.color='#666'; nameWrapper.appendChild(idSpan);
            div.appendChild(nameWrapper);

            if(u.unread_count>0){ const countSpan = document.createElement('span'); countSpan.classList.add('unread-count'); countSpan.textContent=u.unread_count; div.appendChild(countSpan); }

            div.onclick=()=>{
                selectedUserId=u.cari;
                document.querySelectorAll('.user-item').forEach(el=>el.classList.remove('active'));
                div.classList.add('active');
                lastMessageId=0;
                firstMessageId=0;
                chatbox.innerHTML='';
                loadMessages(true);
            }

            userListDiv.appendChild(div);
        });
    });
}

// Polling: yeni mesajları kontrol et
setInterval(()=>{ loadMessages(true); if(isAdmin) loadUsers(); }, 2000);

// Sayfa açıldığında son mesajı getir
window.addEventListener('load', ()=>{ setTimeout(()=>loadMessages(true),100); });

// Scroll up ile eski mesajları yükleme
chatbox.addEventListener('scroll', ()=>{
    if(chatbox.scrollTop === 0 && firstMessageId > 1){
        loadMessages(false);
    }
});

//=========================================================================//

</script>

</body>
</html>
