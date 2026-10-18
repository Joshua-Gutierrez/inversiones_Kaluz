/*
 * Taxi KALUZ Conductor - Service Worker
 * Estrategia: cachea el shell/estáticos; las llamadas a la API van SIEMPRE a la red
 * (el radar y el rastreo de pasajeros necesitan datos frescos en tiempo real).
 */
const CACHE_SHELL = 'kaluz-conductor-shell-v2';

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

    // API del radar, rastreo en vivo, estado y acciones dinámicas: SIEMPRE RED DIRECTA, NUNCA CACHÉ.
    if (
        url.pathname.startsWith('/conductor/api/') ||
        url.pathname.startsWith('/api/') ||
        url.pathname.includes('/posicion') ||
        url.pathname.includes('/estado') ||
        url.searchParams.has('_t') ||
        (request.headers.get('Accept') && request.headers.get('Accept').includes('application/json'))
    ) {
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

    // Solo estáticos propios (build, imágenes, iconos, fuentes): cache-first.
    if (
        url.origin === self.location.origin &&
        (url.pathname.startsWith('/build/') ||
         url.pathname.startsWith('/icons/') ||
         url.pathname.startsWith('/images/') ||
         url.pathname === '/manifest.json' ||
         url.pathname === '/favicon.ico')
    ) {
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
