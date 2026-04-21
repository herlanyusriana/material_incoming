import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.initRealtimeEcho = () => {
    if (window.Echo) {
        return window.Echo;
    }

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? (window.location.protocol === 'https:' ? 'https' : 'http');
    const port = import.meta.env.VITE_REVERB_PORT;

    if (!key) {
        console.warn('Realtime disabled: VITE_REVERB_APP_KEY is not configured.');
        return null;
    }

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port ?? 80,
        wssPort: port ?? 443,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    return window.Echo;
};
