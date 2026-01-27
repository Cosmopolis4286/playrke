import { onMounted, onBeforeUnmount } from 'vue'

/**
 * Composable genérico para manejar eventos CRUD globales.
 *
 * Recibe un objeto con acciones mapeadas a funciones async o sync.
 *
 * @param actions Mapeo de acción → función (que recibe ids si aplica)
 */
export function useCrudEventListener(actions: {
    [action: string]: ((ids?: string[]) => void | Promise<void>) | undefined
}) {
    /**
     * Manejador para eventos custom `table:crud`
     * Ejecuta la función asociada a la acción recibida, pasando los IDs encriptados si existen.
     *
     * @param event Evento CustomEvent con detail { action, encryptIds }
     */
    async function handleCrudCustomEvent(event: Event) {
        const ev = event as CustomEvent<{ action: string; encryptIds: string[] }>
        if (!ev.detail) return

        const { action, encryptIds } = ev.detail

        const handler = actions[action]
        if (typeof handler !== 'function') {
            console.warn(`[useCrudEventListener] Acción no manejada: ${action}`)
            return
        }

        try {
            await handler(encryptIds)
        } catch (error) {
            console.error(`[useCrudEventListener] Error ejecutando acción '${action}':`, error)
        }
    }

    onMounted(() => {
        window.addEventListener('table:crud', handleCrudCustomEvent)

        if (import.meta.env.DEV) {
            console.info('[useCrudEventListener] inicializado')
        }
    })

    onBeforeUnmount(() => {
        window.removeEventListener('table:crud', handleCrudCustomEvent)
    })
}
