/**
 * Estructura para los botones extra que se muestran en la tabla.
 */
export interface ExtraButton {
    /** Texto visible en el botón */
    label?: string
    /** Clases CSS para ícono (FontAwesome, etc.) */
    icon?: string
    /** Clases CSS para el botón (ej: 'btn-info') */
    class?: string
    /**
     * Controla si el botón se muestra.
     * Puede ser booleano o función que recibe IDs seleccionados.
     */
    show?: boolean | ((selectedIds: string[]) => boolean)
    /**
     * Controla si el botón está deshabilitado.
     * Recibe IDs seleccionados y retorna booleano.
     */
    disabled?: (selectedIds: string[]) => boolean
    /**
     * Razón por la cual el botón está deshabilitado.
     * Se muestra en un tooltip cuando el botón está deshabilitado.
     * Puede ser un string o una función que recibe IDs seleccionados.
     */
    disabledReason?: string | ((selectedIds: string[]) => string)
    /** Nombre de la acción que realizará el botón */
    action?: string
    /**
     * Función que se ejecuta al hacer click en el botón.
     * Recibe los IDs seleccionados.
     */
    onClick?: (selectedIds: string[]) => void
}
