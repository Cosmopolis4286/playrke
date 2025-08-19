import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Extender el tipo global Window para TS
declare global {
    interface Window {
        Pusher: typeof Pusher
    }
}

// Asignar Pusher al window
window.Pusher = Pusher

const APP_KEY = import.meta.env.VITE_REVERB_APP_KEY
const HOST = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname
const PORT = Number(import.meta.env.VITE_REVERB_PORT) || 8080
const SCHEME = import.meta.env.VITE_REVERB_SCHEME ?? window.location.protocol.replace(':', '')
const IS_HTTPS = SCHEME === 'https'

const echo = new Echo({
    broadcaster: 'reverb',
    key: APP_KEY,
    wsHost: HOST,
    wsPort: PORT,
    wssPort: PORT,
    forceTLS: IS_HTTPS,
    enabledTransports: IS_HTTPS ? ['wss'] : ['ws', 'wss'],
    disableStats: true,
})

export default echo
