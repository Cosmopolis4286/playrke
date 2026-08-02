import backendClient from '@/js/plugins/axios'

export interface UserSearchResult {
    value: string
    label: string
}

/**
 * Búsqueda ligera de usuarios (GET /users/search) para selectores async,
 * ej. anfitrión de sala privada de Bingo.
 */
export async function searchUsers(search: string): Promise<UserSearchResult[]> {
    const { data } = await backendClient.get<{ data: UserSearchResult[] }>('/users/search', {
        params: { search }
    })
    return data.data ?? []
}
