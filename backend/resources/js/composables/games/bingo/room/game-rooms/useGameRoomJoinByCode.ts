import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import backendClient from '@/js/plugins/axios'

/**
 * Estado y lógica del modal "Tengo un código" — resuelve una sala privada a
 * partir de su código de acceso (sin conocer su ID de antemano) y redirige
 * a su lobby en caso de éxito.
 */
export function useGameRoomJoinByCode() {
  const isOpen = ref(false)
  const accessCode = ref('')
  const password = ref('')
  const loading = ref(false)
  const error = ref<string | null>(null)
  const redirecting = ref(false)

  function open(): void {
    isOpen.value = true
    accessCode.value = ''
    password.value = ''
    error.value = null
  }

  function close(): void {
    isOpen.value = false
  }

  async function submit(): Promise<void> {
    if (!accessCode.value || !password.value) return

    loading.value = true
    error.value = null

    try {
      const { data } = await backendClient.post<{ verified: boolean; room_route_key: string | null }>(
        '/bingo/game-rooms/join-by-code',
        { access_code: accessCode.value, password: password.value }
      )

      if (data.verified && data.room_route_key) {
        redirecting.value = true
        router.visit(`/bingo/${data.room_route_key}/lobby`, {
          onFinish: () => { redirecting.value = false },
        })
      } else {
        // Mensaje deliberadamente genérico — el backend tampoco distingue
        // "código no existe" vs "contraseña incorrecta" (evita enumeración).
        error.value = 'Código o contraseña incorrectos.'
      }
    } catch {
      error.value = 'No se pudo verificar el acceso. Intenta de nuevo.'
    } finally {
      loading.value = false
    }
  }

  return {
    isOpen,
    accessCode,
    password,
    loading,
    error,
    redirecting,
    open,
    close,
    submit,
  }
}
