import { ref } from 'vue'
import type { AdminRole } from '@/js/types/roles/admin/role'
import axios from 'axios'

export function useAdminRoles() {
    const adminRoles = ref<AdminRole[]>([])
    const loading = ref(false)

    const fetchAdminRoles = async () => {
        loading.value = true
        try {
            const { data } = await axios.get('/roles-admin/fetch-list')
            adminRoles.value = data.roles
        } finally {
            loading.value = false
        }
    }

    const updateRoleDescription = async (roleId: string, description: string) => {
        const { data } = await axios.patch(`/roles-admin/${roleId}`, { description })
        const index = adminRoles.value.findIndex(r => r.id === roleId)
        if (index !== -1) adminRoles.value[index] = data.role
        return data.role as AdminRole
    }

    const toggleRoleStatus = async (roleId: string) => {
        const { data } = await axios.put(`/roles-admin/${roleId}/status`)
        const index = adminRoles.value.findIndex(r => r.id === roleId)
        if (index !== -1) adminRoles.value[index] = data.role
        return data.role as AdminRole
    }

    return {
        adminRoles,
        loading,
        fetchAdminRoles,
        updateRoleDescription,
        toggleRoleStatus,
    }
}
