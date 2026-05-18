// Incrementar este número cada vez que se actualice el catálogo
const CACHE = 'repuestos-jorge-v1';

const PRECACHE = [
  './index.html',
];

// Instala el service worker y precachea el HTML principal
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(c => c.addAll(PRECACHE))
  );
  self.skipWaiting();
});

// Al activar, borra versiones anteriores del caché
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  const { request } = e;

  // Imágenes de productos: caché primero, red si no está, y guarda en caché
  if (request.destination === 'image') {
    e.respondWith(
      caches.match(request).then(cached => {
        if (cached) return cached;
        return fetch(request).then(response => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE).then(c => c.put(request, clone));
          }
          return response;
        }).catch(() => cached);
      })
    );
    return;
  }

  // HTML: red primero (para recibir actualizaciones del catálogo),
  // caché como respaldo si no hay conexión
  if (request.mode === 'navigate') {
    e.respondWith(
      fetch(request)
        .then(response => {
          const clone = response.clone();
          caches.open(CACHE).then(c => c.put(request, clone));
          return response;
        })
        .catch(() => caches.match('./index.html'))
    );
    return;
  }

  // Todo lo demás (fuentes, etc.): red con respaldo en caché
  e.respondWith(
    fetch(request).catch(() => caches.match(request))
  );
});
