// resources/js/plugins/echo.ts

import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

/**
 * Extensión del objeto global Window para TypeScript.
 * Permite acceder a Pusher y Echo desde scripts globales
 * o componentes que utilicen window.Echo.
 */
declare global {
    interface Window {
        Pusher: typeof Pusher
        Echo: Echo<any>
    }
}

/**
 * Laravel Echo requiere que Pusher esté disponible
 * en el objeto global window.
 */
window.Pusher = Pusher as any

/**
 * Instancia singleton de Echo.
 * Garantiza que solo exista una conexión WebSocket
 * en toda la aplicación.
 */
let echoInstance: Echo<any> | null = null

/**
 * Obtiene la instancia única de Echo.
 * Si ya existe, la reutiliza; si no, crea una nueva conexión
 * usando Laravel Reverb.
 *
 * @returns {Echo} Instancia única de Laravel Echo
 */
const getEchoInstance = (): Echo<any> => {
    if (echoInstance) return echoInstance

    /**
     * Variables de entorno expuestas por Vite.
     */
    const APP_KEY = import.meta.env.VITE_REVERB_APP_KEY
    const HOST = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname
    const PORT = Number(import.meta.env.VITE_REVERB_PORT) || 8080
    const SCHEME = import.meta.env.VITE_REVERB_SCHEME ?? window.location.protocol.replace(':', '')
    const IS_HTTPS = SCHEME === 'https'

    // Debug: mostrar variables en consola
    console.log('Echo Debug - Variables:', {
        APP_KEY, HOST, PORT, SCHEME, IS_HTTPS,
        VITE_REVERB_APP_KEY: import.meta.env.VITE_REVERB_APP_KEY,
        VITE_REVERB_HOST: import.meta.env.VITE_REVERB_HOST,
        VITE_REVERB_PORT: import.meta.env.VITE_REVERB_PORT,
        VITE_REVERB_SCHEME: import.meta.env.VITE_REVERB_SCHEME,
    })

    /**
     * Creación de la instancia Echo usando Pusher
     * conectado al servidor Reverb.
     */
    echoInstance = new Echo({
        broadcaster: 'reverb',

        // Clave pública de la aplicación Reverb
        key: APP_KEY,

        // Configuración del servidor WebSocket
        wsHost: HOST,
        wsPort: PORT,
        wssPort: PORT,

        // Si la aplicación usa HTTPS, se fuerza WSS
        forceTLS: IS_HTTPS,

        // Transportes habilitados para la conexión
        enabledTransports: ['ws', 'wss'],

        // Deshabilitar estadísticas de Pusher; Reverb no las utiliza
        disableStats: true,

        // Endpoint de autenticación para canales privados o presence
        authEndpoint: '/broadcasting/auth',
    })

    /**
     * Exponer Echo globalmente.
     * Útil para debugging o scripts inline
     */
    window.Echo = echoInstance

    /**
     * Bind de eventos de conexión
     */
    echoInstance.connector.pusher.connection.bind('connected', () => {
        console.log('✅ WebSocket conectado correctamente')
    })

    echoInstance.connector.pusher.connection.bind('error', (err: any) => {
        console.error('⚠️ Error en WebSocket:', err)
    })

    return echoInstance
}

/**
 * Instancia exportada por defecto.
 * Puede importarse directamente en cualquier módulo.
 */
const echo = getEchoInstance()

export default echo
export { getEchoInstance }