import { ref } from 'vue'

const COPIED_RESET_MS = 2000

export function useLobbyShare(roomName: string) {
  const copied = ref(false)
  let resetTimer: ReturnType<typeof setTimeout> | null = null

  async function share() {
    const url   = window.location.href
    const label = roomName || 'Bingo'
    const title = `Sala de Bingo — ${label}`
    const text  = `¡Te invito a jugar Bingo! 🎱 Únete a la sala "${label}" y gana premios.`

    if (navigator.share) {
      try {
        await navigator.share({ title, text, url })
      } catch {
        // usuario canceló el share sheet — no error
      }
      return
    }

    try {
      await navigator.clipboard.writeText(url)
      copied.value = true
      if (resetTimer) clearTimeout(resetTimer)
      resetTimer = setTimeout(() => {
        copied.value = false
        resetTimer   = null
      }, COPIED_RESET_MS)
    } catch {
      // clipboard no disponible — fallback silencioso
    }
  }

  return { share, copied }
}
