/**
 * Información de una columna
 */
export interface Column {
    readonly key: string
    readonly label: string
    readonly sortable?: boolean
    readonly html?: boolean
    /** Trunca el contenido con ellipsis y muestra el valor completo en un title/tooltip. */
    readonly truncate?: boolean
    /**
     * Si es false, esta columna se excluye de la búsqueda global de texto.
     * Útil para columnas con HTML/badges donde el valor crudo no es buscable
     * de forma útil. Por defecto true (comportamiento previo).
     */
    readonly searchable?: boolean
}

/**
 * Tipado de una fila con id requerido
 */
export interface Row {
    readonly id: string | number
    [key: string]: any
}
