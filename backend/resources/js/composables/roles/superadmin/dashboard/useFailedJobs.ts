import { ref } from 'vue'
import axios from 'axios'
import { showDetailedToast } from '@/js/utils/toastNotify'

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
            showDetailedToast('No se pudo cargar los jobs fallidos', 'Verifica tu conexión e intenta de nuevo.', 'error')
        } finally {
            loading.value = false
        }
    }

    async function retryJob(uuid: string): Promise<void> {
        retryingUuid.value = uuid
        const job = jobs.value.find(j => j.uuid === uuid)
        try {
            const { data } = await axios.post(`${baseUrl}/${uuid}/retry`)
            showDetailedToast(
                data.success ? 'Job reencolado' : 'No se pudo reencolar',
                job ? `${job.job_name} · ${job.queue}` : null,
                data.success ? 'success' : 'warning'
            )
            if (data.success) jobs.value = jobs.value.filter(j => j.uuid !== uuid)
        } catch (error) {
            console.error(error)
            showDetailedToast('Error al reencolar el job', job ? job.job_name : null, 'error')
        } finally {
            retryingUuid.value = null
        }
    }

    async function retryAll(): Promise<void> {
        retryingAll.value = true
        const count = jobs.value.length
        try {
            await axios.post(`${baseUrl}/retry-all`)
            showDetailedToast('Jobs reencolados', `${count} job(s) fallido(s) volvieron a la cola.`, 'success')
            jobs.value = []
        } catch (error) {
            console.error(error)
            showDetailedToast('Error al reencolar los jobs', 'Ninguno se movió de la lista de fallidos.', 'error')
        } finally {
            retryingAll.value = false
        }
    }

    async function forgetJob(uuid: string): Promise<void> {
        const job = jobs.value.find(j => j.uuid === uuid)
        try {
            const { data } = await axios.delete(`${baseUrl}/${uuid}`)
            showDetailedToast(
                data.success ? 'Job descartado' : 'No se pudo descartar',
                job ? `${job.job_name} · ${job.queue}` : null,
                data.success ? 'warning' : 'error'
            )
            if (data.success) jobs.value = jobs.value.filter(j => j.uuid !== uuid)
        } catch (error) {
            console.error(error)
            showDetailedToast('Error al descartar el job', job ? job.job_name : null, 'error')
        }
    }

    return { jobs, loading, retryingUuid, retryingAll, loadJobs, retryJob, retryAll, forgetJob }
}
