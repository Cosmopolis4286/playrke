// backend/resources/js/helpers/form/controller/formCrudController.ts

import { submitForm } from '../services/formSubmitService'
import { formCrudState as crud } from '../state/formCrudState'
import { executeCrud } from '../services/crudExecutor'
import type { TableCrudEventDetail } from '@js/helpers/table/types'
import { showPasswordModal } from '@js/helpers/user/password'

/**
 * Controlador central de eventos CRUD.
 *
 * Responsabilidades:
 * - Escuchar interacciones globales de UI (clicks)
 * - Escuchar eventos CustomEvent provenientes de tablas (Vue / Alpine)
 * - Delegar la ejecución de lógica CRUD a servicios especializados
 *
 * ❌ No inicializa módulos
 * ❌ No maneja estado de negocio
 */
let initialized = false

export function initFormCrudController(): void {
  if (initialized) return
  initialized = true

  document.addEventListener('click', handleCrudClick)
  window.addEventListener('table:crud', handleCrudCustomEvent)

  if (import.meta.env.DEV) {
    console.info('[FormCrudController] inicializado')
  }
}

/**
 * Handler global de clicks.
 * Implementa event delegation para acciones CRUD y utilidades rápidas.
 *
 * @param e - MouseEvent del DOM
 */
async function handleCrudClick(e: MouseEvent): Promise<void> {
  const target = e.target as HTMLElement | null
  if (!target) return

  /* ───────── Acciones rápidas ───────── */

  if (target.closest('#btn-confirm')) {
    if (!crud.moduleId) return

    const data = await submitForm<{ password?: string }>(crud.moduleId)

    const passwordResponse = data?.password ? { data: { password: data.password } } : null

    let timeout: number | undefined

    if (passwordResponse?.data?.password) {
      await showPasswordModal(passwordResponse.data.password)
      timeout = 2000
    }

    setTimeout(() => window.location.reload(), timeout ?? 1500)
  }

  if (target.closest('.popover-dismiss')) {
    $(target).popover('toggle')
    return
  }
}

/**
 * Handler para eventos CustomEvent provenientes de tablas
 * (Vue, Alpine u otros adapters).
 *
 * Espera un detail con al menos:
 * - action: string
 *
 * @param e - Evento CustomEvent
 */
function handleCrudCustomEvent(event: Event): void {
  const ev = event as CustomEvent<TableCrudEventDetail>
  if (!ev.detail) return

  const { action, encryptIds } = ev.detail

  executeCrud(action, encryptIds)
}
