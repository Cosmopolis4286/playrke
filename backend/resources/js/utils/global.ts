// js/global/global.ts

import { createApp } from 'vue'
import { showToast } from './toastNotify'
import { initLogout } from '../helpers/user/logout'
import { initModalFocus } from '@js/helpers/modal/modals'
import ScrollToTop from '@js/Components/ui/ScrollToTop.vue'
import LiveWinsTicker from '@js/Components/Common/Platform/LiveWinsTicker.vue'

/**
 * Inicializa el bloqueo de arrastre de imágenes
 * Previene que las imágenes sean arrastradas accidentalmente.
 */
async function initDragStart(): Promise<void> {
  document.addEventListener('dragstart', event => {
    if ((event.target as HTMLElement)?.tagName === 'IMG') {
      event.preventDefault()
    }
  })
}

/**
 * Muestra los mensajes de error generados por Laravel
 * @param errors Array de mensajes de error
 */
async function showLaravelMessages(errors: string[] = []): Promise<void> {
  if (!errors.length) return

  for (const msg of errors) {
    try {
      showToast(msg, 'error')
    } catch (err) {
      console.error('❌ Error mostrando toast de error:', err)
    }
  }

  if (import.meta.env.DEV) {
    console.log(`✅ ${errors.length} LaravelErrors mostrados`)
  }
}

/**
 * Monta el botón ScrollToTop en un div dedicado al final del body.
 * Se omite si el elemento ya existe (protección ante doble llamada).
 */
function mountScrollToTop(): void {
  const existingId = 'scroll-to-top-app'
  if (document.getElementById(existingId)) return

  const el = document.createElement('div')
  el.id = existingId
  document.body.appendChild(el)
  createApp(ScrollToTop).mount(el)
}

/**
 * Monta el ticker de ganadores en vivo debajo del navbar.
 * Solo existe en el layout autenticado (auth-layout.blade.php).
 */
function mountLiveWinsTicker(): void {
  const el = document.getElementById('live-wins-ticker')
  if (!el) return

  createApp(LiveWinsTicker).mount(el)
}

/**
 * Inicializa la aplicación globalmente
 */
export async function initGlobal(): Promise<void> {
  try {
    // 🔹 Logout primero por seguridad
    initLogout()

    // 🔹 Bloquear arrastre de imágenes
    await initDragStart()

    // 🔹 Inicializar modales accesibles
    await initModalFocus()

    // 🔹 Botón volver al inicio
    mountScrollToTop()

    // 🔹 Ticker de ganadores en vivo
    mountLiveWinsTicker()

    // 🔹 Mostrar mensajes flash de sesión
    if (window.Session?.message?.trim()) {
      showToast(window.Session.message, window.Session.type || 'success')
    }

    // 🔹 Mostrar errores de Laravel (validación)
    if (window.LaravelErrors?.length) {
      await showLaravelMessages(window.LaravelErrors)
    }

    if (import.meta.env.DEV) {
      console.log('✅ initGlobal completado')
    }
  } catch (err) {
    console.error('❌ Error en initGlobal:', err)
  }
}

/**
 * Inicialización segura con valores por defecto
 */
export async function initGlobalSafe(): Promise<void> {
  if (import.meta.env.DEV) {
    console.log('initGlobalSafe iniciado')
  }
  await initGlobal()
}
