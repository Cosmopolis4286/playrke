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
 * Tipado de una fila
 */
export type Row = Record<string, any> | string;
