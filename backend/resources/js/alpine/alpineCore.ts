import Alpine from 'alpinejs'
import { tableSelection } from '@js/alpine/tableSelection'

/**
 * Inicializa Alpine UNA sola vez
 */
export function initAlpine(): void {
    if ((window as any).__ALPINE_STARTED__) return

    Alpine.data('tableSelection', tableSelection)
        ; (window as any).Alpine = Alpine
    Alpine.start()
        ; (window as any).__ALPINE_STARTED__ = true

    if (import.meta.env.DEV) {
        console.log('✅ Alpine.js iniciado')
    }
}
