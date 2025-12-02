self.addEventListener('install', event => {
    console.log('Sipariş Service Worker kuruldu, cache kullanılmıyor.');
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    console.log('Sipariş Service Worker aktif, cache kullanılmıyor.');
    clients.claim();
});

self.addEventListener('fetch', event => {
    event.respondWith(fetch(event.request, { cache: 'no-store' }));
});
