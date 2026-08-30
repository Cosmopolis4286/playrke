import { ref } from 'vue'
import axios from 'axios'
import { showToast } from '@/js/utils/toastNotify'

export interface FailedJob {
    uuid:               string
    connection:         string
    queue:               string
    job_name:           string
    exception_summary:  string
    failed_at:          string
    server_timezone:    string
}

/**
 * Gestión de jobs fallidos (failed_jobs) — usado por el modal del widget
 * "Salud del sistema" del dashboard (baseUrl default /superadmin/failed-jobs)
 * y por el panel inline de /system/jobs (baseUrl /system/jobs/failed).
 * Misma lógica de fetch/retry/forget en ambos, solo cambia el prefijo de ruta
 * — evita duplicar este composable para un segundo consumidor de los mismos
 * datos (FailedJobService es la única fuente de verdad en el backend).
 */
export function useFailedJobs(baseUrl: string = '/superadmin/failed-jobs') {
    const jobs = ref<FailedJob[]>([])
    const loading = ref(false)
    const retryingUuid = ref<string | null>(null)
    const retryingAll = ref(false)

    async function loadJobs(): Promise<void> {
        loading.value = true
        try {
            const { data } = await axios.get(baseUrl)
            jobs.value = data.data ?? []
        } catch (error) {
            console.error(error)
            showToast('No se pudo cargar la lista de jobs fallidos', 'error')
        } finally {
            loading.value = false
        }
    }

    async function retryJob(uuid: string): Promise<void> {
        retryingUuid.value = uuid
        try {
            const { data } = await axios.post(`${baseUrl}/${uuid}/retry`)
            showToast(data.message, data.success ? 'success' : 'error')
            if (data.success) jobs.value = jobs.value.filter(j => j.uuid !== uuid)
        } catch (error) {
            console.error(error)
            showToast('Error al reencolar el job', 'error')
        } finally {
            retryingUuid.value = null
        }
    }

    async function retryAll(): Promise<void> {
        retryingAll.value = true
        try {
            const { data } = await axios.post(`${baseUrl}/retry-all`)
            showToast(data.message, 'success')
            jobs.value = []
        } catch (error) {
            console.error(error)
            showToast('Error al reencolar los jobs', 'error')
        } finally {
            retryingAll.value = false
        }
    }

    async function forgetJob(uuid: string): Promise<void> {
        try {
            const { data } = await axios.delete(`${baseUrl}/${uuid}`)
            showToast(data.message, data.success ? 'success' : 'error')
            if (data.success) jobs.value = jobs.value.filter(j => j.uuid !== uuid)
        } catch (error) {
            console.error(error)
            showToast('Error al descartar el job', 'error')
        }
    }

    return { jobs, loading, retryingUuid, retryingAll, loadJobs, retryJob, retryAll, forgetJob }
}
