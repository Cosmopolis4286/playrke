/**
 * Tipo de movimiento de billetera (catálogo dinámico).
 * Basado en la migración y modelo de MovementType.
 */
export interface MovementType {
    readonly id: string // Snowflake ID
    code: string // Único, formato ^[A-Z_]+$ (DEPOSIT, WITHDRAWAL, BONUS...)
    label: string // Nombre visible es-ES
    description: string | null
    icon: string // Clase FontAwesome, ej: fas fa-arrow-down
    color: string // Hex, ej: #16a34a
    is_credit: boolean // true = suma saldo, false = resta saldo
    requires_approval: boolean // true = flujo PENDING -> approve/reject
    readonly is_system: boolean // true = tipo original protegido (DEPOSIT, WITHDRAWAL, etc)
    status: 'ACTIVE' | 'INACTIVE'
    readonly created_at?: string
    readonly updated_at?: string
}

export interface MovementTypePageProps {
    movementTypes: MovementType[]
    tokenId?: string
}
