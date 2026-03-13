// resources/js/plugins/echo.ts
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Extender el tipo global Window para TS
declare global {
    interface Window {
        Pusher: typeof Pusher
    }
}

// Asignar Pusher al window
(window as any).Pusher = Pusher // Forzamos el tipo para evitar errores TS

// Variables de entorno
const APP_KEY = import.meta.env.VITE_REVERB_APP_KEY as string
const HOST = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname
const PORT = Number(import.meta.env.VITE_REVERB_PORT) || 8080
const SCHEME = import.meta.env.VITE_REVERB_SCHEME ?? window.location.protocol.replace(':', '')
const IS_HTTPS = SCHEME === 'https'

// Instancia de Echo
const echo = new Echo({
    broadcaster: 'reverb', // o 'pusher' si usas Pusher oficial
    key: APP_KEY,
    wsHost: HOST,
    wsPort: PORT,
    wssPort: PORT,
    forceTLS: IS_HTTPS,
    enabledTransports: IS_HTTPS ? ['wss'] : ['ws', 'wss'],
    disableStats: true,
})

// Exponer globalmente para scripts inline (navbar, blade pages)
;(window as any).Echo = echo

// Exportar para usar en todo el proyecto
export default echo
