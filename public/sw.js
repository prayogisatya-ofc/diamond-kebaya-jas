const CACHE_VERSION = 'diamond-pwa-v4'
const STATIC_CACHE = `${CACHE_VERSION}-static`
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`

const PRECACHE_URLS = [
    '/manifest.webmanifest',
    '/pwaicon.svg',
    '/favicon.ico',
]

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting()),
    )
})

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => Promise.all(
                cacheNames
                    .filter((cacheName) => ![STATIC_CACHE, RUNTIME_CACHE].includes(cacheName))
                    .map((cacheName) => caches.delete(cacheName)),
            ))
            .then(() => self.clients.claim()),
    )
})

self.addEventListener('fetch', (event) => {
    const { request } = event
    const url = new URL(request.url)

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request))
        return
    }

    if (isStaticAsset(request, url)) {
        event.respondWith(staleWhileRevalidate(request))
    }
})

function isStaticAsset(request, url) {
    return url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/storage/')
        || url.pathname === '/manifest.webmanifest'
        || url.pathname === '/pwaicon.svg'
        || request.destination === 'font'
        || request.destination === 'image'
        || request.destination === 'script'
        || request.destination === 'style'
}

async function networkFirst(request) {
    const cache = await caches.open(RUNTIME_CACHE)

    try {
        const response = await fetch(request)

        if (response.ok && isCacheableResponse(response)) {
            cache.put(request, response.clone())
        }

        return response
    } catch (error) {
        const cachedResponse = await cache.match(request)

        return cachedResponse || new Response('Aplikasi sedang offline. Sambungkan internet untuk membuka data terbaru.', {
            headers: {
                'Content-Type': 'text/plain; charset=utf-8',
            },
            status: 503,
        })
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(RUNTIME_CACHE)
    const cachedResponse = await cache.match(request)

    const networkResponse = fetch(request)
        .then((response) => {
            if (response.ok && isCacheableResponse(response)) {
                cache.put(request, response.clone())
            }

            return response
        })
        .catch(() => null)

    if (cachedResponse) {
        return cachedResponse
    }

    return await networkResponse || fetch(request)
}

function isCacheableResponse(response) {
    return !response.headers.get('Cache-Control')?.includes('no-store')
}
