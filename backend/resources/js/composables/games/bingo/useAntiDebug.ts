/**
 * Composable para prevenir debugging y acceso a consola en producción
 * 
 * Úsalo en componentes que requieren protección contra inspección
 */
import { onMounted, onUnmounted } from 'vue'

export function useAntiDebug() {
  const isProd = import.meta.env.PROD
  
  // Teclas a bloquear (F12, Ctrl+Shift+I/J/C)
  const blockedKeys = [
    { key: 'F12' },
    { ctrlKey: true, shiftKey: true, key: 'i' },
    { ctrlKey: true, shiftKey: true, key: 'I' },
    { ctrlKey: true, shiftKey: true, key: 'j' },
    { ctrlKey: true, shiftKey: true, key: 'J' },
    { ctrlKey: true, shiftKey: true, key: 'c' },
    { ctrlKey: true, shiftKey: true, key: 'C' },
    { ctrlKey: true, key: 'u' }, // Ver código fuente
    { ctrlKey: true, key: 'U' },
  ]

  function isBlockedKey(e: KeyboardEvent): boolean {
    return blockedKeys.some(blocked => {
      const keyMatch = blocked.key ? e.key === blocked.key : true
      const ctrlMatch = blocked.ctrlKey ? e.ctrlKey === blocked.ctrlKey : true
      const shiftMatch = blocked.shiftKey ? e.shiftKey === blocked.shiftKey : true
      return keyMatch && ctrlMatch && shiftMatch
    })
  }

  function handleKeyDown(e: KeyboardEvent) {
    if (!isProd) return // Solo en producción
    
    if (isBlockedKey(e)) {
      e.preventDefault()
      e.stopPropagation()
      showWarning()
      return false
    }
  }

  function handleContextMenu(e: MouseEvent) {
    if (!isProd) return
    // Permitir clic derecho en inputs/textareas pero no en imágenes
    const target = e.target as HTMLElement
    if (target.tagName === 'IMG' || target.closest('.ig-drawn-board') || target.closest('.ig-cards-section')) {
      e.preventDefault()
      return false
    }
  }

  function showWarning() {
    // Mostrar alerta sutil
    const warning = document.createElement('div')
    warning.className = 'anti-debug-warning'
    warning.textContent = '⚠️ Acción no permitida'
    warning.style.cssText = `
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(255, 107, 107, 0.9);
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      z-index: 99999;
      animation: warning-slide 0.3s ease;
    `
    document.body.appendChild(warning)
    
    setTimeout(() => {
      warning.remove()
    }, 2000)
  }

  // Detección de DevTools abierta
  let devToolsCheck: ReturnType<typeof setInterval> | null = null
  
  function detectDevTools() {
    if (!isProd) return
    
    const threshold = 160
    let lastWidth = window.outerWidth
    let lastHeight = window.outerHeight
    
    devToolsCheck = setInterval(() => {
      const widthDiff = Math.abs(window.outerWidth - window.innerWidth)
      const heightDiff = Math.abs(window.outerHeight - window.innerHeight)
      
      // Si hay una diferencia grande, probablemente DevTools está abierto
      if (widthDiff > threshold || heightDiff > threshold) {
        // Redirigir o mostrar advertencia
        console.clear()
        console.log('%c⚠️', 'font-size: 50px; color: red;')
        console.log('%cAcceso no autorizado detectado', 'font-size: 20px; color: red;')
        
        // Opcional: redirigir o cerrar sesión
        // window.location.href = '/'
      }
    }, 1000)
  }

  onMounted(() => {
    if (!isProd) return
    
    document.addEventListener('keydown', handleKeyDown, true)
    document.addEventListener('contextmenu', handleContextMenu, true)
    detectDevTools()
    
    // Deshabilitar console.* en producción
    const originalLog = console.log
    const originalWarn = console.warn
    const originalError = console.error
    const originalInfo = console.info
    
    console.log = () => {}
    console.warn = () => {}
    console.info = () => {}
    // Mantener console.error para debugging real de errores
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown, true)
    document.removeEventListener('contextmenu', handleContextMenu, true)
    if (devToolsCheck) clearInterval(devToolsCheck)
  })
}
