import { ref, onMounted } from 'vue'

/**
 * Loading breve al montar el dashboard — los datos ya llegan resueltos por
 * SSR de Inertia (sin fetch async posterior), así que esto no espera nada
 * real; da la sensación de carga progresiva de los widgets en vez de un
 * salto brusco de vacío a lleno, consistente con dashboards que tienen
 * varios paneles independientes.
 *
 * @param durationMs Duración del skeleton antes de revelar el contenido real.
 */
export function useDashboardMountLoading(durationMs = 450) {
    const loading = ref(true)

    onMounted(() => {
        window.setTimeout(() => {
            loading.value = false
        }, durationMs)
    })

    return { loading }
}
