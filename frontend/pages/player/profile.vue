<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-900 via-pink-900 to-red-900">
    <!-- Header -->
    <header class="bg-black/30 backdrop-blur-md border-b border-white/10">
      <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <h1 class="text-2xl font-bold text-white">Mi Perfil</h1>
          <NuxtLink to="/player/lobby" class="text-white hover:text-gray-300 transition">
            <Icon name="lucide:arrow-left" class="h-6 w-6 inline mr-2" />
            Volver al Lobby
          </NuxtLink>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar: Avatar y stats -->
        <div class="lg:col-span-1">
          <!-- Avatar -->
          <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-6">
            <div class="text-center">
              <div class="relative inline-block">
                <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-4xl font-bold mx-auto mb-4">
                  {{ initials }}
                </div>
                <button
                  class="absolute bottom-2 right-2 w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded-full flex items-center justify-center text-white transition"
                  @click="showAvatarUpload = true"
                >
                  <Icon name="lucide:camera" class="h-5 w-5" />
                </button>
              </div>
              <h2 class="text-2xl font-bold text-white mb-1">
                {{ user?.first_name }} {{ user?.last_name }}
              </h2>
              <p class="text-gray-300">@{{ user?.username }}</p>
            </div>
          </div>

          <!-- Stats -->
          <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
            <h3 class="text-white font-semibold mb-4">Estadísticas</h3>
            <div class="space-y-4">
              <div>
                <div class="flex justify-between text-sm text-gray-300 mb-1">
                  <span>Nivel</span>
                  <span class="text-yellow-400 font-bold">15</span>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                  <div class="bg-gradient-to-r from-yellow-400 to-orange-500 h-2 rounded-full" style="width: 65%"></div>
                </div>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-300 text-sm">Partidas jugadas</span>
                <span class="text-white font-semibold">{{ stats.gamesPlayed }}</span>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-300 text-sm">Partidas ganadas</span>
                <span class="text-green-400 font-semibold">{{ stats.gamesWon }}</span>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-300 text-sm">Tasa de victoria</span>
                <span class="text-blue-400 font-semibold">{{ winRate }}%</span>
              </div>

              <div class="flex justify-between">
                <span class="text-gray-300 text-sm">Ganancias totales</span>
                <span class="text-yellow-400 font-semibold">${{ stats.totalWinnings }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Main content: Información del perfil -->
        <div class="lg:col-span-2">
          <!-- Tabs -->
          <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
            <div class="flex border-b border-white/10">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                :class="[
                  'flex-1 py-4 px-6 text-sm font-medium transition',
                  activeTab === tab.id
                    ? 'bg-white/10 text-white border-b-2 border-blue-500'
                    : 'text-gray-300 hover:bg-white/5'
                ]"
                @click="activeTab = tab.id"
              >
                {{ tab.label }}
              </button>
            </div>

            <!-- Tab: Información Personal -->
            <div v-show="activeTab === 'info'" class="p-6">
              <form @submit.prevent="updateProfile" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Nombre -->
                  <div>
                    <label class="block text-sm font-medium text-gray-200 mb-2">
                      Nombre
                    </label>
                    <input
                      v-model="profileForm.first_name"
                      type="text"
                      class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                  </div>

                  <!-- Apellido -->
                  <div>
                    <label class="block text-sm font-medium text-gray-200 mb-2">
                      Apellido
                    </label>
                    <input
                      v-model="profileForm.last_name"
                      type="text"
                      class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                  </div>

                  <!-- Email -->
                  <div>
                    <label class="block text-sm font-medium text-gray-200 mb-2">
                      Email
                    </label>
                    <input
                      v-model="profileForm.email"
                      type="email"
                      class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                  </div>

                  <!-- Username (readonly) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-200 mb-2">
                      Nombre de usuario
                    </label>
                    <input
                      :value="user?.username"
                      type="text"
                      disabled
                      class="w-full px-4 py-2 bg-gray-700/50 border border-gray-600 rounded-lg text-gray-400 cursor-not-allowed"
                    >
                  </div>
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-3 pt-4">
                  <button
                    type="button"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition"
                    @click="resetForm"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    :disabled="isSaving"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50"
                  >
                    <Icon v-if="isSaving" name="lucide:loader-2" class="inline h-4 w-4 mr-2 animate-spin" />
                    {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Tab: Seguridad -->
            <div v-show="activeTab === 'security'" class="p-6">
              <form @submit.prevent="updatePassword" class="space-y-4">
                <!-- Contraseña actual -->
                <div>
                  <label class="block text-sm font-medium text-gray-200 mb-2">
                    Contraseña actual
                  </label>
                  <input
                    v-model="passwordForm.current_password"
                    type="password"
                    class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                </div>

                <!-- Nueva contraseña -->
                <div>
                  <label class="block text-sm font-medium text-gray-200 mb-2">
                    Nueva contraseña
                  </label>
                  <input
                    v-model="passwordForm.password"
                    type="password"
                    class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                </div>

                <!-- Confirmar contraseña -->
                <div>
                  <label class="block text-sm font-medium text-gray-200 mb-2">
                    Confirmar nueva contraseña
                  </label>
                  <input
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-3 pt-4">
                  <button
                    type="button"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition"
                    @click="resetPasswordForm"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    :disabled="isChangingPassword"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50"
                  >
                    <Icon v-if="isChangingPassword" name="lucide:loader-2" class="inline h-4 w-4 mr-2 animate-spin" />
                    {{ isChangingPassword ? 'Actualizando...' : 'Cambiar Contraseña' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Tab: Preferencias -->
            <div v-show="activeTab === 'preferences'" class="p-6">
              <div class="space-y-6">
                <!-- Notificaciones -->
                <div>
                  <h4 class="text-white font-semibold mb-3">Notificaciones</h4>
                  <div class="space-y-3">
                    <label class="flex items-center">
                      <input
                        v-model="preferences.emailNotifications"
                        type="checkbox"
                        class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-600 rounded bg-gray-800"
                      >
                      <span class="ml-3 text-gray-300">Recibir notificaciones por email</span>
                    </label>
                    <label class="flex items-center">
                      <input
                        v-model="preferences.soundEffects"
                        type="checkbox"
                        class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-600 rounded bg-gray-800"
                      >
                      <span class="ml-3 text-gray-300">Efectos de sonido en el juego</span>
                    </label>
                    <label class="flex items-center">
                      <input
                        v-model="preferences.autoMark"
                        type="checkbox"
                        class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-600 rounded bg-gray-800"
                      >
                      <span class="ml-3 text-gray-300">Marcar números automáticamente</span>
                    </label>
                  </div>
                </div>

                <!-- Idioma -->
                <div>
                  <h4 class="text-white font-semibold mb-3">Idioma</h4>
                  <select
                    v-model="preferences.language"
                    class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="es">Español</option>
                    <option value="en">English</option>
                    <option value="pt">Português</option>
                  </select>
                </div>

                <!-- Botón guardar -->
                <div class="flex justify-end pt-4">
                  <button
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                    @click="savePreferences"
                  >
                    Guardar Preferencias
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal de subida de avatar (placeholder) -->
    <Modal v-model="showAvatarUpload" title="Cambiar Avatar" size="sm">
      <div class="text-center py-8">
        <Icon name="lucide:upload" class="h-16 w-16 text-gray-400 mx-auto mb-4" />
        <p class="text-gray-600">Funcionalidad de subida de avatar</p>
        <p class="text-sm text-gray-500 mt-2">Próximamente disponible</p>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de perfil del jugador
 *
 * Permite editar información personal, cambiar contraseña
 * y configurar preferencias del juego.
 */

definePageMeta({
  middleware: ['auth'],
  layout: 'player'
})

const { user, updateUser: updateAuthUser } = useAuth()
const { updateProfile: updateProfileApi, updatePassword: updatePasswordApi } = useUserApi()

// Estado
const activeTab = ref('info')
const isSaving = ref(false)
const isChangingPassword = ref(false)
const showAvatarUpload = ref(false)

const tabs = [
  { id: 'info', label: 'Información Personal' },
  { id: 'security', label: 'Seguridad' },
  { id: 'preferences', label: 'Preferencias' }
]

// Stats del jugador
const stats = ref({
  gamesPlayed: 142,
  gamesWon: 38,
  totalWinnings: 1250.50
})

// Formulario de perfil
const profileForm = ref({
  first_name: '',
  last_name: '',
  email: ''
})

// Formulario de contraseña
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})

// Preferencias
const preferences = ref({
  emailNotifications: true,
  soundEffects: true,
  autoMark: true,
  language: 'es'
})

// Computed
const initials = computed(() => {
  if (!user.value) return ''
  const first = user.value.first_name[0] || ''
  const last = user.value.last_name[0] || ''
  return (first + last).toUpperCase()
})

const winRate = computed(() => {
  if (stats.value.gamesPlayed === 0) return 0
  return Math.round((stats.value.gamesWon / stats.value.gamesPlayed) * 100)
})

// Métodos
const loadProfile = () => {
  if (user.value) {
    profileForm.value = {
      first_name: user.value.first_name,
      last_name: user.value.last_name,
      email: user.value.email
    }
  }
}

const updateProfile = async () => {
  isSaving.value = true
  try {
    await updateProfileApi(profileForm.value)
    updateAuthUser(profileForm.value)
    alert('Perfil actualizado exitosamente')
  } catch (error) {
    console.error('Error actualizando perfil:', error)
    alert('Error al actualizar el perfil')
  } finally {
    isSaving.value = false
  }
}

const updatePassword = async () => {
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    alert('Las contraseñas no coinciden')
    return
  }

  isChangingPassword.value = true
  try {
    await updatePasswordApi(passwordForm.value)
    resetPasswordForm()
    alert('Contraseña actualizada exitosamente')
  } catch (error) {
    console.error('Error actualizando contraseña:', error)
    alert('Error al actualizar la contraseña')
  } finally {
    isChangingPassword.value = false
  }
}

const resetForm = () => {
  loadProfile()
}

const resetPasswordForm = () => {
  passwordForm.value = {
    current_password: '',
    password: '',
    password_confirmation: ''
  }
}

const savePreferences = () => {
  // TODO: Guardar preferencias en API
  alert('Preferencias guardadas')
}

// Lifecycle
onMounted(() => {
  loadProfile()
})

// SEO
useHead({
  title: 'Mi Perfil - PlayRKE'
})
</script>
