// Caches only the built JS/CSS (content-hashed file names) and the icon, never
// pages or personal data. Bump the version on each release: the browser then
// installs this worker again and deletes the previous release's cached files.
const CACHE_NAME = 'resident-assets-v2';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    const isCacheableAsset =
        request.method === 'GET' &&
        url.origin === self.location.origin &&
        (url.pathname.startsWith('/build/') || url.pathname === '/icon.svg');

    if (!isCacheableAsset) {
        return;
    }

    event.respondWith(
        caches.open(CACHE_NAME).then(async (cache) => {
            const cached = await cache.match(request);
            if (cached) {
                return cached;
            }

            const response = await fetch(request);
            if (response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
    );
});
