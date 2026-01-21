/**
 * Opción seleccionable en un filtro
 */
export interface TableFilterOption {
    readonly value: string
    readonly label: string
}

/**
 * Definición de filtro de tabla
 *
 * key debe corresponder con Column.key
 */
export interface TableFilter<K extends string = string> {
    readonly key: K
    readonly label: string
    readonly placeholder?: string
    readonly options: readonly TableFilterOption[]
}
