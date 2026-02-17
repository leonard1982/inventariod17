const CACHE_VERSION = 'inventario-d17-v2';
const CACHE_ASSETS = [
  './',
  './index.php',
  './css/index.css?v=20260217_03',
  './js/index.js?v=20260217_03',
  './imagenes/pwa-icon-192.png',
  './imagenes/pwa-icon-512.png',
  './imagenes/favicon_gestion.svg'
];

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_VERSION).then(function(cache) {
      return cache.addAll(CACHE_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(names) {
      return Promise.all(
        names.map(function(name) {
          if (name !== CACHE_VERSION) {
            return caches.delete(name);
          }
          return Promise.resolve();
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', function(event) {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(request)
      .then(function(response) {
        const clone = response.clone();
        caches.open(CACHE_VERSION).then(function(cache) {
          cache.put(request, clone).catch(function() {});
        });
        return response;
      })
      .catch(function() {
        return caches.match(request).then(function(cached) {
          if (cached) {
            return cached;
          }
          if (request.mode === 'navigate') {
            return caches.match('./index.php');
          }
          return new Response('', { status: 504, statusText: 'Offline' });
        });
      })
  );
});
