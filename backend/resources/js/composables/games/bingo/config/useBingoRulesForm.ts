import { ref, computed } from 'vue'
import type { BingoRulesFormData } from '@/js/types/games/bingo/config/bingo-config'

/**
 * Composable para el formulario de reglamento del bingo.
 *
 * Gestiona:
 * - Estado reactivo del form (rules, image)
 * - Validación
 * - Preview de la imagen seleccionada
 */
export function useBingoRulesForm(initialRules: string = '') {
    const form = ref<BingoRulesFormData>({
        rules: initialRules,
        image: null,
    })

    const imagePreview = ref<string | null>(null)

    /**
     * Errores de validación client-side
     */
    const errors = computed<Partial<Record<keyof BingoRulesFormData, string>>>(() => {
        const e: Partial<Record<keyof BingoRulesFormData, string>> = {}

        if (!form.value.rules?.trim()) {
            e.rules = 'El reglamento es obligatorio.'
        } else if (form.value.rules.trim().length < 10) {
            e.rules = 'El reglamento debe tener al menos 10 caracteres.'
        }

        return e
    })

    const isValid = computed(() => Object.keys(errors.value).length === 0)

    /**
     * Maneja el cambio de imagen: guarda el File y genera preview.
     */
    function onImageChange(file: File | null): void {
        form.value.image = file

        if (imagePreview.value) {
            URL.revokeObjectURL(imagePreview.value)
        }

        imagePreview.value = file ? URL.createObjectURL(file) : null
    }

    return {
        form,
        errors,
        isValid,
        imagePreview,
        onImageChange,
    }
}
