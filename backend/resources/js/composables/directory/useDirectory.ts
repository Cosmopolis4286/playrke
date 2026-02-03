import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { Directory } from '@/js/types/directory'

/**
 * Contrato público del composable useDirectory
 */
interface UseDirectoryReturn {
    /** Datos del directorio (para crear o editar) */
    directoryData: Ref<Partial<Directory>>

    /** Lista reactiva de directorios */
    directories: Ref<Directory[]>

    /** Inicializa la lista local */
    setInitialDirectories: (initialDirectories: Directory[]) => void

    /** Envío del formulario */
    submit: () => Promise<boolean>

    /** Modo creación */
    create: () => void

    /** Modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Reset del formulario */
    reset: () => void

    /** Cambio de estado */
    changeStatus: (id: string | number) => Promise<boolean>

    /** Eliminación */
    remove: (id: string | number) => Promise<boolean>

    /** Fetch desde backend */
    fetchDirectories: () => Promise<boolean>
}

/**
 * Factory para un directorio vacío (Pago Móvil)
 */
const emptyDirectory = (): Partial<Directory> =>
    Object.freeze({
        document_type_id: '',
        document: '',
        phone: '',
        bank_id: ''
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
    const directoryData = ref<Partial<Directory>>(emptyDirectory())

    /** Lista reactiva local */
    const directories = ref<Directory[]>([])

    /**
     * Inicializa la lista local
     */
    function setInitialDirectories(initialDirectories: Directory[]) {
        directories.value = [...initialDirectories]
    }

    /**
     * Obtiene la lista desde backend
     */
    async function fetchDirectories(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: Directory[] }>(
                '/admin/directory/list-filtered',
                { params: { token: tokenId } }
            )

            if (Array.isArray(response.data?.data)) {
                directories.value = response.data.data
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
    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ data: Directory }>(
                `/admin/directory/${id}`
            )

            directoryData.value = {
                id: data.data.id,
                document_type_id: data.data.document_type?.id ?? data.data.document_type_id,
                document: data.data.document,
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
    async function changeStatus(id: string | number): Promise<boolean> {
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
    async function remove(id: string | number): Promise<boolean> {
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
