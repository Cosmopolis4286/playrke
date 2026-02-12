import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { DirectoryVEN, DirectoryTableData } from '@/js/types/directory/directory-ven'

/**
 * Contrato público del composable useDirectory
 */
interface UseDirectoryReturn {
    /** Datos del directorio (para crear o editar) */
    directoryData: Ref<Partial<DirectoryVEN>>

    /** Datos de tabla (columns + rows) */
    directories: Ref<DirectoryTableData>

    /** Inicializa la lista local */
    setInitialDirectories: (initialData: DirectoryTableData) => void

    /** Envío del formulario */
    submit: () => Promise<boolean>

    /** Modo creación */
    create: () => void

    /** Modo edición */
    edit: (id: string) => Promise<boolean>

    /** Reset del formulario */
    reset: () => void

    /** Cambio de estado */
    changeStatus: (id: string) => Promise<boolean>

    /** Eliminación */
    remove: (id: string) => Promise<boolean>

    /** Fetch desde backend */
    fetchDirectories: () => Promise<boolean>
}

/**
 * Factory para un directorio vacío (Pago Móvil)
 */
const emptyDirectory = (): Partial<DirectoryVEN> =>
    Object.freeze({
        holder_name: '',
        document_type_id: '',
        document_number: '',
        phone: '',
        bank_id: ''
    })

/**
 * Factory para datos de tabla vacíos
 */
const emptyTableData = (): DirectoryTableData => ({
    columns: [],
    rows: []
})

/**
 * Composable para manejar directorios
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 *
 * ⚠️ No controla UI directamente
 */
export function useDirectory(tokenId: string = ''): UseDirectoryReturn {
    /** Estado de carga global */
    const loading = ref(false)

    /** Estado único del formulario */
    const directoryData = ref<Partial<DirectoryVEN>>(emptyDirectory())

    /** Datos de tabla (columns + rows) */
    const directories = ref<DirectoryTableData>(emptyTableData())

    /**
     * Inicializa los datos de tabla desde props
     */
    function setInitialDirectories(initialData: DirectoryTableData): void {
        if (!initialData || typeof initialData !== 'object') {
            directories.value = emptyTableData()
            return
        }

        directories.value = {
            columns: Array.isArray(initialData.columns) ? [...initialData.columns] : [],
            rows: Array.isArray(initialData.rows) ? [...initialData.rows] : []
        }
    }

    /**
     * Obtiene la lista desde backend
     */
    async function fetchDirectories(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: DirectoryTableData }>(
                '/admin/directory/fetch-list',
                { params: { token: tokenId } }
            )

            const data = response.data?.data
            if (data && typeof data === 'object') {
                directories.value = {
                    columns: Array.isArray(data.columns) ? data.columns : directories.value.columns,
                    rows: Array.isArray(data.rows) ? data.rows : []
                }
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los directorios', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Envío del formulario (create / update)
     */
    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            if (directoryData.value.id) {
                const { data } = await backendClient.put(
                    `/admin/directory/${directoryData.value.id}`,
                    directoryData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/admin/directory',
                    directoryData.value
                )
                showToast(data.message, 'success')
            }

            await fetchDirectories()
            reset()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar el directorio',
                'error'
            )
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Modo creación
     */
    function create(): void {
        directoryData.value = { ...emptyDirectory() }
    }

    /**
     * Modo edición
     */
    async function edit(id: string): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ data: DirectoryVEN }>(
                `/admin/directory/${id}`
            )

            directoryData.value = {
                id: data.data.id,
                holder_name: data.data.holder_name,
                document_type_id: data.data.document_type?.id ?? data.data.document_type_id,
                document_number: data.data.document_number,
                phone: data.data.phone,
                bank_id: data.data.bank?.id ?? data.data.bank_id
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el directorio', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Reset del formulario
     */
    function reset(): void {
        directoryData.value = { ...emptyDirectory() }
    }

    /**
     * Cambio de estado
     */
    async function changeStatus(id: string): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            text: '¿Quieres cambiar el estado de este directorio?',
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/directory/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchDirectories()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    /**
     * Eliminación (soft delete)
     */
    async function remove(id: string): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            text: '¿Estás seguro que deseas eliminar este directorio?',
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/directory/${id}/delete`)
            showToast('Directorio eliminado con éxito', 'success')
            await fetchDirectories()
            return true
        } catch {
            showToast('Error al eliminar el directorio', 'error')
            return false
        }
    }

    return {
        directoryData,
        directories,
        setInitialDirectories,
        submit,
        create,
        edit,
        reset,
        changeStatus,
        remove,
        fetchDirectories
    }
}
