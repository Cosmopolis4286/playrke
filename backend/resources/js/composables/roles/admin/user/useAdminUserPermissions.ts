import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

export interface CatalogPermission {
    id: string
    name: string
    guard_name: string
    description?: string | null
    is_system: boolean
}

export interface UserPermissionsData {
    userId: string | number
    userName: string
    catalog: CatalogPermission[]
    viaRoles: string[]
    specific: string[]
}

/**
 * Gestión de permisos específicos de un usuario individual (distinto de la
 * matriz rol×permiso — ver PermissionMatrix.vue). Solo superadmin, protegido
 * también en el backend (UserPolicy::managePermissions + role:superadmin).
 */
export function useAdminUserPermissions() {
    const loading = ref(false)
    const saving = ref(false)

    async function fetchPermissions(userId: string | number, userName: string): Promise<UserPermissionsData | null> {
        loading.value = true
        try {
            const { data } = await backendClient.get(`/admin/user/${userId}/permissions`)
            return {
                userId,
                userName,
                catalog: data.catalog ?? [],
                viaRoles: data.via_roles ?? [],
                specific: data.specific ?? [],
            }
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los permisos del usuario', 'error')
            return null
        } finally {
            loading.value = false
        }
    }

    async function savePermissions(userId: string | number, permissions: string[]): Promise<boolean> {
        saving.value = true
        try {
            await backendClient.put(`/admin/user/${userId}/permissions`, { permissions })
            showToast('Permisos actualizados correctamente', 'success')
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al actualizar los permisos', 'error')
            return false
        } finally {
            saving.value = false
        }
    }

    return {
        loading,
        saving,
        fetchPermissions,
        savePermissions,
    }
}
