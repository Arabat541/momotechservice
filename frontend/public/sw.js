const CACHE_NAME = 'momo-tech-cache-v3';
const urlsToCache = [
  '/',
  '/favicon.svg',
  '/manifest.json',
  '/icons/icon-144x144.png',
  '/offline.html',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(async (cache) => {
        try {
          await cache.addAll(urlsToCache.map(url => new Request(url, { cache: 'reload' })));
        } catch (err) {
          console.error('Failed to open cache or add URLs:', err);
        }
      })
  );
  self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  // Navigation requests: Network First, fallback to cache, then offline.html
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return caches.match(event.request)
              .then(cachedResponse => cachedResponse || caches.match('/offline.html'));
          }
          return response;
        })
        .catch(() => {
          return caches.match(event.request)
            .then(cachedResponse => cachedResponse || caches.match('/offline.html'));
        })
    );
    return;
  }

  // Assets: Cache First, fallback to network, fallback offline
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        if (response) return response;
        return fetch(event.request)
          .catch(() => caches.match('/offline.html'));
      })
  );
});

self.addEventListener('activate', (event) => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (!cacheWhitelist.includes(cacheName)) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});
