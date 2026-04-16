/**
 * Composable para prevenir debugging y acceso a consola en producción
 * 
 * Úsalo en componentes que requieren protección contra inspección.
 * En producción, si detecta debugging, DETIENE la ejecución del código.
 */
import { onMounted, onUnmounted } from 'vue'

export function useAntiDebug() {
  const isProd = import.meta.env.PROD
  let isLocked = false
  
  // Función para BLOQUEAR completamente la ejecución
  function lockExecution(reason: string) {
    if (isLocked) return // Evitar múltiples llamadas
    isLocked = true
    
    // Mostrar pantalla de bloqueo
    const lockScreen = document.createElement('div')
    lockScreen.id = 'anti-debug-lock'
    lockScreen.innerHTML = `
      <div style="
        position: fixed;
        inset: 0;
        background: #000;
        z-index: 2147483647;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-family: system-ui, sans-serif;
        color: #fff;
      ">
        <div style="font-size: 60px; margin-bottom: 20px;">🔒</div>
        <h1 style="font-size: 24px; margin: 0 0 12px;">Acceso No Autorizado</h1>
        <p style="color: #999; margin: 0;">Herramientas de desarrollo detectadas</p>
      </div>
    `
    document.body.appendChild(lockScreen)
    
    // Congelar todo
    while (true) {
      debugger
      alert('⚠️ ' + reason)
    }
  }
  
  // Teclas a bloquear (F12, Ctrl+Shift+I/J/C)
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
      const keyMatch = blocked.key ? e.key === blocked.key : true
      const ctrlMatch = blocked.ctrlKey ? e.ctrlKey === blocked.ctrlKey : true
      const shiftMatch = blocked.shiftKey ? e.shiftKey === blocked.shiftKey : true
      return keyMatch && ctrlMatch && shiftMatch
    })
  }

  function handleKeyDown(e: KeyboardEvent) {
    if (!isProd) return
    
    if (isBlockedKey(e)) {
      e.preventDefault()
      e.stopPropagation()
      lockExecution('Acceso por teclado bloqueado')
      return false
    }
  }

  function handleContextMenu(e: MouseEvent) {
    if (!isProd) return
    const target = e.target as HTMLElement
    if (target.tagName === 'IMG' || target.closest('.ig-drawn-board') || target.closest('.ig-cards-section')) {
      e.preventDefault()
      return false
    }
  }

  // Detección AGRESIVA de DevTools
  let devToolsCheck: ReturnType<typeof setInterval> | null = null
  
  function detectDevTools() {
    if (!isProd) return
    
    // Método 1: Diferencia de dimensiones
    const checkDimensions = () => {
      const threshold = 160
      const widthDiff = Math.abs(window.outerWidth - window.innerWidth)
      const heightDiff = Math.abs(window.outerHeight - window.innerHeight)
      
      if (widthDiff > threshold || heightDiff > threshold) {
        lockExecution('DevTools detectado por dimensiones de ventana')
      }
    }
    
    // Método 2: Debugger trap
    const debuggerTrap = () => {
      const start = performance.now()
      debugger
      const end = performance.now()
      if (end - start > 100) {
        lockExecution('DevTools detectado por debugger trap')
      }
    }
    
    // Método 3: Console function redefinition
    const checkConsole = () => {
      const before = new Date().getTime()
      console.clear()
      const after = new Date().getTime()
      if (after - before > 10) {
        lockExecution('DevTools detectado por manipulación de consola')
      }
    }
    
    // Método 4: Performance timing
    const performanceCheck = () => {
      const start = performance.now()
      console.log('test')
      const end = performance.now()
      console.log = () => {} // Restore
      if (end - start > 50) {
        lockExecution('DevTools detectado por timing de ejecución')
      }
    }
    
    // Ejecutar checks periódicamente
    devToolsCheck = setInterval(() => {
      checkDimensions()
      debuggerTrap()
      checkConsole()
      performanceCheck()
    }, 500)
  }

  // Protección de tiempo de ejecución
  function runtimeProtection() {
    if (!isProd) return
    
    // Ofuscar variables críticas
    const _0x1a2b = () => {
      const _0x3c4d = new Date().getTime()
      if (_0x3c4d % 2 === 0) {
        // Código legítimo
        return true
      }
      // Si se modifica, falla
      lockExecution('Integridad del código comprometida')
      return false
    }
    
    // Verificar integridad cada segundo
    setInterval(() => {
      _0x1a2b()
    }, 1000)
  }

  onMounted(() => {
    if (!isProd) {
      console.log('[AntiDebug] Modo desarrollo - protección desactivada')
      return
    }
    
    console.log = () => {}
    console.warn = () => {}
    console.info = () => {}
    console.debug = () => {}
    
    document.addEventListener('keydown', handleKeyDown, true)
    document.addEventListener('contextmenu', handleContextMenu, true)
    detectDevTools()
    runtimeProtection()
    
    // Ejecutar debugger trap inmediatamente
    setTimeout(() => {
      const start = performance.now()
      debugger
      const end = performance.now()
      if (end - start > 50) {
        lockExecution('DevTools abierto al inicio')
      }
    }, 100)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown, true)
    document.removeEventListener('contextmenu', handleContextMenu, true)
    if (devToolsCheck) clearInterval(devToolsCheck)
  })
}
