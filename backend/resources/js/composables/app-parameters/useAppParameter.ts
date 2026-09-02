import { ref, type Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { AppParameter } from '@/js/types/app-parameter'

interface UseAppParameterReturn {
    loading: Ref<boolean>
    parameterData: Ref<Partial<AppParameter>>
    parameters: Ref<AppParameter[]>
    setInitialParameters: (initial: AppParameter[]) => void
    submit: () => Promise<boolean>
    create: () => void
    edit: (id: string | number) => Promise<boolean>
    remove: (id: string | number) => Promise<boolean>
    toggleStatus: (id: string | number) => Promise<boolean>
    fetchParameters: () => Promise<boolean>
}

export function useAppParameter(): UseAppParameterReturn {
    const loading       = ref(false)
    const parameterData = ref<Partial<AppParameter>>({})
    const parameters    = ref<AppParameter[]>([])

    function setInitialParameters(initial: AppParameter[]) {
        parameters.value = [...initial]
    }

    async function fetchParameters(): Promise<boolean> {
        try {
            loading.value = true
            const { data } = await backendClient.get<{ success: boolean; data: AppParameter[] }>('/app-parameters/fetch-list')
            if (Array.isArray(data?.data)) parameters.value = data.data
            return true
        } catch {
            showToast('Error al cargar la configuración', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            if (parameterData.value.id) {
                const { data } = await backendClient.put(`/app-parameters/${parameterData.value.id}`, parameterData.value)
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post('/app-parameters', parameterData.value)
                showToast(data.message, 'success')
            }
            await fetchParameters()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al guardar el parámetro', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        parameterData.value = { param: '', value: null, status: 'ACTIVE' }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            const { data } = await backendClient.get<{ success: boolean; data: AppParameter }>(`/app-parameters/${id}`)
            if (!data?.data) throw new Error('No encontrado')
            const p = data.data
            parameterData.value = { id: p.id, param: p.param, value: p.value, status: p.status }
            return true
        } catch {
            showToast('Error al cargar el parámetro', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const p = parameters.value.find(x => x.id === id)
        const currentLabel = p?.status === 'ACTIVE' ? 'activo' : 'inactivo'
        const nextLabel    = p?.status === 'ACTIVE' ? 'inactivo' : 'activo'

        const confirm = await showToastConfirmAction({
            html: `El parámetro <b>${p?.param ?? ''}</b> está actualmente <b>${currentLabel}</b>. ¿Deseas marcarlo como <b>${nextLabel}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, alternar',
        })
        if (!confirm) return false

        try {
            loading.value = true
            const { data } = await backendClient.put(`/app-parameters/${id}/status`)
            showToast(data.message, 'success')
            await fetchParameters()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al actualizar el estado', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const p = parameters.value.find(x => x.id === id)

        const confirm = await showToastConfirmAction({
            html: `¿Deseas eliminar el parámetro <b>${p?.param ?? ''}</b>? Esta acción no se puede deshacer.`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar',
        })
        if (!confirm) return false

        try {
            loading.value = true
            await backendClient.put(`/app-parameters/${id}/delete`)
            showToast(`Parámetro "${p?.param ?? id}" eliminado exitosamente`, 'success')
            await fetchParameters()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al eliminar el parámetro', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    return { loading, parameterData, parameters, setInitialParameters, submit, create, edit, remove, toggleStatus, fetchParameters }
}
