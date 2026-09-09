import type { SellerSalesRow } from '@/js/types/roles/commerce/dashboard/dashboard-commerce'

export interface EconomyByCurrency {
    currency_id: string
    iso_code:    string
    symbol:      string
    income:      number
    commission:  number
    prize_pool:  number
}

export interface EconomyTotals {
    total_income:     number
    total_commission: number
    total_prize_pool: number
}

export interface CommerceEconomySummary {
    by_currency: EconomyByCurrency[]
    by_seller:   SellerSalesRow[]
    totals:      EconomyTotals
}

export interface EconomySummaryFilters {
    start_date: string | null
    end_date:   string | null
}

export interface EconomySummaryPageProps {
    title:   string
    summary: CommerceEconomySummary
    filters: EconomySummaryFilters
}
