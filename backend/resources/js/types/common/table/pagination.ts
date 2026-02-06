export interface Pagination {
    readonly total: number
    readonly per_page: number
    readonly current_page: number
    readonly last_page: number
    readonly from: number | null
    readonly to: number | null
}
