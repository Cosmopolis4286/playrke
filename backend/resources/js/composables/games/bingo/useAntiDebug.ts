import { onMounted, onUnmounted } from 'vue'

/**
 * Previene acceso a herramientas de desarrollo en producción.
 * Solo bloquea atajos de teclado y clic derecho en elementos sensibles.
 * Los métodos de detección por dimensiones/timing son eliminados — causan
 * falsos positivos en monitores ultrawide, teclados en pantalla y zoom del SO.
 */
export function useAntiDebug() {
  const isProd = import.meta.env.PROD

  const blockedKeys = [
    { key: 'F12' },
    { ctrlKey: true, shiftKey: true, key: 'i' },
    { ctrlKey: true, shiftKey: true, key: 'I' },
    { ctrlKey: true, shiftKey: true, key: 'j' },
    { ctrlKey: true, shiftKey: true, key: 'J' },
    { ctrlKey: true, shiftKey: true, key: 'c' },
    { ctrlKey: true, shiftKey: true, key: 'C' },
    { ctrlKey: true, key: 'u' },
    { ctrlKey: true, key: 'U' },
  ]

  function isBlockedKey(e: KeyboardEvent): boolean {
    return blockedKeys.some(blocked => {
      const keyMatch   = blocked.key       ? e.key       === blocked.key       : true
      const ctrlMatch  = blocked.ctrlKey   ? e.ctrlKey   === blocked.ctrlKey   : true
      const shiftMatch = blocked.shiftKey  ? e.shiftKey  === blocked.shiftKey  : true
      return keyMatch && ctrlMatch && shiftMatch
    })
  }

  function handleKeyDown(e: KeyboardEvent) {
    if (isBlockedKey(e)) {
      e.preventDefault()
      e.stopPropagation()
    }
  }

  function handleContextMenu(e: MouseEvent) {
    const target = e.target as HTMLElement
    if (
      target.tagName === 'IMG' ||
      target.closest('.db-root') ||
      target.closest('.ig-cards-section')
    ) {
      e.preventDefault()
    }
  }

  onMounted(() => {
    if (!isProd) return
    document.addEventListener('keydown',      handleKeyDown,    true)
    document.addEventListener('contextmenu',  handleContextMenu, true)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown',     handleKeyDown,    true)
    document.removeEventListener('contextmenu', handleContextMenu, true)
  })
}
