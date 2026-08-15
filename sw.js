self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Intercept media requests
    if (url.pathname.startsWith('/sw-media/')) {
        const realUrl = url.pathname.replace('/sw-media/', '/api/media/') + url.search;
        
        // Clone the request but add our custom anti-IDM header
        const newHeaders = new Headers(event.request.headers);
        newHeaders.set('X-Anti-IDM', 'true');
        newHeaders.set('X-Requested-With', 'XMLHttpRequest'); // Common AJAX header
        
        const modifiedRequest = new Request(realUrl, {
            method: event.request.method,
            headers: newHeaders,
            mode: 'same-origin', // Ensure it's a same-origin request
            credentials: 'same-origin' // MUST send session cookies!
        });

        // For range requests, we must pass the Range header
        if (event.request.headers.has('range')) {
            newHeaders.set('Range', event.request.headers.get('range'));
        }

        event.respondWith(
            fetch(modifiedRequest).then(response => {
                // Return the response directly to the video tag
                return response;
            }).catch(error => {
                console.error('SW Fetch Error:', error);
                return new Response('Media fetch failed', { status: 500 });
            })
        );
    }
});
