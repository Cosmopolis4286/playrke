import { onMounted, onUnmounted } from 'vue'

/**
 * Previene acceso a herramientas de desarrollo en producción.
 * Solo bloquea atajos de teclado y clic derecho en elementos sensibles.
 * Los métodos de detección por dimensiones/timing son eliminados — causan
 * falsos positivos en monitores ultrawide, teclados en pantalla y zoom del SO.
 */
export function useAntiDebug() {
  const isProd = import.meta.env.PROD

  // Cada regla declara explícitamente todos los modificadores para evitar
  // bloquear combinaciones no intencionadas (ej: Ctrl+Shift+U bloqueado por Ctrl+U)
  const blockedKeys: { key: string; ctrlKey: boolean; shiftKey: boolean }[] = [
    { key: 'F12',  ctrlKey: false, shiftKey: false },
    { key: 'F12',  ctrlKey: true,  shiftKey: false },
    { key: 'i',    ctrlKey: true,  shiftKey: true  },
    { key: 'I',    ctrlKey: true,  shiftKey: true  },
    { key: 'j',    ctrlKey: true,  shiftKey: true  },
    { key: 'J',    ctrlKey: true,  shiftKey: true  },
    { key: 'c',    ctrlKey: true,  shiftKey: true  },
    { key: 'C',    ctrlKey: true,  shiftKey: true  },
    { key: 'u',    ctrlKey: true,  shiftKey: false },
    { key: 'U',    ctrlKey: true,  shiftKey: false },
  ]

  function isBlockedKey(e: KeyboardEvent): boolean {
    return blockedKeys.some(b =>
      e.key === b.key && e.ctrlKey === b.ctrlKey && e.shiftKey === b.shiftKey
    )
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
