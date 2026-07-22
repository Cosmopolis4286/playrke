import { ref } from 'vue'
import type { SystemModule } from '@/js/types/roles/admin/module'
import axios from 'axios'

export function useModules(initialModules?: SystemModule[]) {
    const modules = ref<SystemModule[]>(initialModules ?? [])
    const loading = ref(false)

    const fetchModules = async () => {
        loading.value = true
        try {
            const { data } = await axios.get('/modules/fetch-list')
            modules.value = data.modules
        } finally {
            loading.value = false
        }
    }

    const toggleModuleStatus = async (moduleId: string) => {
        const { data } = await axios.put(`/modules/${moduleId}/status`)
        const index = modules.value.findIndex(m => m.id === moduleId)
        if (index !== -1) {
            modules.value[index] = data.module
        }
        return data.module as SystemModule
    }

    return {
        modules,
        loading,
        fetchModules,
        toggleModuleStatus,
    }
}
