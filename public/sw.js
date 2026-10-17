/*
 * Taxi KALUZ Conductor - Service Worker
 * Estrategia: cachea el shell/estáticos; las llamadas a la API van SIEMPRE a la red
 * (el radar necesita datos frescos: polling cada 3 s).
 */
const CACHE_SHELL = 'kaluz-conductor-shell-v1';

const SHELL_URLS = [
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_SHELL).then((cache) => cache.addAll(SHELL_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_SHELL).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // API del radar y acciones: siempre red, nunca caché.
    if (url.pathname.startsWith('/conductor/api/')) {
        event.respondWith(fetch(request));
        return;
    }

    // Navegación: red primero, con fallback al caché (offline básico).
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/conductor/radar'))
        );
        return;
    }

    // Estáticos propios: cache-first.
    if (url.origin === self.location.origin) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response && response.status === 200 && response.type === 'basic') {
                    const copy = response.clone();
                    caches.open(CACHE_SHELL).then((cache) => cache.put(request, copy));
                }
                return response;
            }))
        );
    }
});
