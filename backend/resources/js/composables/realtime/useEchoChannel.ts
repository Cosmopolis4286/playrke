// resources/js/composables/realtime/useEchoChannel.ts

import { onMounted, onBeforeUnmount } from 'vue'
import echo from '@/js/plugins/echo'

/**
 * Tipado de un listener de evento
 */
export type EchoEventCallback<T = any> = (payload: T) => void

/**
 * Configuración del canal
 */
export interface UseEchoChannelOptions<T = any> {
    channel: string
    event: string
    callback: EchoEventCallback<T>
    type?: 'public' | 'private' | 'presence'
}

/**
 * Composable para suscribirse a canales de Laravel Echo.
 *
 * Maneja automáticamente:
 * - suscripción
 * - escucha de eventos
 * - limpieza al desmontar el componente
 *
 * @example
 * useEchoChannel({
 *   channel: 'bingo.room.1',
 *   event: 'NumberGenerated',
 *   callback: (data) => console.log(data)
 * })
 */
export function useEchoChannel<T = any>({
    channel,
    event,
    callback,
    type = 'public',
}: UseEchoChannelOptions<T>) {
    let channelInstance: any = null

    onMounted(() => {
        switch (type) {
            case 'private':
                channelInstance = echo.private(channel)
                break

            case 'presence':
                channelInstance = echo.join(channel)
                break

            default:
                channelInstance = echo.channel(channel)
        }

        channelInstance.listen(event, callback)
    })

    onBeforeUnmount(() => {
        if (!channelInstance) return

        echo.leave(channel)
    })
}
