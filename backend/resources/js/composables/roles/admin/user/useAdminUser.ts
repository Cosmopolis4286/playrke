import { ref, Ref } from 'vue'
import { router } from '@inertiajs/vue3'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { showPasswordModal } from '@/js/helpers/user/password'
import type { AdminUser, AdminRole, ROLE_ROUTE_MAP } from '@/js/types/roles/admin/admin-user'
import Swal from 'sweetalert2'

interface UseAdminUserReturn {
    users: Ref<AdminUser[]>
    setInitialUsers: (initial: AdminUser[]) => void
    fetchUsers: () => Promise<boolean>
    selectRoleAndRedirect: (roles: AdminRole[]) => Promise<void>
    toggleStatus: (id: string | number) => Promise<boolean>
    toggleStatusBulk: (ids: (string | number)[]) => Promise<boolean>
    remove: (id: string | number) => Promise<boolean>
    removeBulk: (ids: (string | number)[]) => Promise<boolean>
    resetPassword: (id: string | number) => Promise<boolean>
    loadWalletBalance: (id: string | number) => Promise<boolean>
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
     * Muestra SweetAlert con selector de rol.
     * Al confirmar, redirige a la vista de creación correspondiente.
     */
    async function selectRoleAndRedirect(roles: AdminRole[]): Promise<void> {
        const rolesWithRoute = roles.filter(r => r.name.toLowerCase() in ROLE_ROUTES)

        if (rolesWithRoute.length === 0) {
            showToast('No hay roles disponibles para registrar usuarios', 'warning')
            return
        }

        const inputOptions = Object.fromEntries(
            rolesWithRoute.map(r => [r.name.toLowerCase(), ucfirst(r.name)])
        )

        const { value: selectedRole, isConfirmed } = await Swal.fire({
            title: 'Registrar nuevo usuario',
            text: 'Selecciona el rol del usuario a registrar:',
            input: 'select',
            inputOptions,
            inputPlaceholder: 'Seleccionar rol...',
            showCancelButton: true,
            confirmButtonText: 'Ir a registrar',
            cancelButtonText: 'Cancelar',
            inputValidator: value => {
                if (!value) return 'Debes seleccionar un rol'
            }
        })

        if (!isConfirmed || !selectedRole) return

        const route = ROLE_ROUTES[selectedRole]
        if (route) {
            router.visit(route)
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
            for (const id of ids) await backendClient.put(`/admin/user/${id}/status`)
            showToast(`Estado cambiado para ${ids.length} usuarios`, 'success')
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
            for (const id of ids) await backendClient.put(`/admin/user/${id}/delete`)
            showToast(`${ids.length} usuarios eliminados con éxito`, 'success')
            await fetchUsers()
            return true
        } catch {
            showToast('Error al eliminar los usuarios', 'error')
            return false
        }
    }

    async function loadWalletBalance(id: string | number): Promise<boolean> {
        const user = users.value.find(u => u.id === id)
        const name = user?.full_name || user?.username || 'este usuario'

        const { value: amount, isConfirmed } = await Swal.fire({
            title: 'Cargar Saldo',
            text: `Ingrese el monto a cargar para <b>${name}</b>:`,
            input: 'number',
            inputPlaceholder: '0.00',
            showCancelButton: true,
            confirmButtonText: 'Cargar Saldo',
            inputValidator: value => {
                if (!value || parseFloat(value) <= 0) return 'Debe ingresar un monto válido mayor a 0'
            }
        })

        if (!isConfirmed || !amount) return false

        try {
            const { data } = await backendClient.put(`/admin/user/${id}/load-wallet-balance`, {
                amount: parseFloat(amount)
            })
            showToast(data.message ?? `Saldo cargado: $${amount}`, 'success')
            await fetchUsers()
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el saldo', 'error')
            return false
        }
    }

    return {
        users,
        setInitialUsers,
        fetchUsers,
        selectRoleAndRedirect,
        toggleStatus,
        toggleStatusBulk,
        remove,
        removeBulk,
        resetPassword,
        loadWalletBalance,
    }
}

function ucfirst(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1)
}
