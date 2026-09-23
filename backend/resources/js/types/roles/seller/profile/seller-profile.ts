/** Ficha de autogestión del propio vendedor (GET/PUT /seller/profile). */
export interface SellerProfileReportsTo {
    name: string
    type: 'subsidiary' | 'commerce'
    commerce_name: string | null
}

export interface SellerProfile {
    id: string
    name: string
    phone: string
    status: string
    status_badge: string
    country: string | null
    reports_to: SellerProfileReportsTo
    user: { username: string; email: string }
}

export interface SellerProfilePageProps {
    seller: SellerProfile
    /** true si un admin/superadmin está viendo la ficha de otro vendedor (ver AdminProfileAccessService). */
    isAdminView?: boolean
}
