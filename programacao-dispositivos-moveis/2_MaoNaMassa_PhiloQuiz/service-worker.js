const CACHE_NAME = 'philoquest-v4'; // Força o navegador a renovar o cache

const assets = [
  './',
  './index.html',
  './manifest.json',
  './css/estilo.css',
  './imagens/icone-192.png',
  './imagens/icone-512.png',
  './js/app.js',
  './js/api.js',
  './js/quiz.js',
  './js/routes.js',
  './js/storage.js',
  './data/faceis.json',
  './data/medias.json',
  './data/dificeis.json',
  './data/ranking.json'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(assets))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.map(key => {
        if (key !== CACHE_NAME) return caches.delete(key);
      })
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  if (!event.request.url.startsWith(self.location.origin)) return;
  event.respondWith(
    caches.match(event.request).then(cachedResponse => cachedResponse || fetch(event.request))
  );
});