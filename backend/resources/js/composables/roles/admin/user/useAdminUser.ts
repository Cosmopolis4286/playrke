import { ref, Ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { showPasswordModal } from '@/js/helpers/user/password'
import type { AdminUser, AdminUserEditData } from '@/js/types/roles/admin/admin-user'

interface BulkResult {
    updated: number
    skipped: { id: string; name: string; reason: string }[]
}

interface UseAdminUserReturn {
    users: Ref<AdminUser[]>
    userEditData: Ref<Partial<AdminUserEditData>>
    setInitialUsers: (initial: AdminUser[]) => void
    fetchUsers: () => Promise<boolean>
    redirectToRoleCreation: (roleName: string) => void
    editUser: (id: string | number) => void
    updateUser: () => Promise<boolean>
    toggleStatus: (id: string | number) => Promise<boolean>
    toggleStatusBulk: (ids: (string | number)[]) => Promise<boolean>
    remove: (id: string | number) => Promise<boolean>
    removeBulk: (ids: (string | number)[]) => Promise<boolean>
    resetPassword: (id: string | number) => Promise<boolean>
    loadWalletBalance: (id: string | number, amount: number, reason?: string) => Promise<boolean>
    getWalletBalance: (id: string | number) => Promise<{ balance: number, currency: any } | null>
    grantXp: (id: string | number, amount: number, reason?: string) => Promise<boolean>
}

/**
 * Mapa rol.name → ruta Inertia de creación
 */
const ROLE_ROUTES: Record<string, string> = {
    commerce:   '/admin/commerces',
    customer:   '/admin/customers',
    player:     '/admin/players',
    seller:     '/admin/sellers',
    treasurer:  '/admin/treasurers',
}

export function useAdminUser(): UseAdminUserReturn {
    const loading = ref(false)
    const users   = ref<AdminUser[]>([])
    const userEditData = ref<Partial<AdminUserEditData>>({})

    function setInitialUsers(initial: AdminUser[]) {
        users.value = [...initial]
    }

    async function fetchUsers(): Promise<boolean> {
        try {
            loading.value = true
            const response = await backendClient.get<{ data: AdminUser[] }>('/admin/user/fetch-list')
            if (Array.isArray(response.data?.data)) {
                users.value = response.data.data
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los usuarios', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Redirige a la vista de creación del rol indicado, usado por
     * UserCreateRoleForm.vue tras seleccionar una tarjeta de rol.
     */
    function redirectToRoleCreation(roleName: string): void {
        const route = ROLE_ROUTES[roleName.toLowerCase()]
        if (route) {
            router.visit(route)
        }
    }

    /**
     * Carga los datos de un usuario en userEditData para abrir el modal de edición.
     */
    function editUser(id: string | number): void {
        const user = users.value.find(u => u.id === id)
        if (!user) return

        userEditData.value = {
            id: user.id,
            first_name: user.first_name ?? '',
            last_name: user.last_name ?? '',
            username: user.username ?? '',
            email: user.email ?? '',
            country_id: user.country_id,
            role_id: user.role_id,
        }
    }

    /**
     * Envía los cambios de userEditData al backend (PATCH /admin/user/{id}).
     */
    async function updateUser(): Promise<boolean> {
        if (loading.value || !userEditData.value.id) return false

        try {
            loading.value = true

            await backendClient.patch(`/admin/user/${userEditData.value.id}`, {
                first_name: userEditData.value.first_name,
                last_name: userEditData.value.last_name,
                username: userEditData.value.username,
                email: userEditData.value.email,
                country_id: userEditData.value.country_id,
                role_id: userEditData.value.role_id,
            })

            showToast('Usuario actualizado correctamente', 'success')
            await fetchUsers()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(error?.response?.data?.message ?? 'Error al actualizar el usuario', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const user = users.value.find(u => u.id === id)
        const name = user?.full_name || user?.username || 'este usuario'

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })
        if (!confirm) return false

        try {
            await backendClient.put(`/admin/user/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchUsers()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const user = users.value.find(u => u.id === id)
        const name = user?.full_name || user?.username || 'este usuario'

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar a <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })
        if (!confirm) return false

        try {
            await backendClient.put(`/admin/user/${id}/delete`)
            showToast('Usuario eliminado con éxito', 'success')
            await fetchUsers()
            return true
        } catch {
            showToast('Error al eliminar el usuario', 'error')
            return false
        }
    }

    async function resetPassword(id: string | number): Promise<boolean> {
        const user = users.value.find(u => u.id === id)
        const name = user?.full_name || user?.username || 'este usuario'

        if (user?.status !== 'ACTIVE') {
            showToast('Solo se puede resetear la contraseña de usuarios activos', 'warning')
            return false
        }

        const confirm = await showToastConfirmAction({
            html: `¿Deseas resetear la contraseña de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, resetear'
        })
        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/admin/user/${id}/reset-password`)
            showToast(data.message ?? 'Contraseña reseteada', 'success')
            await showPasswordModal(data.password)
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al resetear la contraseña', 'error')
            return false
        }
    }

    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => users.value.find(u => u.id === id))
            .filter(Boolean)
            .map(u => u!.full_name || u!.username)

        return `
            <div style="max-height:150px;overflow-y:auto;text-align:left;margin-top:10px;padding:10px;background:#f8f9fa;border-radius:6px;">
                <ul style="margin:0;padding-left:20px;">
                    ${items.map(name => `<li style="padding:4px 0;">${name}</li>`).join('')}
                </ul>
            </div>
        `
    }

    /**
     * Muestra el resultado de una operación bulk. Si hubo omitidos (cuenta
     * propia, cuenta superadmin sin permiso, etc.), detalla cada uno con su
     * razón en vez de un toast genérico — el backend ya aplicó lo que pudo
     * en una sola transacción (ver AdminUserService::toggleStatusBulk/deleteBulk).
     */
    async function reportBulkResult(result: BulkResult, successVerb: string): Promise<void> {
        if (result.skipped.length === 0) {
            showToast(`${result.updated} usuario(s) ${successVerb} correctamente`, 'success')
            return
        }

        const skippedHtml = result.skipped
            .map(s => `<li style="padding:4px 0;"><b>${s.name}</b>: ${s.reason}</li>`)
            .join('')

        await Swal.fire({
            icon: result.updated > 0 ? 'warning' : 'error',
            title: result.updated > 0 ? 'Completado parcialmente' : 'No se pudo completar',
            html: `
                <p style="margin-bottom:8px;">${result.updated} usuario(s) ${successVerb} correctamente.</p>
                <p style="margin-bottom:4px;">${result.skipped.length} omitido(s):</p>
                <div style="max-height:150px;overflow-y:auto;text-align:left;padding:10px;background:#f8f9fa;border-radius:6px;">
                    <ul style="margin:0;padding-left:20px;">${skippedHtml}</ul>
                </div>
            `,
            confirmButtonText: 'Entendido',
        })
    }

    async function toggleStatusBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return toggleStatus(ids[0])

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de estos ${ids.length} usuarios?${buildItemListHtml(ids)}`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })
        if (!confirm) return false

        try {
            const { data } = await backendClient.put<BulkResult>('/admin/user/bulk/status', { ids })
            await reportBulkResult(data, 'actualizados')
            await fetchUsers()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function removeBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return remove(ids[0])

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar estos ${ids.length} usuarios?${buildItemListHtml(ids)}`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })
        if (!confirm) return false

        try {
            const { data } = await backendClient.put<BulkResult>('/admin/user/bulk/delete', { ids })
            await reportBulkResult(data, 'eliminados')
            await fetchUsers()
            return true
        } catch {
            showToast('Error al eliminar los usuarios', 'error')
            return false
        }
    }

    /**
     * Carga saldo manual a un usuario (solo aplica a roles Comercio, Vendedor
     * y Jugador — validado también en el backend vía
     * AdminUserService::loadWalletBalance).
     */
    async function loadWalletBalance(id: string | number, amount: number, reason?: string): Promise<boolean> {
        try {
            const { data } = await backendClient.put(`/admin/user/${id}/load-wallet-balance`, {
                amount,
                reason: reason || undefined,
            })
            showToast(data.message ?? `Saldo cargado: $${amount}`, 'success')
            await fetchUsers()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(error?.response?.data?.message ?? 'Error al cargar el saldo', 'error')
            return false
        }
    }

    async function getWalletBalance(id: string | number): Promise<{ balance: number, currency: any } | null> {
        try {
            const { data } = await backendClient.get(`/admin/user/${id}/wallet-balance`)
            return data
        } catch (error) {
            console.error(error)
            showToast('Error al obtener el balance', 'error')
            return null
        }
    }

    /**
     * Otorga XP manualmente a un usuario (solo aplica a rol Jugador — validado
     * también en el backend vía AdminUserService::grantXp).
     */
    async function grantXp(id: string | number, amount: number, reason?: string): Promise<boolean> {
        try {
            const { data } = await backendClient.put(`/admin/user/${id}/grant-xp`, {
                amount,
                reason: reason || undefined,
            })
            showToast(data.message ?? `XP otorgado: ${amount}`, 'success')
            await fetchUsers()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(error?.response?.data?.message ?? 'Error al otorgar XP', 'error')
            return false
        }
    }

    return {
        users,
        userEditData,
        setInitialUsers,
        fetchUsers,
        redirectToRoleCreation,
        editUser,
        updateUser,
        toggleStatus,
        toggleStatusBulk,
        remove,
        removeBulk,
        resetPassword,
        loadWalletBalance,
        getWalletBalance,
        grantXp,
    }
}
