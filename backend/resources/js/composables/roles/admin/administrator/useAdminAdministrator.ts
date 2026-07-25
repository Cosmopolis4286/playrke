import { computed, ref } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
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
    
    const admins = ref<Administrator[]>([])
    const loading = ref(false)
    const selectedIds = ref<string[]>([])

    // =======================
    // Computed
    // =======================
    
    const hasSelected = computed(() => selectedIds.value.length > 0)
    const allSelected = computed(() => {
        return admins.value.length > 0 && selectedIds.value.length === admins.value.length
    })

    // =======================
    // Métodos CRUD
    // =======================
    
    const fetchadmins = async () => {
        loading.value = true
        try {
            const response = await axios.get(route('admin.indexfetchList'))
            admins.value = response.data.data
        } catch (error) {
            console.error('Error fetching admins:', error)
            window.Swal.fire('Error', 'No se pudieron cargar los administradores', 'error')
        } finally {
            loading.value = false
        }
    }

    const toggleStatus = async (id: string) => {
        try {
            await axios.patch(route('admin.indextoggleStatus', { user: id }))
            window.Swal.fire('Éxito', 'Estado cambiado correctamente', 'success')
            fetchadmins()
        } catch (error) {
            console.error('Error toggling status:', error)
            window.Swal.fire('Error', 'No se pudo cambiar el estado', 'error')
        }
    }

    const resetPassword = async (id: string) => {
        try {
            const response = await axios.put(route('admin.indexresetPassword', { user: id }))
            window.Swal.fire({
                title: 'Contraseña Restablecida',
                text: `Nueva contraseña: ${response.data.password}`,
                icon: 'info'
            })
            fetchadmins()
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
                    axios.delete(route('admin.indexdelete', { user: id }))
                ))
                window.Swal.fire('Eliminado', 'Administrador(es) eliminado(s)', 'success')
                fetchadmins()
                selectedIds.value = []
            } catch (error) {
                console.error('Error deleting admins:', error)
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
                    axios.delete(route('admin.indexdestroy', { user: id }))
                ))
                window.Swal.fire('Eliminado', 'Administrador(es) eliminado(s) permanentemente', 'success')
                fetchadmins()
                selectedIds.value = []
            } catch (error) {
                console.error('Error destroying admins:', error)
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
            selectedIds.value = admins.value.map(admin => admin.id)
        }
    }

    return {
        // Estado
        admins,
        loading,
        selectedIds,
        hasSelected,
        allSelected,
        
        // Métodos
        fetchadmins,
        toggleStatus,
        resetPassword,
        remove,
        destroy,
        toggleSelection,
        selectAll
    }
}
