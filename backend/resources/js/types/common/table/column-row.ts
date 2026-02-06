/**
 * Información de una columna
 */
export interface Column {
    readonly key: string
    readonly label: string
    readonly sortable?: boolean
    readonly html?: boolean
}

/**
 * Tipado de una fila con id requerido
 */
export interface Row {
    readonly id: string
    [key: string]: any
}
