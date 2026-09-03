import type { Currency } from '../../currency'
import type { Country } from '../../country'
import type { Subsidiary } from '../subsidiary/subsidiary'
import type { Column } from '../../common/table/column-row'
import type { Pagination } from '../../common/table/pagination'

/** Comercio padre para el select de la sucursal — subset liviano, no el Commerce completo. */
export interface SubsidiaryCommerceOption {
    id: string
    name: string
    /** Ausente cuando el actor es 'commerce' (solo se lista a sí mismo). Presente para admin, que ve todos los comercios sin filtrar por status. */
    status?: string
}

export interface AdminSubsidiaryPageProps {
    subsidiaries: {
        columns: Column[],
        rows: Subsidiary[]
    },
    subsidiaries_pagination: Pagination | null,
    commerces: SubsidiaryCommerceOption[],
    countries: Country[],
    currencies: Currency[],
    /** Id de la sucursal a abrir automáticamente (llegada desde el botón "Ver más" de Admin > Usuarios). */
    openId?: string | number | null
}
