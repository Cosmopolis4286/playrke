// js/global/global.ts

import { showToast } from './toastNotify'
import { initLogout } from '../helpers/user/logout'
import { initModalFocus } from '@js/helpers/modal/modals'

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
