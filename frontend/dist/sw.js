
const CACHE_NAME = 'momo-tech-cache-v1';
const urlsToCache = [
  '/',
  '/index.html',
  '/manifest.json',
  '/favicon.svg',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  // Add other static assets that should be cached (e.g., JS/CSS bundles if names are static)
  // Vite generates hashed filenames, so caching them directly here is tricky.
  // We'll rely on the browser cache for those, and the service worker for the shell.
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache.map(url => new Request(url, { cache: 'reload' })));
      })
      .catch(err => {
        console.error('Failed to open cache or add URLs:', err);
      })
  );
  self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
  // For navigation requests, try network first, then cache (NetworkFirst strategy)
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Check if we received a valid response
          if (!response || response.status !== 200 || response.type !== 'basic') {
            // If network fails or returns an error, try to serve from cache
            return caches.match(event.request)
              .then(cachedResponse => {
                return cachedResponse || caches.match('/index.html'); // Fallback to index.html
              });
          }
          // Optional: Cache the successful navigation response if needed
          // const responseToCache = response.clone();
          // caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseToCache));
          return response;
        })
        .catch(() => {
          // Network request failed, try to serve from cache
          return caches.match(event.request)
            .then(cachedResponse => {
              return cachedResponse || caches.match('/index.html'); // Fallback to index.html
            });
        })
    );
    return;
  }

  // For other requests (assets like CSS, JS, images), use CacheFirst strategy
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        if (response) {
          return response; // Serve from cache
        }
        // Not in cache, fetch from network
        return fetch(event.request).then(
          (networkResponse) => {
            // Optional: Cache the fetched resource
            // if (networkResponse && networkResponse.status === 200) {
            //   const responseToCache = networkResponse.clone();
            //   caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseToCache));
            // }
            return networkResponse;
          }
        ).catch(error => {
          console.error('Fetching failed:', error);
          // You could return a fallback asset here if appropriate
        });
      })
  );
});

self.addEventListener('activate', (event) => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});
