import { computed } from 'vue'
import type { Administrator } from '@/js/types/roles/admin/administrator'

/**
 * Composable para gestión de Administradores
 *
 * Responsabilidades:
 * - Estado de la tabla
 * - Operaciones CRUD
 * - Eventos de tabla
 */
export function useAdminAdministrator() {
    // =======================
    // Estado reactivo
    // =======================
    
    const administrators = ref<Administrator[]>([])
    const loading = ref(false)
    const selectedIds = ref<string[]>([])

    // =======================
    // Computed
    // =======================
    
    const hasSelected = computed(() => selectedIds.value.length > 0)
    const allSelected = computed(() => {
        return administrators.value.length > 0 && selectedIds.value.length === administrators.value.length
    })

    // =======================
    // Métodos CRUD
    // =======================
    
    const fetchAdministrators = async () => {
        loading.value = true
        try {
            const response = await window.axios.get(route('admin.administrators.fetchList'))
            administrators.value = response.data.data
        } catch (error) {
            console.error('Error fetching administrators:', error)
            window.Swal.fire('Error', 'No se pudieron cargar los administradores', 'error')
        } finally {
            loading.value = false
        }
    }

    const toggleStatus = async (id: string) => {
        try {
            await window.axios.patch(route('admin.administrators.toggleStatus', { user: id }))
            window.Swal.fire('Éxito', 'Estado cambiado correctamente', 'success')
            fetchAdministrators()
        } catch (error) {
            console.error('Error toggling status:', error)
            window.Swal.fire('Error', 'No se pudo cambiar el estado', 'error')
        }
    }

    const resetPassword = async (id: string) => {
        try {
            const response = await window.axios.put(route('admin.administrators.resetPassword', { user: id }))
            window.Swal.fire({
                title: 'Contraseña Restablecida',
                text: `Nueva contraseña: ${response.data.password}`,
                icon: 'info'
            })
            fetchAdministrators()
        } catch (error) {
            console.error('Error resetting password:', error)
            window.Swal.fire('Error', 'No se pudo restablecer la contraseña', 'error')
        }
    }

    const remove = async (ids: string[]) => {
        const result = await window.Swal.fire({
            title: 'Eliminar Administradores',
            text: `¿Está seguro de eliminar ${ids.length} administrador(es)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        })

        if (result.isConfirmed) {
            try {
                await Promise.all(ids.map(id => 
                    window.axios.delete(route('admin.administrators.delete', { user: id }))
                ))
                window.Swal.fire('Eliminado', 'Administrador(es) eliminado(s)', 'success')
                fetchAdministrators()
                selectedIds.value = []
            } catch (error) {
                console.error('Error deleting administrators:', error)
                window.Swal.fire('Error', 'No se pudieron eliminar los administradores', 'error')
            }
        }
    }

    const destroy = async (ids: string[]) => {
        const result = await window.Swal.fire({
            title: 'Eliminar Permanentemente',
            text: `¿Está seguro de eliminar permanentemente ${ids.length} administrador(es)? Esta acción no se puede deshacer.`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        })

        if (result.isConfirmed) {
            try {
                await Promise.all(ids.map(id => 
                    window.axios.delete(route('admin.administrators.destroy', { user: id }))
                ))
                window.Swal.fire('Eliminado', 'Administrador(es) eliminado(s) permanentemente', 'success')
                fetchAdministrators()
                selectedIds.value = []
            } catch (error) {
                console.error('Error destroying administrators:', error)
                window.Swal.fire('Error', 'No se pudieron eliminar los administradores', 'error')
            }
        }
    }

    // =======================
    // Selección
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
        if (allSelected.value) {
            selectedIds.value = []
        } else {
            selectedIds.value = administrators.value.map(admin => admin.id)
        }
    }

    return {
        // Estado
        administrators,
        loading,
        selectedIds,
        hasSelected,
        allSelected,
        
        // Métodos
        fetchAdministrators,
        toggleStatus,
        resetPassword,
        remove,
        destroy,
        toggleSelection,
        selectAll
    }
}
