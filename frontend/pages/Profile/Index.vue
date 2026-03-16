<!--
  Página de perfil de usuario.

  Permite al usuario ver y editar su información personal,
  gestionar su avatar, cambiar contraseña y configurar preferencias.
-->

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="font-gaming text-4xl font-bold text-white mb-2">
              Mi Perfil
            </h1>
            <p class="text-slate-400">
              Gestiona tu información y preferencias
            </p>
          </div>
          <button 
            class="btn-secondary"
            @click="goBack"
          >
            <Icon name="lucide:arrow-left" class="w-4 h-4 mr-2" />
            Volver
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Columna izquierda - Avatar e información básica -->
        <div class="lg:col-span-1">
          <!-- Avatar -->
          <div class="card-player mb-6">
            <div class="p-6">
              <div class="text-center">
                <div class="relative inline-block mb-4">
                  <img 
                    :src="profileData.avatar || '/images/default-avatar.png'" 
                    :alt="profileData.name"
                    class="w-32 h-32 rounded-full mx-auto border-4 border-gaming-gold"
                  />
                  <button 
                    class="absolute bottom-0 right-0 bg-gaming-gold text-white p-2 rounded-full hover:scale-110 transition-transform"
                    @click="showAvatarModal = true"
                  >
                    <Icon name="lucide:camera" class="w-4 h-4" />
                  </button>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">{{ profileData.name }}</h2>
                <p class="text-slate-400 mb-4">@{{ profileData.username }}</p>
                
                <!-- Verificación -->
                <div v-if="profileData.is_verified" class="flex items-center justify-center text-gaming-gold mb-4">
                  <Icon name="lucide:check-circle" class="w-5 h-5 mr-2" />
                  <span class="text-sm">Cuenta Verificada</span>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-2 gap-4 text-center">
                  <div class="bg-slate-800 rounded-lg p-3">
                    <p class="text-2xl font-bold text-gaming-gold">{{ userStats.level }}</p>
                    <p class="text-xs text-slate-400">Nivel</p>
                  </div>
                  <div class="bg-slate-800 rounded-lg p-3">
                    <p class="text-2xl font-bold text-gaming-neon">{{ userStats.games_won }}</p>
                    <p class="text-xs text-slate-400">Victorias</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Acciones rápidas -->
          <div class="card-player">
            <div class="p-6">
              <h3 class="text-lg font-bold text-white mb-4">Acciones Rápidas</h3>
              <div class="space-y-3">
                <button class="btn-gaming w-full" @click="activeTab = 'settings'">
                  <Icon name="lucide:settings" class="w-4 h-4 mr-2" />
                  Configuración
                </button>
                <button class="btn-secondary w-full" @click="activeTab = 'security'">
                  <Icon name="lucide:shield" class="w-4 h-4 mr-2" />
                  Seguridad
                </button>
                <button class="btn-secondary w-full" @click="logout">
                  <Icon name="lucide:log-out" class="w-4 h-4 mr-2" />
                  Cerrar Sesión
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Columna derecha - Tabs de contenido -->
        <div class="lg:col-span-2">
          <div class="card-player">
            <!-- Tabs -->
            <div class="border-b border-slate-700">
              <nav class="flex space-x-8">
                <button
                  v-for="tab in tabs"
                  :key="tab.key"
                  class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                  :class="[
                    activeTab === tab.key
                      ? 'border-gaming-gold text-gaming-gold'
                      : 'border-transparent text-slate-400 hover:text-white'
                  ]"
                  @click="activeTab = tab.key"
                >
                  <Icon :name="tab.icon" class="w-4 h-4 mr-2 inline" />
                  {{ tab.label }}
                </button>
              </nav>
            </div>

            <!-- Contenido del tab -->
            <div class="p-6">
              <!-- Tab Perfil -->
              <div v-if="activeTab === 'profile'" class="space-y-6">
                <h3 class="text-xl font-bold text-white mb-4">Información Personal</h3>
                
                <form @submit.prevent="updateProfile" class="space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-slate-300 mb-2">
                        Nombre Completo
                      </label>
                      <input
                        v-model="editForm.name"
                        type="text"
                        class="input-gaming w-full"
                        required
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-300 mb-2">
                        Nombre de Usuario
                      </label>
                      <input
                        v-model="editForm.username"
                        type="text"
                        class="input-gaming w-full"
                        required
                        pattern="[a-zA-Z0-9_]+"
                        title="Solo letras, números y guiones bajos"
                      />
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">
                      Correo Electrónico
                    </label>
                    <input
                      v-model="editForm.email"
                      type="email"
                      class="input-gaming w-full"
                      required
                    />
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-slate-300 mb-2">
                        Teléfono
                      </label>
                      <input
                        v-model="editForm.phone"
                        type="tel"
                        class="input-gaming w-full"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-300 mb-2">
                        Fecha de Nacimiento
                      </label>
                      <input
                        v-model="editForm.birth_date"
                        type="date"
                        class="input-gaming w-full"
                      />
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">
                      País
                    </label>
                    <select v-model="editForm.country" class="input-gaming w-full">
                      <option value="">Selecciona tu país</option>
                      <option value="VE">Venezuela</option>
                      <option value="CO">Colombia</option>
                      <option value="MX">México</option>
                      <option value="AR">Argentina</option>
                      <option value="PE">Perú</option>
                      <option value="ES">España</option>
                      <option value="US">Estados Unidos</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">
                      Biografía
                    </label>
                    <textarea
                      v-model="editForm.bio"
                      class="input-gaming w-full"
                      rows="4"
                      maxlength="500"
                      placeholder="Cuéntanos sobre ti..."
                    ></textarea>
                    <p class="text-xs text-slate-500 mt-1">
                      {{ editForm.bio?.length || 0 }}/500 caracteres
                    </p>
                  </div>

                  <div class="flex justify-end">
                    <button type="submit" class="btn-gaming" :disabled="isLoading">
                      <Icon name="lucide:save" class="w-4 h-4 mr-2" />
                      {{ isLoading ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                  </div>
                </form>
              </div>

              <!-- Tab Estadísticas -->
              <div v-if="activeTab === 'stats'" class="space-y-6">
                <h3 class="text-xl font-bold text-white mb-4">Mis Estadísticas</h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div class="bg-slate-800 rounded-lg p-4 text-center">
                    <Icon name="lucide:gamepad-2" class="w-8 h-8 text-gaming-neon mx-auto mb-2" />
                    <p class="text-2xl font-bold text-white">{{ userStats.games_played }}</p>
                    <p class="text-sm text-slate-400">Partidas Jugadas</p>
                  </div>
                  <div class="bg-slate-800 rounded-lg p-4 text-center">
                    <Icon name="lucide:trophy" class="w-8 h-8 text-gaming-gold mx-auto mb-2" />
                    <p class="text-2xl font-bold text-white">{{ userStats.games_won }}</p>
                    <p class="text-sm text-slate-400">Victorias</p>
                  </div>
                  <div class="bg-slate-800 rounded-lg p-4 text-center">
                    <Icon name="lucide:dollar-sign" class="w-8 h-8 text-green-500 mx-auto mb-2" />
                    <p class="text-2xl font-bold text-white">${{ userStats.total_winnings }}</p>
                    <p class="text-sm text-slate-400">Ganancias Totales</p>
                  </div>
                  <div class="bg-slate-800 rounded-lg p-4 text-center">
                    <Icon name="lucide:zap" class="w-8 h-8 text-gaming-neon mx-auto mb-2" />
                    <p class="text-2xl font-bold text-white">{{ userStats.current_streak }}</p>
                    <p class="text-sm text-slate-400">Racha Actual</p>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="bg-slate-800 rounded-lg p-6">
                    <h4 class="text-lg font-bold text-white mb-4">Progreso</h4>
                    <div class="space-y-3">
                      <div>
                        <div class="flex justify-between text-sm mb-1">
                          <span class="text-slate-400">Nivel {{ userStats.level }}</span>
                          <span class="text-slate-400">{{ userStats.experience_points }} XP</span>
                        </div>
                        <div class="w-full bg-slate-700 rounded-full h-2">
                          <div 
                            class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full"
                            :style="{ width: '65%' }"
                          ></div>
                        </div>
                      </div>
                      <div>
                        <div class="flex justify-between text-sm mb-1">
                          <span class="text-slate-400">Mejor Racha</span>
                          <span class="text-gaming-gold font-bold">{{ userStats.best_streak }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="bg-slate-800 rounded-lg p-6">
                    <h4 class="text-lg font-bold text-white mb-4">Logros</h4>
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-3xl font-bold text-gaming-gold">{{ userStats.achievements_count }}</p>
                        <p class="text-sm text-slate-400">Logros Desbloqueados</p>
                      </div>
                      <Icon name="lucide:award" class="w-12 h-12 text-gaming-gold" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tab Configuración -->
              <div v-if="activeTab === 'settings'" class="space-y-6">
                <h3 class="text-xl font-bold text-white mb-4">Preferencias</h3>
                
                <form @submit.prevent="updatePreferences" class="space-y-6">
                  <!-- Idioma y Zona Horaria -->
                  <div>
                    <h4 class="text-lg font-medium text-white mb-3">General</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                          Idioma
                        </label>
                        <select v-model="preferences.language" class="input-gaming w-full">
                          <option value="es">Español</option>
                          <option value="en">English</option>
                          <option value="pt">Português</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                          Zona Horaria
                        </label>
                        <select v-model="preferences.timezone" class="input-gaming w-full">
                          <option value="America/Caracas">Caracas</option>
                          <option value="America/Bogota">Bogotá</option>
                          <option value="America/Mexico_City">México</option>
                          <option value="America/Argentina/Buenos_Aires">Buenos Aires</option>
                          <option value="UTC">UTC</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- Notificaciones -->
                  <div>
                    <h4 class="text-lg font-medium text-white mb-3">Notificaciones</h4>
                    <div class="space-y-3">
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Notificaciones por Email</span>
                        <input
                          v-model="preferences.notifications.email"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Notificaciones Push</span>
                        <input
                          v-model="preferences.notifications.push"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Notificaciones SMS</span>
                        <input
                          v-model="preferences.notifications.sms"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                    </div>
                  </div>

                  <!-- Privacidad -->
                  <div>
                    <h4 class="text-lg font-medium text-white mb-3">Privacidad</h4>
                    <div class="space-y-3">
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Perfil Público</span>
                        <input
                          v-model="preferences.privacy.profile_visible"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Mostrar Email</span>
                        <input
                          v-model="preferences.privacy.show_email"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Mostrar Teléfono</span>
                        <input
                          v-model="preferences.privacy.show_phone"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                    </div>
                  </div>

                  <!-- Configuración del Juego -->
                  <div>
                    <h4 class="text-lg font-medium text-white mb-3">Configuración del Juego</h4>
                    <div class="space-y-3">
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Sonido Activado</span>
                        <input
                          v-model="preferences.game_settings.sound_enabled"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Música Activada</span>
                        <input
                          v-model="preferences.game_settings.music_enabled"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Marcador Automático</span>
                        <input
                          v-model="preferences.game_settings.auto_dauber"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                      <label class="flex items-center justify-between">
                        <span class="text-slate-300">Animaciones de Cartón</span>
                        <input
                          v-model="preferences.game_settings.card_animation"
                          type="checkbox"
                          class="toggle-gaming"
                        />
                      </label>
                    </div>
                  </div>

                  <div class="flex justify-end">
                    <button type="submit" class="btn-gaming" :disabled="isLoading">
                      <Icon name="lucide:save" class="w-4 h-4 mr-2" />
                      {{ isLoading ? 'Guardando...' : 'Guardar Preferencias' }}
                    </button>
                  </div>
                </form>
              </div>

              <!-- Tab Seguridad -->
              <div v-if="activeTab === 'security'" class="space-y-6">
                <h3 class="text-xl font-bold text-white mb-4">Seguridad</h3>
                
                <!-- Cambiar Contraseña -->
                <div class="bg-slate-800 rounded-lg p-6">
                  <h4 class="text-lg font-medium text-white mb-4">Cambiar Contraseña</h4>
                  <form @submit.prevent="updatePassword" class="space-y-4">
                    <div>
                      <label class="block text-sm font-medium text-slate-300 mb-2">
                        Contraseña Actual
                      </label>
                      <input
                        v-model="passwordForm.current_password"
                        type="password"
                        class="input-gaming w-full"
                        required
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-300 mb-2">
                        Nueva Contraseña
                      </label>
                      <input
                        v-model="passwordForm.password"
                        type="password"
                        class="input-gaming w-full"
                        required
                        minlength="8"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-300 mb-2">
                        Confirmar Nueva Contraseña
                      </label>
                      <input
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        class="input-gaming w-full"
                        required
                      />
                    </div>
                    <button type="submit" class="btn-gaming" :disabled="isLoading">
                      <Icon name="lucide:lock" class="w-4 h-4 mr-2" />
                      {{ isLoading ? 'Actualizando...' : 'Actualizar Contraseña' }}
                    </button>
                  </form>
                </div>

                <!-- Sesiones Activas -->
                <div class="bg-slate-800 rounded-lg p-6">
                  <h4 class="text-lg font-medium text-white mb-4">Sesiones Activas</h4>
                  <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-slate-700 rounded-lg">
                      <div class="flex items-center space-x-3">
                        <Icon name="lucide:monitor" class="w-5 h-5 text-gaming-gold" />
                        <div>
                          <p class="text-white font-medium">Chrome - Windows</p>
                          <p class="text-sm text-slate-400">Actual - Caracas, Venezuela</p>
                        </div>
                      </div>
                      <span class="text-xs text-gaming-gold">Activa</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Avatar -->
    <div v-if="showAvatarModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="card-player max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white">Cambiar Avatar</h3>
            <button @click="showAvatarModal = false" class="text-slate-400 hover:text-white">
              <Icon name="lucide:x" class="w-6 h-6" />
            </button>
          </div>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">
                Selecciona una imagen
              </label>
              <input
                type="file"
                accept="image/*"
                @change="handleAvatarChange"
                class="input-gaming w-full"
              />
              <p class="text-xs text-slate-500 mt-1">
                Formatos: JPEG, PNG, GIF. Máximo 2MB
              </p>
            </div>
            
            <div v-if="avatarPreview" class="text-center">
              <img 
                :src="avatarPreview" 
                alt="Preview"
                class="w-24 h-24 rounded-full mx-auto border-4 border-gaming-gold"
              />
            </div>
            
            <div class="flex space-x-3">
              <button 
                @click="uploadAvatar" 
                class="btn-gaming flex-1"
                :disabled="!avatarFile || isLoading"
              >
                <Icon name="lucide:upload" class="w-4 h-4 mr-2" />
                {{ isLoading ? 'Subiendo...' : 'Subir Avatar' }}
              </button>
              <button 
                v-if="profileData.avatar"
                @click="deleteAvatar" 
                class="btn-secondary flex-1"
                :disabled="isLoading"
              >
                <Icon name="lucide:trash-2" class="w-4 h-4 mr-2" />
                Eliminar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Configuración de la página
definePageMeta({
  layout: 'player',
  middleware: 'auth'
})

// SEO
useHead({
  title: 'Mi Perfil - PlayRKE',
  meta: [
    { name: 'description', content: 'Gestiona tu perfil de jugador en PlayRKE' }
  ]
})

// Composables
const { $api } = useNuxtApp()
const toast = useToast()

// Estados reactivos
const isLoading = ref(false)
const showAvatarModal = ref(false)
const activeTab = ref('profile')
const avatarFile = ref(null)
const avatarPreview = ref(null)

// Datos del perfil
const profileData = ref({
  id: null,
  name: '',
  username: '',
  email: '',
  avatar: null,
  phone: '',
  birth_date: '',
  country: '',
  bio: '',
  is_verified: false,
  roles: [],
  wallet: null
})

// Formulario de edición
const editForm = ref({
  name: '',
  username: '',
  email: '',
  phone: '',
  birth_date: '',
  country: '',
  bio: ''
})

// Formulario de contraseña
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})

// Preferencias
const preferences = ref({
  language: 'es',
  timezone: 'America/Caracas',
  notifications: {
    email: true,
    push: true,
    sms: false
  },
  privacy: {
    profile_visible: true,
    show_email: false,
    show_phone: false
  },
  game_settings: {
    sound_enabled: true,
    music_enabled: true,
    auto_dauber: false,
    card_animation: true
  }
})

// Estadísticas del usuario
const userStats = ref({
  games_played: 0,
  games_won: 0,
  total_winnings: 0,
  current_streak: 0,
  best_streak: 0,
  level: 1,
  experience_points: 0,
  achievements_count: 0
})

// Tabs disponibles
const tabs = ref([
  { key: 'profile', label: 'Perfil', icon: 'lucide:user' },
  { key: 'stats', label: 'Estadísticas', icon: 'lucide:bar-chart' },
  { key: 'settings', label: 'Configuración', icon: 'lucide:settings' },
  { key: 'security', label: 'Seguridad', icon: 'lucide:shield' }
])

// Métodos
const loadProfile = async () => {
  try {
    isLoading.value = true
    const response = await $api.get('/user/profile')
    
    if (response.success) {
      profileData.value = response.data
      editForm.value = { ...response.data }
    }
  } catch (error) {
    toast.error('Error al cargar el perfil')
  } finally {
    isLoading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await $api.get('/user/stats')
    
    if (response.success) {
      userStats.value = response.data
    }
  } catch (error) {
    console.error('Error al cargar estadísticas:', error)
  }
}

const loadPreferences = async () => {
  try {
    const response = await $api.get('/user/preferences')
    
    if (response.success) {
      preferences.value = { ...preferences.value, ...response.data }
    }
  } catch (error) {
    console.error('Error al cargar preferencias:', error)
  }
}

const updateProfile = async () => {
  try {
    isLoading.value = true
    const response = await $api.put('/user/profile', editForm.value)
    
    if (response.success) {
      profileData.value = { ...profileData.value, ...response.data }
      toast.success('Perfil actualizado correctamente')
    }
  } catch (error) {
    toast.error('Error al actualizar el perfil')
  } finally {
    isLoading.value = false
  }
}

const updatePassword = async () => {
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    toast.error('Las contraseñas no coinciden')
    return
  }

  try {
    isLoading.value = true
    const response = await $api.put('/user/password', passwordForm.value)
    
    if (response.success) {
      toast.success('Contraseña actualizada correctamente')
      passwordForm.value = {
        current_password: '',
        password: '',
        password_confirmation: ''
      }
    }
  } catch (error) {
    toast.error('Error al actualizar la contraseña')
  } finally {
    isLoading.value = false
  }
}

const updatePreferences = async () => {
  try {
    isLoading.value = true
    const response = await $api.put('/user/preferences', preferences.value)
    
    if (response.success) {
      toast.success('Preferencias actualizadas correctamente')
    }
  } catch (error) {
    toast.error('Error al actualizar las preferencias')
  } finally {
    isLoading.value = false
  }
}

const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
  }
}

const uploadAvatar = async () => {
  if (!avatarFile.value) return

  try {
    isLoading.value = true
    const formData = new FormData()
    formData.append('avatar', avatarFile.value)
    
    const response = await $api.post('/user/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    if (response.success) {
      profileData.value.avatar = response.data.avatar
      toast.success('Avatar actualizado correctamente')
      showAvatarModal.value = false
      avatarFile.value = null
      avatarPreview.value = null
    }
  } catch (error) {
    toast.error('Error al subir el avatar')
  } finally {
    isLoading.value = false
  }
}

const deleteAvatar = async () => {
  try {
    isLoading.value = true
    const response = await $api.delete('/user/avatar')
    
    if (response.success) {
      profileData.value.avatar = null
      toast.success('Avatar eliminado correctamente')
      showAvatarModal.value = false
    }
  } catch (error) {
    toast.error('Error al eliminar el avatar')
  } finally {
    isLoading.value = false
  }
}

const goBack = () => {
  navigateTo('/player')
}

const logout = async () => {
  try {
    await $api.post('/auth/logout')
    await navigateTo('/player/auth/login')
  } catch (error) {
    console.error('Error al cerrar sesión:', error)
    await navigateTo('/player/auth/login')
  }
}

// Cargar datos al montar el componente
onMounted(async () => {
  await Promise.all([
    loadProfile(),
    loadStats(),
    loadPreferences()
  ])
})
</script>
