import { readonly } from "vue"

export interface Commerce {
    readonly id?: number | string,
    readonly name: string,
    readonly country_id: number,
    readonly time_zone_id: number,
    readonly currency_id: number,
    readonly phone: string,
    readonly username: string,
    readonly email: string
    readonly other_currencies?: [],
    readonly allowed_games?: []
}
