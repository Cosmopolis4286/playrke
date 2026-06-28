import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import type { MovementType } from '@/js/types/movementType'

export interface MovementTypeMeta {
    icon: string
    label: string
    color: string
    sign: '+' | '-'
    description: string | null
}

/**
 * Catálogo de tipos de movimiento (MovementType), con un helper para
 * derivar metadata visual {icon, label, color, sign} por code. Reemplaza
 * los objetos hardcoded movementMeta/typeConfig/MOV_META que existían
 * antes de que el catálogo fuera dinámico.
 */
export function useMovementTypeCatalog(initial: MovementType[] = []) {
    const movementTypes: Ref<MovementType[]> = ref([...initial])
    const loading = ref(false)

    function setInitialMovementTypes(types: MovementType[]) {
        movementTypes.value = [...types]
    }

    async function fetchMovementTypes(): Promise<boolean> {
        try {
            loading.value = true
            const { data } = await backendClient.get<{ success: boolean; data: MovementType[] }>(
                '/movement-types/fetch-list',
                { params: { status: 'ACTIVE' } }
            )
            if (Array.isArray(data?.data)) {
                movementTypes.value = data.data
            }
            return true
        } catch (error) {
            console.error('Error al obtener el catálogo de tipos de movimiento:', error)
            return false
        } finally {
            loading.value = false
        }
    }

    function metaFor(code: string): MovementTypeMeta {
        const type = movementTypes.value.find(t => t.code === code)
        if (!type) {
            return { icon: 'fas fa-circle', label: code, color: '#718096', sign: '+', description: null }
        }
        return {
            icon: type.icon,
            label: type.label,
            color: type.color,
            sign: type.is_credit ? '+' : '-',
            description: type.description,
        }
    }

    return {
        movementTypes,
        loading,
        setInitialMovementTypes,
        fetchMovementTypes,
        metaFor,
    }
}
