import type { Administrator } from './administrator'
import type { Country } from '../../country'
import type { Column } from '../../common/table/column-row'
import type { Pagination } from '../../common/table/pagination'

export interface AdminAdministratorPageProps {
    admins: {
        columns: Column[]
        rows: Administrator[]
        pagination: Pagination | null
    }
    countries: Country[]
    roles: { id: string; name: string; prefix: string | null }[]
}
