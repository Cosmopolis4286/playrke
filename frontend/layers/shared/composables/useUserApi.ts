/**
 * Composable para API de Usuarios
 *
 * Proporciona funciones para gestión de usuarios, perfiles y roles.
 */

export interface UserData {
  id: string
  username: string
  first_name: string
  last_name: string
  email: string
  roles: string[]
  permissions: string[]
  status: 'active' | 'inactive' | 'banned'
  created_at: string
  updated_at: string
}

export interface UpdateUserData {
  first_name?: string
  last_name?: string
  email?: string
  phone?: string
  country?: string
}

export interface UpdatePasswordData {
  current_password: string
  password: string
  password_confirmation: string
}

export const useUserApi = () => {
  const api = useApi()

  /**
   * Obtiene la lista de usuarios (admin)
   */
  const getUsers = async (params?: {
    role?: string
    status?: string
    search?: string
    page?: number
    perPage?: number
  }) => {
    const query = new URLSearchParams()
    if (params?.role) query.append('role', params.role)
    if (params?.status) query.append('status', params.status)
    if (params?.search) query.append('search', params.search)
    if (params?.page) query.append('page', params.page.toString())
    if (params?.perPage) query.append('perPage', params.perPage.toString())

    return api.get<{ data: UserData[]; meta: any }>(`/users?${query.toString()}`)
  }

  /**
   * Obtiene un usuario específico
   */
  const getUser = async (userId: string) => {
    return api.get<{ data: UserData }>(`/users/${userId}`)
  }

  /**
   * Crea un nuevo usuario (admin)
   */
  const createUser = async (userData: Partial<UserData>) => {
    return api.post<{ data: UserData }>('/users', userData)
  }

  /**
   * Actualiza un usuario
   */
  const updateUser = async (userId: string, userData: UpdateUserData) => {
    return api.put<{ data: UserData }>(`/users/${userId}`, userData)
  }

  /**
   * Elimina un usuario (admin)
   */
  const deleteUser = async (userId: string) => {
    return api.delete(`/users/${userId}`)
  }

  /**
   * Actualiza la contraseña del usuario actual
   */
  const updatePassword = async (passwordData: UpdatePasswordData) => {
    return api.put('/user/password', passwordData)
  }

  /**
   * Actualiza el estado de un usuario (admin)
   */
  const updateUserStatus = async (userId: string, status: 'active' | 'inactive' | 'banned') => {
    return api.put(`/users/${userId}/status`, { status })
  }

  /**
   * Asigna roles a un usuario (admin)
   */
  const assignRoles = async (userId: string, roles: string[]) => {
    return api.post(`/users/${userId}/roles`, { roles })
  }

  /**
   * Obtiene el perfil del usuario actual
   */
  const getProfile = async () => {
    return api.get<{ data: UserData }>('/user/profile')
  }

  /**
   * Actualiza el perfil del usuario actual
   */
  const updateProfile = async (profileData: UpdateUserData) => {
    return api.put<{ data: UserData }>('/user/profile', profileData)
  }

  /**
   * Sube un avatar
   */
  const uploadAvatar = async (file: File) => {
    const formData = new FormData()
    formData.append('avatar', file)

    return api.post<{ data: { avatar_url: string } }>('/user/avatar', formData)
  }

  return {
    getUsers,
    getUser,
    createUser,
    updateUser,
    deleteUser,
    updatePassword,
    updateUserStatus,
    assignRoles,
    getProfile,
    updateProfile,
    uploadAvatar
  }
}
