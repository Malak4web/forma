// FORMA service worker — app-shell caching so the installed app opens instantly.
// API calls are never cached (auth, payments, progress must always be live).
const VERSION = 'forma-v1'
const SHELL = ['/', '/manifest.webmanifest', '/icons/icon-192.png', '/img/logo-icon.webp']

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(VERSION).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()))
})

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
      .then(() => self.clients.claim()),
  )
})

self.addEventListener('fetch', (e) => {
  const req = e.request
  const url = new URL(req.url)
  if (req.method !== 'GET' || url.origin !== location.origin || url.pathname.startsWith('/api/')) return

  // pages: network first, fall back to the cached shell when offline
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req).then((res) => { caches.open(VERSION).then((c) => c.put('/', res.clone())); return res })
        .catch(() => caches.match('/')),
    )
    return
  }

  // hashed build assets, images, fonts: cache first
  if (/^\/(assets|img|icons)\//.test(url.pathname)) {
    e.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        if (res.ok) caches.open(VERSION).then((c) => c.put(req, res.clone()))
        return res
      })),
    )
  }
})
