import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

// Use a fallback key so Echo doesn't throw on pages where Reverb isn't active.
// Real-time features simply won't connect until a proper key is configured.
const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY ?? 'chatroom-key',
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
})

export default echo
