import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

import type { BingoRulesFormData } from '@/js/types/games/bingo/config/bingo-config'

/**
 * Composable para manejar la configuración de reglas del bingo.
 *
 * Responsabilidades:
 * - Enviar imagen + texto de reglamento al backend (multipart/form-data)
 * - Gestionar el estado de carga
 */
export function useBingoConfig() {
    const loading = ref(false)

    /**
     * Envía el formulario de configuración al backend.
     * Usa multipart/form-data para soportar la subida de imagen.
     */
    async function save(data: BingoRulesFormData): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const formData = new FormData()
            formData.append('rules', data.rules ?? '')

            if (data.image instanceof File) {
                formData.append('image', data.image)
            }

            const { data: response } = await backendClient.post(
                '/bingo/config/save',
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            )

            showToast(response.message ?? 'Configuración guardada exitosamente.', 'success')
            return true
        } catch (error: any) {
            console.error('Error al guardar la configuración del bingo:', error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar la configuración.',
                'error'
            )
            return false
        } finally {
            loading.value = false
        }
    }

    return { loading, save }
}
