/** Ficha de autogestión del propio comercio (GET/PUT /commerce/profile). */
export interface CommerceProfile {
    id: string
    name: string
    phone: string
    status: string
    status_badge: string
    country: string | null
    user: { username: string; email: string }
}

export interface CommerceProfilePageProps {
    commerce: CommerceProfile
}
