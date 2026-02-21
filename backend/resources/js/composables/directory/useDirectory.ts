import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { DirectoryVEN, DirectoryTableData, DirectoryContext } from '@/js/types/directory/directory-ven'

/**
 * Contrato público del composable useDirectory
 */
interface UseDirectoryReturn {
    /** Datos del directorio (para crear o editar) */
    directoryData: Ref<Partial<DirectoryVEN>>

    /** Datos de tabla (columns + rows) */
    directories: Ref<DirectoryTableData>

    /** País actualmente seleccionado */
    activeCountry: Ref<string>

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

    /** Fetch lista desde backend (por país) */
    fetchDirectories: (countryCode?: string) => Promise<boolean>

    /** Fetch contexto completo (bancos + documentTypes + directorios) por país */
    fetchContext: (countryCode: string) => Promise<DirectoryContext | null>
}

/**
 * Factory para un directorio vacío según país
 */
const emptyDirectory = (countryCode: string = 'VE'): Partial<DirectoryVEN> => {
    if (countryCode === 'BR') {
        return Object.freeze({
            holder_name: '',
            pix_key_type: '',
            pix_key_value: '',
            bank_id: '',
            country_code: 'BR',
        } as any)
    }
    return Object.freeze({
        holder_name: '',
        document_type_id: '',
        document_number: '',
        phone: '',
        bank_id: '',
        country_code: 'VE',
    } as any)
}

/**
 * Factory para datos de tabla vacíos
 */
const emptyTableData = (): DirectoryTableData => ({
    columns: [],
    rows: []
})

/**
 * Composable para manejar directorios multi-país
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 * - Carga dinámica de contexto al cambiar de país
 *
 * ⚠️ No controla UI directamente
 */
export function useDirectory(tokenId: string = ''): UseDirectoryReturn {
    /** Estado de carga global */
    const loading = ref(false)

    /** País actualmente activo */
    const activeCountry = ref<string>('VE')

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
     * Obtiene la lista de directorios desde backend para un país dado
     */
    async function fetchDirectories(countryCode: string = activeCountry.value): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: DirectoryTableData }>(
                '/directory/fetch-list',
                { params: { token: tokenId, country: countryCode } }
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
     * Obtiene el contexto completo (bancos + documentTypes + directorios) para un país.
     * Usado al cambiar de tab.
     */
    async function fetchContext(countryCode: string): Promise<DirectoryContext | null> {
        try {
            loading.value = true
            activeCountry.value = countryCode

            const response = await backendClient.get<{ data: DirectoryContext }>(
                '/directory/fetch-context',
                { params: { token: tokenId, country: countryCode } }
            )

            const data = response.data?.data
            if (data) {
                setInitialDirectories(data.directories)
                return data
            }

            return null
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el directorio del país', 'error')
            return null
        } finally {
            loading.value = false
        }
    }

    /**
     * Envío del formulario (create / update).
     * Incluye country_code para que el backend despache VE vs BR.
     */
    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const payload = {
                ...directoryData.value,
                country_code: activeCountry.value,
            }

            if (directoryData.value.id) {
                const { data } = await backendClient.patch(
                    `/directory/${directoryData.value.id}/update`,
                    payload
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/directory',
                    payload
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
        directoryData.value = { ...emptyDirectory(activeCountry.value) }
    }

    /**
     * Modo edición — soporta VE (Pago Móvil) y BR (PIX)
     */
    async function edit(id: string): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ data: any }>(
                `/directory/${id}/show`,
                { params: { country: activeCountry.value } }
            )

            const raw = data.data

            if (activeCountry.value === 'BR') {
                directoryData.value = {
                    id:            raw.id,
                    holder_name:   raw.holder_name,
                    pix_key_type:  raw.pix_key_type,
                    pix_key_value: raw.pix_key_value,
                    bank_id:       raw.bank?.id ?? raw.bank_id,
                    country_code:  'BR',
                } as any
            } else {
                directoryData.value = {
                    id: raw.id,
                    holder_name: raw.holder_name,
                    document_type_id: raw.document_type?.id ?? raw.document_type_id,
                    document_number: raw.document_number,
                    phone: raw.phone,
                    bank_id: raw.bank?.id ?? raw.bank_id,
                    country_code: 'VE',
                } as any
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
        directoryData.value = { ...emptyDirectory(activeCountry.value) }
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
            await backendClient.patch(`/directory/${id}/status`, {}, { params: { country: activeCountry.value } })
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
            await backendClient.delete(`/directory/${id}/delete`, { params: { country: activeCountry.value } })
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
        activeCountry,
        setInitialDirectories,
        submit,
        create,
        edit,
        reset,
        changeStatus,
        remove,
        fetchDirectories,
        fetchContext
    }
}
