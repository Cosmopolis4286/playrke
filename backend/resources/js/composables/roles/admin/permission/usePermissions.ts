import { computed, ref } from 'vue'
import type { Permission, RolePermission, RoleWithPermissions } from '@/js/types/roles/admin/permission'
import axios from 'axios'

/**
 * Composable para gestión de Permisos
 *
 * Responsabilidades:
 * - Estado de la tabla
 * - Operaciones CRUD
 * - Gestión de permisos por rol
 */
export function usePermissions() {
    // =======================
    // Estado reactivo
    // =======================
    
    const permissions = ref<{
        rows: Permission[]
        pagination: any
    }>({
        rows: [],
        pagination: { current_page: 1, per_page: 15, total: 0, last_page: 1, from: 1, to: 0 }
    })
    const roles = ref<RoleWithPermissions[]>([])
    const loading = ref(false)
    const selectedIds = ref<string[]>([])
    const currentRole = ref<RolePermission | null>(null)

    // =======================
    // Computed
    // =======================
    
    const hasSelected = computed(() => selectedIds.value.length > 0)
    const selectedCount = computed(() => selectedIds.value.length)

    // =======================
    // Métodos de API
    // =======================
    
    const fetchPermissions = async (perPage = 15) => {
        loading.value = true
        try {
            const response = await axios.get('/permissions/fetch-list', {
                params: { per_page: perPage, _t: Date.now() } // Timestamp para evitar caché
            })
            permissions.value = {
                rows: response.data.permissions.rows,
                pagination: response.data.permissions.pagination
            }
            return response.data.permissions
        } catch (error) {
            console.error('Error fetching permissions:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    const fetchRolesWithPermissions = async () => {
        try {
            const response = await axios.get('/permissions/roles-permissions')
            roles.value = response.data.roles
            return response.data.roles
        } catch (error) {
            console.error('Error fetching roles:', error)
            throw error
        }
    }

    const fetchRolePermissions = async (roleId: string) => {
        try {
            const response = await axios.get(`/permissions/roles/${roleId}/permissions`)
            currentRole.value = response.data.role
            return response.data.role
        } catch (error) {
            console.error('Error fetching role permissions:', error)
            throw error
        }
    }

    const assignPermissions = async (roleId: string, permissionIds: string[]) => {
        try {
            const response = await axios.post(`/permissions/roles/${roleId}/assign`, {
                permission_ids: permissionIds
            })
            
            // Actualizar el rol actual si es el mismo
            if (currentRole.value?.roleId === roleId) {
                await fetchRolePermissions(roleId)
            }
            
            return response.data
        } catch (error) {
            console.error('Error assigning permissions:', error)
            throw error
        }
    }

    const revokePermissions = async (roleId: string, permissionIds: string[]) => {
        try {
            const response = await axios.post(`/permissions/roles/${roleId}/revoke`, {
                permission_ids: permissionIds
            })
            
            // Actualizar el rol actual si es el mismo
            if (currentRole.value?.roleId === roleId) {
                await fetchRolePermissions(roleId)
            }
            
            return response.data
        } catch (error) {
            console.error('Error revoking permissions:', error)
            throw error
        }
    }

    const createPermission = async (data: {
        name: string
        guard_name?: string
        description?: string
    }) => {
        try {
            const response = await axios.post('/permissions', data)
            await fetchPermissions()
            return response.data.permission
        } catch (error) {
            console.error('Error creating permission:', error)
            throw error
        }
    }

    const updatePermission = async (permissionId: string, data: {
        name?: string
        guard_name?: string
        description?: string
    }) => {
        try {
            const response = await axios.patch(`/permissions/${permissionId}`, data)
            await fetchPermissions()
            return response.data.permission
        } catch (error) {
            console.error('Error updating permission:', error)
            throw error
        }
    }

    const deletePermission = async (permissionId: string) => {
        try {
            const response = await axios.delete(`/permissions/${permissionId}`)
            await fetchPermissions()
            return response.data
        } catch (error) {
            console.error('Error deleting permission:', error)
            throw error
        }
    }

    // =======================
    // Métodos de selección
    // =======================
    
    const toggleSelection = (id: string) => {
        const index = selectedIds.value.indexOf(id)
        if (index > -1) {
            selectedIds.value.splice(index, 1)
        } else {
            selectedIds.value.push(id)
        }
    }

    const selectAll = () => {
        selectedIds.value = permissions.value.rows.map(p => p.id)
    }

    const clearSelection = () => {
        selectedIds.value = []
    }

    const togglePermissionSelection = (permissionId: string) => {
        if (!currentRole.value) return
        
        const permissions = currentRole.value.permissions
        const index = permissions.findIndex(p => p.id === permissionId)
        
        if (index > -1) {
            // Quitar permiso
            revokePermissions(currentRole.value.roleId, [permissionId])
        } else {
            // Agregar permiso
            assignPermissions(currentRole.value.roleId, [permissionId])
        }
    }

    // =======================
    // Utilidades
    // =======================
    
    const hasPermission = (permissionId: string): boolean => {
        if (!currentRole.value) return false
        return currentRole.value.permissions.some(p => p.id === permissionId)
    }

    const reset = () => {
        permissions.value = {
            rows: [],
            pagination: { current_page: 1, per_page: 15, total: 0, last_page: 1, from: 1, to: 0 }
        }
        roles.value = []
        selectedIds.value = []
        currentRole.value = null
        loading.value = false
    }

    return {
        // Estado
        permissions: computed(() => permissions.value),
        roles: computed(() => roles.value),
        loading: computed(() => loading.value),
        selectedIds: computed(() => selectedIds.value),
        currentRole: computed(() => currentRole.value),
        hasSelected,
        selectedCount,
        
        // Métodos
        fetchPermissions,
        fetchRolesWithPermissions,
        fetchRolePermissions,
        assignPermissions,
        revokePermissions,
        createPermission,
        updatePermission,
        deletePermission,
        
        // Selección
        toggleSelection,
        selectAll,
        clearSelection,
        togglePermissionSelection,
        hasPermission,
        
        // Utilidades
        reset,
    }
}
