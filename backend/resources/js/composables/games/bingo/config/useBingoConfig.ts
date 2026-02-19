import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

export function useBingoConfig() {
    const loadingRules = ref(false)
    const loadingImage = ref(false)

    async function saveRules(rules: string): Promise<string | null> {
        if (loadingRules.value) return null
        try {
            loadingRules.value = true
            const { data } = await backendClient.post('/bingo/config/save-rules', { rules })
            showToast(data.message ?? 'Reglamento guardado exitosamente.', 'success')
            return rules
        } catch (error: any) {
            console.error('Error al guardar el reglamento:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar el reglamento.', 'error')
            return null
        } finally {
            loadingRules.value = false
        }
    }

    async function saveImage(image: File): Promise<string | null> {
        if (loadingImage.value) return null
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
            return data.image_path ?? null
        } catch (error: any) {
            console.error('Error al guardar la imagen:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar la imagen.', 'error')
            return null
        } finally {
            loadingImage.value = false
        }
    }

    return { loadingRules, loadingImage, saveRules, saveImage }
}
