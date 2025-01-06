self.addEventListener('install', (event) => {
    console.log('Service Worker installé.');
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request).catch(() => {
            return new Response("App hors ligne. Connectez-vous au réseau.");
        })
    );
});
