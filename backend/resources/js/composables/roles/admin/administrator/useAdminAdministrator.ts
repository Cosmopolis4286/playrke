import { ref, Ref } from 'vue'
import Swal from 'sweetalert2'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { showPasswordModal } from '@/js/helpers/user/password'
import type { Administrator, AdministratorCreateData, AdministratorEditData } from '@/js/types/roles/admin/administrator'
import type { Pagination } from '@/js/types/common/table/pagination'
import type { ServerQuery } from '@/js/composables/common/table/useTableController'

interface UseAdminAdministratorReturn {
    admins: Ref<Administrator[]>
    pagination: Ref<Pagination | null>
    loading: Ref<boolean>
    lastQuery: Ref<ServerQuery>
    editData: Ref<Partial<AdministratorEditData>>
    setInitialAdmins: (initial: Administrator[], initialPagination?: Pagination | null) => void
    fetchAdmins: () => Promise<boolean>
    onQueryChange: (query: ServerQuery) => Promise<void>
    createAdministrator: (data: AdministratorCreateData) => Promise<boolean>
    editAdministrator: (id: string) => void
    updateAdministrator: () => Promise<boolean>
    toggleStatus: (id: string) => Promise<boolean>
    remove: (ids: string[]) => Promise<boolean>
    destroy: (ids: string[]) => Promise<boolean>
    resetPassword: (id: string) => Promise<boolean>
}

/**
 * Composable para gestión de Administradores (/admins) — mismo patrón que
 * useAdminUser.ts: backendClient (no window.route), filtros/paginación
 * server-side reales, y helpers de creación/edición para el flujo completo
 * de alta.
 */
export function useAdminAdministrator(): UseAdminAdministratorReturn {
    const admins = ref<Administrator[]>([])
    const pagination = ref<Pagination | null>(null)
    const loading = ref(false)
    const editData = ref<Partial<AdministratorEditData>>({})

    const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: null }, page: 1 })

    function setInitialAdmins(initial: Administrator[], initialPagination: Pagination | null = null) {
        admins.value = [...initial]
        pagination.value = initialPagination
    }

    async function fetchAdmins(): Promise<boolean> {
        try {
            loading.value = true
            const response = await backendClient.get<{ data: Administrator[]; meta: Pagination }>('/admins/fetch-list', {
                params: {
                    page: lastQuery.value.page,
                    search: lastQuery.value.search || undefined,
                    role_name: lastQuery.value.filters.role_name || undefined,
                    country_name: lastQuery.value.filters.country_name || undefined,
                    status: lastQuery.value.filters.status || undefined,
                },
            })
            if (Array.isArray(response.data?.data)) {
                admins.value = response.data.data
            }
            if (response.data?.meta) {
                pagination.value = response.data.meta
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los administradores', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function onQueryChange(query: ServerQuery): Promise<void> {
        lastQuery.value = query
        await fetchAdmins()
    }

    async function createAdministrator(data: AdministratorCreateData): Promise<boolean> {
        try {
            await backendClient.post('/admins', data)
            showToast('Administrador registrado correctamente', 'success')
            await fetchAdmins()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(error?.response?.data?.message ?? 'No se pudo registrar el administrador', 'error')
            return false
        }
    }

    function editAdministrator(id: string): void {
        const admin = admins.value.find(a => a.id === id)
        if (!admin) return

        editData.value = {
            id: admin.id,
            first_name: admin.first_name ?? '',
            last_name: admin.last_name ?? '',
            username: admin.username ?? '',
            email: admin.email ?? '',
            phone_number: admin.phone_number ?? '',
            country_id: admin.country_id,
            status: admin.status,
            real_id: admin.real_id,
        }
    }

    async function updateAdministrator(): Promise<boolean> {
        if (loading.value || !editData.value.id) return false

        try {
            loading.value = true
            await backendClient.patch(`/admins/${editData.value.id}`, {
                first_name: editData.value.first_name,
                last_name: editData.value.last_name,
                username: editData.value.username,
                email: editData.value.email,
                phone_number: editData.value.phone_number,
                country_id: editData.value.country_id,
            })
            showToast('Administrador actualizado correctamente', 'success')
            await fetchAdmins()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(error?.response?.data?.message ?? 'Error al actualizar el administrador', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string): Promise<boolean> {
        const admin = admins.value.find(a => a.id === id)
        const name = admin?.full_name || admin?.username || 'este administrador'

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado',
        })
        if (!confirm) return false

        try {
            await backendClient.patch(`/admins/${id}/toggle-status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchAdmins()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'No se pudo cambiar el estado', 'error')
            return false
        }
    }

    async function remove(ids: string[]): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar ${ids.length} administrador(es)?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar',
        })
        if (!confirm) return false

        try {
            await Promise.all(ids.map(id => backendClient.delete(`/admins/${id}`)))
            showToast('Administrador(es) eliminado(s)', 'success')
            await fetchAdmins()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'No se pudieron eliminar los administradores', 'error')
            return false
        }
    }

    async function destroy(ids: string[]): Promise<boolean> {
        const result = await Swal.fire({
            title: 'Eliminar permanentemente',
            text: `¿Estás seguro de eliminar permanentemente ${ids.length} administrador(es)? Esta acción no se puede deshacer.`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
        })
        if (!result.isConfirmed) return false

        try {
            await Promise.all(ids.map(id => backendClient.delete(`/admins/${id}/force-delete`)))
            showToast('Administrador(es) eliminado(s) permanentemente', 'success')
            await fetchAdmins()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'No se pudieron eliminar los administradores', 'error')
            return false
        }
    }

    async function resetPassword(id: string): Promise<boolean> {
        const admin = admins.value.find(a => a.id === id)
        const name = admin?.full_name || admin?.username || 'este administrador'

        if (admin?.status !== 'ACTIVE') {
            showToast('Solo se puede resetear la contraseña de administradores activos', 'warning')
            return false
        }

        const confirm = await showToastConfirmAction({
            html: `¿Deseas resetear la contraseña de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, resetear',
        })
        if (!confirm) return false

        try {
            const response = await backendClient.put(`/admins/${id}/reset-password`)
            await showPasswordModal(response.data.password)
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'No se pudo restablecer la contraseña', 'error')
            return false
        }
    }

    return {
        admins,
        pagination,
        loading,
        lastQuery,
        editData,
        setInitialAdmins,
        fetchAdmins,
        onQueryChange,
        createAdministrator,
        editAdministrator,
        updateAdministrator,
        toggleStatus,
        remove,
        destroy,
        resetPassword,
    }
}
