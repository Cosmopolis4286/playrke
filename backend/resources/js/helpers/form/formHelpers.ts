// helpers/form/formHelpers.ts

/**
 * Llena un formulario con valores de un objeto,
 * mapeando IDs con "-" a propiedades con "_".
 * Permite override explícito usando data-bind.
 *
 * Soporta:
 * - input (text, number, date, checkbox, radio)
 * - textarea
 * - select (single y multiple)
 *
 * @param data - Objeto con los datos (ej: commerce)
 * @param root - Elemento raíz del formulario (opcional)
 */
export function fillFormFromObject<T extends Record<string, unknown>>(
  data: T,
  root: HTMLElement | Document = document
): void {
  root
    .querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('[id]')
    .forEach(el => {
      const prop = el.dataset.bind ?? el.id.replace(/-/g, '_')

      if (!(prop in data)) return

      const value = data[prop]

      // ======================
      // INPUT
      // ======================
      if (el instanceof HTMLInputElement) {
        switch (el.type) {
          case 'checkbox':
            el.checked = Boolean(value)
            break

          case 'radio':
            el.checked = el.value === String(value)
            break

          default:
            el.value = value != null ? String(value) : ''
        }
        return
      }

      // ======================
      // TEXTAREA
      // ======================
      if (el instanceof HTMLTextAreaElement) {
        el.value = value != null ? String(value) : ''
        return
      }

      // ======================
      // SELECT
      // ======================
      if (el instanceof HTMLSelectElement) {
        if (el.multiple && Array.isArray(value)) {
          Array.from(el.options).forEach(option => {
            option.selected = value.includes(option.value)
          })
        } else {
          el.value = value != null ? String(value) : ''
        }
      }
    })
}

/**
 * Limpia los errores de validación del formulario.
 * Compatible con validaciones nativas, Bootstrap y APIs backend.
 *
 * @param formId - ID del formulario a limpiar
 */
export function clearValidationErrors(formId: string): void {
  const form = document.getElementById(formId)
  if (!form) return

  // Remover estado de validación
  form.classList.remove('was-validated')

  // Remover clases inválidas
  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'))

  // Remover mensajes de error
  form.querySelectorAll('.invalid-feedback').forEach(el => el.remove())
}

/**
 * Resetea el formulario y oculta contenedores dinámicos.
 * Maneja correctamente formularios con Select2.
 *
 * @param formId - ID del formulario a resetear
 * @param containerIds - IDs de contenedores a ocultar (opcional)
 */
export function resetFormAndContainers(formId: string, containerIds: string[] = []): void {
  const form = document.getElementById(formId) as HTMLFormElement | null
  if (!form) return

  // ======================
  // Reset nativo
  // ======================
  form.reset()

  // ======================
  // Reset Select2
  // ======================
  form.querySelectorAll<HTMLSelectElement>('select').forEach(select => {
    if (window.jQuery && (window.jQuery as any)(select).data('select2')) {
      ;(window.jQuery as any)(select).val(null).trigger('change')
    }
  })

  // ======================
  // Limpiar selects explícitos
  // ======================
  form.querySelectorAll<HTMLSelectElement>('select[data-reset="true"]').forEach(select => {
    select.innerHTML = ''
  })

  // ======================
  // Ocultar contenedores dinámicos
  // ======================
  containerIds.forEach(containerId => {
    document.getElementById(containerId)?.classList.add('d-none')
  })
}

/**
 * Resetea un formulario usando la utilidad compartida.
 * Opcionalmente remueve el atributo readonly (uso típico en CRUD).
 *
 * @param form - El formulario a resetear
 * @param options - Configuración adicional
 */
export function resetForm(
  form: HTMLFormElement,
  options: { removeReadonly?: boolean } = { removeReadonly: true }
): void {
  if (!form.id) {
    console.warn('El formulario no tiene ID, no se puede resetear')
    return
  }

  resetFormAndContainers(form.id)

  if (options.removeReadonly) {
    form.querySelectorAll<HTMLInputElement>('input[readonly]').forEach(input => {
      input.readOnly = false
    })
  }
}
