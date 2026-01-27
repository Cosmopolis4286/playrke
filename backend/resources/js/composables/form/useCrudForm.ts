import { formCrudState as crud } from '../state/formCrudState'
import { resetForm } from '../formHelpers'
import { getInfo } from '../services/getInfoService'
import { buildRoute, buildParamsForSinglePlaceholder } from '@js/routes/helper'

/**
 * Composable para preparar y gestionar formularios CRUD
 * asociados a un modal.
 *
 * Responsabilidades:
 * - Inicializar y limpiar el formulario
 * - Construir rutas dinámicas según acción
 * - Preparar estados de CREATE / EDIT
 * - Mostrar modal cuando corresponde
 */
export function useCrudForm() {
    /* ==========================================================================
     * Helpers internos
     * ========================================================================== */

    /**
     * Inicializa el formulario del módulo actual
     */
    function initForm(): HTMLFormElement | null {
        if (!crud.moduleId) return null

        const form = document.querySelector<HTMLFormElement>(`#${crud.moduleId}`)
        if (!form) return null

        resetForm(form)
        return form
    }

    /**
     * Actualiza el título del modal
     */
    function setModalLabel(title: string): void {
        const label = document.querySelector(`#modal-${crud.moduleId}-label`)
        if (label) label.textContent = title
    }

    /* ==========================================================================
     * Preparación del formulario
     * ========================================================================== */

    /**
     * Configura el formulario antes del submit
     *
     * @param form - Formulario HTML
     * @param routeName - Nombre de la ruta (store, update, etc.)
     * @param method - Método HTTP
     * @param id - ID cifrado (opcional)
     */
    function prepareForm(
        form: HTMLFormElement,
        routeName: string,
        method: string = 'POST',
        id?: string
    ): void {
        const methodUpper = method.toUpperCase()

        try {
            const params = buildParamsForSinglePlaceholder(crud.routes, routeName, id)
            const requiresId = ['PUT', 'PATCH', 'DELETE'].includes(methodUpper)

            if (requiresId && !id) {
                throw new Error(`'id' requerido para ${methodUpper}`)
            }

            const actionUrl = requiresId
                ? buildRoute(crud.routes, routeName, params)
                : buildRoute(crud.routes, routeName)

            form.action = actionUrl
            form.method = methodUpper
        } catch (error) {
            console.error(`Error generando la ruta para '${routeName}':`, error)
        }

        // Limpia métodos previos
        form.querySelectorAll('input[name="_method"]').forEach(i => i.remove())

        if (!['POST', 'GET'].includes(methodUpper)) {
            const hidden = document.createElement('input')
            hidden.type = 'hidden'
            hidden.name = '_method'
            hidden.value = methodUpper
            form.appendChild(hidden)
        }

        // CSRF token
        if (!form.querySelector('input[name="_token"]')) {
            const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            if (meta?.content) {
                const token = document.createElement('input')
                token.type = 'hidden'
                token.name = '_token'
                token.value = meta.content
                form.appendChild(token)
            } else {
                console.warn('Token CSRF no encontrado en meta.')
            }
        }
    }

    /* ==========================================================================
     * Acciones públicas CRUD
     * ========================================================================== */

    /**
     * Prepara el formulario para crear un nuevo registro
     */
    function prepareCreate(): void {
        const form = initForm()
        if (!form) return

        setModalLabel(`Registrar ${crud.formTitleSuffix}`)
        prepareForm(form, 'store', 'POST')

        crud.insertInfo?.('create')
        $(crud.modalId!).modal('show')
    }

    /**
     * Prepara el formulario para editar un registro existente
     *
     * @param encryptId - ID cifrado del registro
     */
    async function prepareEdit(encryptId: string): Promise<void> {
        const form = initForm()
        if (!form) return

        setModalLabel(`Editar ${crud.formTitleSuffix}`)
        prepareForm(form, 'update', 'PUT', encryptId)

        const info = await getInfo(encryptId)
        if (info) {
            $(crud.modalId!).modal('show')
        }
    }

    /* ==========================================================================
     * API pública del composable
     * ========================================================================== */

    return {
        prepareCreate,
        prepareEdit,
        prepareForm
    }
}
