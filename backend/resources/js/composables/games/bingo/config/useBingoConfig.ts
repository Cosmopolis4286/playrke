import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

/**
 * Composable para manejar la configuración de reglas del bingo.
 *
 * Expone dos operaciones independientes:
 * - saveRules: guarda solo el texto del reglamento (JSON)
 * - saveImage: sube solo la imagen (multipart/form-data)
 */
export function useBingoConfig() {
    const loadingRules = ref(false)
    const loadingImage = ref(false)

    /**
     * Guarda el texto del reglamento.
     */
    async function saveRules(rules: string): Promise<boolean> {
        if (loadingRules.value) return false

        try {
            loadingRules.value = true

            const { data } = await backendClient.post('/bingo/config/save-rules', { rules })

            showToast(data.message ?? 'Reglamento guardado exitosamente.', 'success')
            return true
        } catch (error: any) {
            console.error('Error al guardar el reglamento:', error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar el reglamento.',
                'error'
            )
            return false
        } finally {
            loadingRules.value = false
        }
    }

    /**
     * Sube la imagen del reglamento.
     */
    async function saveImage(image: File): Promise<boolean> {
        if (loadingImage.value) return false

        try {
            loadingImage.value = true

            const formData = new FormData()
            formData.append('image', image)

            const { data } = await backendClient.post(
                '/bingo/config/save-image',
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            )

            showToast(data.message ?? 'Imagen guardada exitosamente.', 'success')
            return true
        } catch (error: any) {
            console.error('Error al guardar la imagen:', error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar la imagen.',
                'error'
            )
            return false
        } finally {
            loadingImage.value = false
        }
    }

    return { loadingRules, loadingImage, saveRules, saveImage }
}
