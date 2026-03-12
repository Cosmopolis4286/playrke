/**
 * Opción seleccionable dentro de un filtro de tabla.
 */
export interface TableFilterOption {
    /**
     * Valor interno del filtro.
     */
    readonly value: string

    /**
     * Texto visible para el usuario.
     */
    readonly label: string
}

/**
 * Definición de filtro de tabla.
 *
 * El `key` debe corresponder con `Column.key`
 * para que el filtro pueda aplicarse correctamente.
 *
 * @template K Clave de columna asociada al filtro
 */
export interface TableFilterInterface<K extends string = string> {
    /**
     * Clave de la columna que será filtrada.
     */
    readonly key: K

    /**
     * Nombre visible del filtro.
     */
    readonly label: string

    /**
     * Placeholder opcional para el selector.
     */
    readonly placeholder?: string

    /**
     * Opciones disponibles para el filtro.
     */
    readonly options: readonly TableFilterOption[]
}
