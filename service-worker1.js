self.addEventListener('install', event => {
    console.log('Yiğit Sipariş Service Worker: Kurulum tamamlandı.');
    event.waitUntil(
        caches.open('siparis-cache').then(cache => {
            return cache.addAll([
                '/siparis/index.php',
                '/siparis/styles-v1.1.css',
                '/siparis/scripts-v1.1.js',
                '/siparis/icons/kontaksiparisicon-192x192.png',
                '/siparis/icons/kontaksiparisicon-512x512.png'
            ]);
        })
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});
