/** Ficha de autogestión de la propia agencia (GET/PUT /subsidiary/profile). */
export interface SubsidiaryProfile {
    id: string
    name: string
    phone: string
    brand: string | null
    product: string | null
    status: string
    status_badge: string
    commerce: { name: string; status: string } | null
    country: string | null
    currency: string | null
    user: { username: string; email: string }
}

export interface SubsidiaryProfilePageProps {
    subsidiary: SubsidiaryProfile
}
