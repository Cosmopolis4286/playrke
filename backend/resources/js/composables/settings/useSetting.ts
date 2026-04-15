import { ref, type Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { Setting } from '@/js/types/setting'

interface UseSettingReturn {
    settingData: Ref<Partial<Setting>>
    settings: Ref<Setting[]>
    setInitialSettings: (initial: Setting[]) => void
    submit: () => Promise<boolean>
    create: () => void
    edit: (id: string | number) => Promise<boolean>
    remove: (id: string | number) => Promise<boolean>
    toggleStatus: (id: string | number) => Promise<boolean>
    fetchSettings: () => Promise<boolean>
}

export function useSetting(): UseSettingReturn {
    const loading     = ref(false)
    const settingData = ref<Partial<Setting>>({})
    const settings    = ref<Setting[]>([])

    function setInitialSettings(initial: Setting[]) {
        settings.value = [...initial]
    }

    async function fetchSettings(): Promise<boolean> {
        try {
            loading.value = true
            const { data } = await backendClient.get<{ success: boolean; data: Setting[] }>('/settings/fetch-list')
            if (Array.isArray(data?.data)) settings.value = data.data
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
            if (settingData.value.id) {
                const { data } = await backendClient.put(`/settings/${settingData.value.id}`, settingData.value)
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post('/settings', settingData.value)
                showToast(data.message, 'success')
            }
            await fetchSettings()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al guardar el parámetro', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        settingData.value = { param: '', value: null, status: 'ACTIVE' }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            const { data } = await backendClient.get<{ success: boolean; data: Setting }>(`/settings/${id}`)
            if (!data?.data) throw new Error('No encontrado')
            const s = data.data
            settingData.value = { id: s.id, param: s.param, value: s.value, status: s.status }
            return true
        } catch {
            showToast('Error al cargar el parámetro', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const s = settings.value.find(x => x.id === id)
        const currentLabel = s?.status === 'ACTIVE' ? 'activo' : 'inactivo'
        const nextLabel    = s?.status === 'ACTIVE' ? 'inactivo' : 'activo'

        const confirm = await showToastConfirmAction({
            html: `El parámetro <b>${s?.param ?? ''}</b> está actualmente <b>${currentLabel}</b>. ¿Deseas marcarlo como <b>${nextLabel}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, alternar',
        })
        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/settings/${id}/status`)
            showToast(data.message, 'success')
            await fetchSettings()
            return true
        } catch {
            showToast('Error al actualizar el estado', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const s = settings.value.find(x => x.id === id)

        const confirm = await showToastConfirmAction({
            html: `¿Deseas eliminar el parámetro <b>${s?.param ?? ''}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar',
        })
        if (!confirm) return false

        try {
            await backendClient.put(`/settings/${id}/delete`)
            showToast('Parámetro eliminado exitosamente', 'success')
            await fetchSettings()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al eliminar el parámetro', 'error')
            return false
        }
    }

    return { settingData, settings, setInitialSettings, submit, create, edit, remove, toggleStatus, fetchSettings }
}
