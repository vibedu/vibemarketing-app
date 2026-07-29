/* Vibe Marketing service worker.
   Makes the app installable (Chrome needs a SW with a fetch handler)
   and gives basic offline support. Network-first so an online user always
   gets the latest version; the cache is only a fallback when offline. */
const CACHE = 'vibe-cache-v2';

self.addEventListener('install', (e) => { self.skipWaiting(); });

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;
  e.respondWith(
    fetch(e.request)
      .then((res) => {
        try {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(e.request, copy)).catch(() => {});
        } catch (err) {}
        return res;
      })
      .catch(() => caches.match(e.request))
  );
});
