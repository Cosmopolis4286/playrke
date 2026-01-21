// backend/resources/js/composables/useFormCrud.ts

import { onMounted, onBeforeUnmount } from 'vue'
import { submitForm } from '@/js/helpers/form/services/formSubmitService'
import { formCrudState as crud } from '@/js/helpers/form/state/formCrudState'
import { executeCrud } from '@/js/helpers/form/services/crudExecutor'
import type { TableCrudEventDetail } from '@js/helpers/table/types'
import { showPasswordModal } from '@/js/helpers/user/password'

/**
 * Composable para manejar eventos CRUD globales.
 *
 * Responsabilidades:
 * - Escuchar clicks globales para acciones rápidas y confirmaciones.
 * - Escuchar eventos personalizados `table:crud` para acciones CRUD delegadas.
 * - Delegar la ejecución de la lógica CRUD a servicios especializados.
 *
 * Uso:
 * Llamar dentro del `setup()` en el componente raíz o donde se necesite capturar eventos globales CRUD.
 */
export function useFormCrud() {
    /**
     * Manejador global de eventos click.
     * Implementa delegación de eventos para capturar acciones CRUD y utilidades.
     *
     * @param {MouseEvent} e - Evento click
     */
    function handleCrudClick(e: MouseEvent): void {
        const target = e.target as HTMLElement | null
        if (!target) return

        // Acción rápida: Confirmar formulario
        if (target.closest('#btn-confirm')) {
            if (!crud.moduleId) return

            submitForm<{ password?: string }>(crud.moduleId).then(async (data) => {
                // Si la respuesta contiene contraseña, mostrar modal para confirmar
                const passwordResponse = data?.password ? { data: { password: data.password } } : null

                let timeout: number | undefined

                if (passwordResponse?.data?.password) {
                    await showPasswordModal(passwordResponse.data.password)
                    timeout = 2000
                }

                // Recargar página tras el timeout o inmediatamente
                setTimeout(() => window.location.reload(), timeout ?? 1500)
            })
        }

        // Acción rápida: Toggle popover (requiere jQuery)
        if (target.closest('.popover-dismiss')) {
            // Asume jQuery está cargado en el proyecto
            $(target).popover('toggle')
        }
    }

    /**
     * Manejador para eventos custom `table:crud` disparados desde tablas.
     * Recibe detalles con la acción y posibles IDs encriptados.
     *
     * @param {Event} event - Evento custom
     */
    function handleCrudCustomEvent(event: Event): void {
        const ev = event as CustomEvent<TableCrudEventDetail>
        if (!ev.detail) return

        const { action, encryptIds } = ev.detail

        // Ejecuta la acción CRUD correspondiente con los IDs si los hay
        executeCrud(action, encryptIds)
    }

    onMounted(() => {
        // Se agregan listeners globales al montar el componente
        document.addEventListener('click', handleCrudClick)
        window.addEventListener('table:crud', handleCrudCustomEvent)

        if (import.meta.env.DEV) {
            console.info('[useFormCrud] inicializado')
        }
    })

    onBeforeUnmount(() => {
        // Limpieza de listeners al desmontar el componente para evitar leaks
        document.removeEventListener('click', handleCrudClick)
        window.removeEventListener('table:crud', handleCrudCustomEvent)
    })
}
