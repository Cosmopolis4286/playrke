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
    /**
     * Oculta esta columna (header + celdas) en pantallas MENORES al
     * breakpoint indicado, vía las utilidades Bootstrap `d-none d-{bp}-table-cell`
     * ya presentes en el proyecto — mismo criterio que `hidden {bp}:table-cell`
     * de Tailwind en las tablas de Trivia, para no perder ese responsive al
     * migrar una tabla a BaseTable.
     */
    readonly hideBelow?: 'sm' | 'md' | 'lg' | 'xl'
}

/**
 * Tipado de una fila con id requerido
 */
export interface Row {
    readonly id: string | number
    [key: string]: any
}
