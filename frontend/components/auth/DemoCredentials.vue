<template>
  <!-- Modal Toggle Button - Mostrar cuando el modal está cerrado -->
  <ClientOnly>
    <div v-if="isDevelopment && hasCredentials && !isOpen" class="fixed bottom-4 right-4 z-50">
    <div class="relative group">
      <!-- Tooltip -->
      <div
        v-show="!isOpen"
        class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap"
      >
        Demo Login (Ctrl+D)
        <div class="absolute top-full right-4 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900" />
      </div>

      <!-- Main Button -->
      <UiBaseButton
        class="relative bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white shadow-lg hover:shadow-2xl transition-all duration-300 rounded-full p-3 group-hover:scale-110"
        :title="isOpen ? 'Cerrar credenciales' : 'Abrir credenciales demo'"
        @click.stop="toggleModal"
      >
        <SharedMediaIcon
          :name="isOpen ? 'x-mark' : 'key'"
          class="w-5 h-5 transition-transform duration-200"
          :class="{ 'rotate-180': isOpen }"
        />

        <!-- Notification Badge -->
        <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse border-2 border-white" />
      </UiBaseButton>
    </div>
  </div>
  </ClientOnly>

  <!-- Modal Overlay -->
  <ClientOnly>
    <Teleport to="body" :disabled="false">
      <!-- Debug: Modal state -->
      <div v-if="isDevelopment" style="position: fixed; top: 0; left: 0; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; font-size: 10px; z-index: 9999;">
        Modal: {{ isOpen ? 'OPEN' : 'CLOSED' }} | Dev: {{ isDevelopment }} | HasCreds: {{ hasCredentials }}
      </div>

      <Transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isDevelopment && isOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center p-2 sm:p-4"
        @click="closeModal"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/30 backdrop-blur-md" />

        <!-- Modal Content -->
        <Transition
          enter-active-class="duration-300 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="duration-200 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-4"
        >
          <div
            v-if="isOpen"
            class="relative bg-white/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-2xl border border-white/20 max-w-lg w-full max-h-[90vh] sm:max-h-[85vh] overflow-hidden"
            @click.stop
          >
            <!-- Header -->
            <div class="relative p-4 sm:p-6 border-b border-gray-100/50">
              <!-- Background Pattern -->
              <div class="absolute inset-0 bg-gradient-to-r from-amber-50 to-orange-50 opacity-50" />

              <div class="relative flex items-center justify-between gap-2">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                  <div class="p-2 sm:p-3 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl shadow-lg flex-shrink-0">
                    <SharedMediaIcon name="key" class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                  </div>
                  <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 truncate">
                      Cuentas Demo
                    </h3>
                    <p class="text-xs sm:text-sm text-gray-600 truncate">
                      Acceso rápido de desarrollo
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <UiBaseButton
                    variant="ghost"
                    size="sm"
                    class="p-2 hover:bg-white/60 rounded-xl transition-all duration-200 group"
                    :title="showPasswords ? 'Ocultar contraseñas' : 'Mostrar contraseñas'"
                    @click="togglePasswordVisibility"
                  >
                    <SharedMediaIcon
                      :name="showPasswords ? 'eye-slash' : 'eye'"
                      class="w-4 h-4 text-gray-600 group-hover:text-gray-800 transition-all duration-200"
                      :class="{ 'text-blue-600 group-hover:text-blue-700': showPasswords }"
                    />
                  </UiBaseButton>
                  <UiBaseButton
                    variant="ghost"
                    size="sm"
                    class="p-2 hover:bg-red-50 hover:text-red-600 rounded-xl transition-all duration-200"
                    @click="closeModal"
                  >
                    <SharedMediaIcon name="x-mark" class="w-4 h-4" />
                  </UiBaseButton>
                </div>
              </div>
            </div>

            <!-- Content -->
            <div class="p-4 sm:p-6 space-y-3 sm:space-y-4 max-h-[calc(90vh-180px)] sm:max-h-[65vh] overflow-y-auto custom-scrollbar">
              <!-- Loading State -->
              <div v-if="isLoadingCredentials" class="flex items-center justify-center py-8">
                <div class="text-center">
                  <div class="w-8 h-8 border-4 border-orange-200 border-t-orange-500 rounded-full animate-spin mx-auto mb-3" />
                  <p class="text-sm text-gray-600">
                    Cargando credenciales...
                  </p>
                </div>
              </div>

              <!-- Error State -->
              <div v-else-if="credentialsError" class="flex items-center justify-center py-8">
                <div class="text-center">
                  <SharedMediaIcon name="exclamation-triangle" class="w-8 h-8 text-red-500 mx-auto mb-3" />
                  <p class="text-sm text-red-600 mb-3">
                    {{ credentialsError }}
                  </p>
                  <UiBaseButton
                    variant="outline"
                    size="sm"
                    class="text-red-600 border-red-300 hover:bg-red-50"
                    @click="refreshCredentials"
                  >
                    Reintentar
                  </UiBaseButton>
                </div>
              </div>

              <!-- Content when loaded -->
              <template v-else-if="isReady">
                <!-- Quick Info Banner -->
                <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-100">
                  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div class="flex items-center gap-2">
                      <SharedMediaIcon name="terminal" class="w-4 h-4 text-blue-600" />
                      <span class="text-sm font-medium text-blue-900">Atajos de Teclado</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                      <div class="flex items-center gap-1">
                        <kbd class="px-2 py-1 bg-white border border-blue-200 rounded text-xs font-mono text-blue-700">Ctrl+D</kbd>
                        <span class="text-xs text-blue-600">Modal</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <kbd class="px-2 py-1 bg-white border border-blue-200 rounded text-xs font-mono text-blue-700">Ctrl+1-2</kbd>
                        <span class="text-xs text-blue-600">Auto-Login</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <kbd class="px-2 py-1 bg-white border border-blue-200 rounded text-xs font-mono text-blue-700">Esc</kbd>
                        <span class="text-xs text-blue-600">Cerrar</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Credentials Grid -->
                <div class="space-y-2 sm:space-y-3">
                  <div
                    v-for="config in credentialsConfig"
                    :key="config.id"
                    :class="[
                      'group relative p-3 sm:p-4 border rounded-xl sm:rounded-2xl',
                      'hover:bg-gradient-to-r transition-all duration-300 hover:shadow-lg hover:scale-[1.02]',
                      config.borderColor,
                      config.hoverGradient,
                    ]"
                  >
                    <!-- Main content row -->
                    <div :class="config.hasSelector ? 'flex items-start justify-between gap-2' : 'flex items-center justify-between gap-2'">
                      <div :class="config.hasSelector ? 'flex items-start gap-3 flex-1 min-w-0' : 'flex items-center gap-3 min-w-0 flex-1'">
                        <!-- Avatar with badge -->
                        <div class="relative flex-shrink-0">
                          <div
                            :class="[
                              'w-9 h-9 sm:w-10 sm:h-10 bg-gradient-to-br rounded-xl flex items-center justify-center shadow-lg',
                              config.gradientFrom,
                              config.gradientTo,
                            ]"
                          >
                            <SharedMediaIcon
                              :name="config.icon"
                              class="w-4 h-4 sm:w-5 sm:h-5 text-white"
                            />
                          </div>
                          <div
                            :class="[
                              'absolute -top-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center',
                              config.bgColor,
                            ]"
                          >
                            <span :class="['text-xs font-bold', config.textColor]">
                              {{ config.numberBadge }}
                            </span>
                          </div>
                        </div>

                        <!-- Credential info -->
                        <div :class="config.hasSelector ? 'flex-1 min-w-0' : 'min-w-0'">
                          <div class="font-semibold text-sm sm:text-base text-gray-900 truncate">
                            {{ config.label }}
                          </div>
                          <div class="text-xs sm:text-sm text-gray-600 truncate">
                            {{ config.description }}
                          </div>
                          <div class="text-xs text-gray-500 font-mono truncate">
                            {{ config.credentials.value?.email }}
                          </div>

                          <!-- Password reveal -->
                          <Transition
                            enter-active-class="duration-300 ease-out"
                            enter-from-class="opacity-0 transform scale-95 translate-y-1"
                            enter-to-class="opacity-100 transform scale-100 translate-y-0"
                            leave-active-class="duration-200 ease-in"
                            leave-from-class="opacity-100 transform scale-100 translate-y-0"
                            leave-to-class="opacity-0 transform scale-95 translate-y-1"
                          >
                            <div
                              v-if="showPasswords"
                              :class="[
                                'text-xs font-mono px-2 py-1 rounded mt-1 border flex items-center gap-1',
                                config.textColor,
                                config.bgColor,
                                `border-${config.id}-200`,
                              ]"
                            >
                              🔑 <span class="font-semibold">{{ config.credentials.value?.password }}</span>
                            </div>
                          </Transition>
                        </div>
                      </div>

                      <!-- Action button -->
                      <div
                        :class="[
                          'flex items-center gap-2 transition-all duration-200 flex-shrink-0',
                          config.hasSelector ? 'opacity-100 sm:opacity-0 sm:group-hover:opacity-100' : 'opacity-0 group-hover:opacity-100',
                        ]"
                      >
                        <UiBaseButton
                          variant="ghost"
                          size="sm"
                          :loading="loadingStates[config.id]"
                          :class="[
                            'rounded-xl',
                            config.hasSelector ? 'p-2' : 'p-3',
                            `hover:${config.bgColor}`,
                          ]"
                          :title="config.hasSelector
                            ? `Iniciar sesión automáticamente como ${config.credentials.value?.firstName}`
                            : 'Iniciar sesión automáticamente'
                          "
                          @click="config.fillMethod"
                        >
                          <SharedMediaIcon
                            name="bolt"
                            :class="[
                              config.hasSelector ? 'w-4 h-4' : 'w-5 h-5',
                              config.textColor,
                            ]"
                          />
                        </UiBaseButton>
                      </div>
                    </div>

                    <!-- Player selector (only for player) -->
                    <div
                      v-if="config.hasSelector"
                      class="mt-3 pt-3 border-t border-orange-100/50 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-all duration-200"
                    >
                      <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <span :class="['text-xs font-medium whitespace-nowrap flex-shrink-0', config.textColor]">
                          Seleccionar jugador:
                        </span>
                        <select
                          v-model="selectedPlayerIndex"
                          :class="[
                            'text-xs border rounded-lg px-3 py-2 bg-white/80 hover:bg-white transition-all w-full sm:flex-1 min-w-0',
                            `border-${config.id}-200`,
                            `focus:ring-2 focus:ring-${config.id}-300`,
                          ]"
                          @change="updateSelectedPlayer"
                        >
                          <option
                            v-for="(player, index) in playerCredentials"
                            :key="player.id || index"
                            :value="index"
                          >
                            {{ player.firstName }} - {{ player.email }}
                          </option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Enhanced Footer Stats -->
                <div class="mt-6 pt-4 border-t border-gray-100/50">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-gradient-to-r from-gray-50 to-gray-100/50 rounded-2xl">
                    <div class="flex items-center justify-between sm:justify-start sm:gap-4 gap-2 overflow-x-auto">
                      <div class="text-center flex-shrink-0">
                        <div class="text-base sm:text-lg font-bold text-gray-800">
                          {{ usageCount }}
                        </div>
                        <div class="text-xs text-gray-600 whitespace-nowrap">
                          Usos
                        </div>
                      </div>
                      <div class="w-px h-8 bg-gray-300 hidden sm:block" />
                      <div class="text-center flex-shrink-0">
                        <div class="text-xs sm:text-sm font-semibold text-gray-800 truncate max-w-[80px] sm:max-w-[120px]">
                          {{ lastUsedRole || 'N/A' }}
                        </div>
                        <div class="text-xs text-gray-600 whitespace-nowrap">
                          Último
                        </div>
                      </div>
                      <div class="w-px h-8 bg-gray-300 hidden sm:block" />
                      <div class="text-center flex-shrink-0">
                        <div class="text-base sm:text-lg font-bold text-gray-800">
                          {{ totalCredentials }}
                        </div>
                        <div class="text-xs text-gray-600 whitespace-nowrap">
                          Total
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center justify-center sm:justify-start gap-2 flex-shrink-0">
                      <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                      <span class="text-xs font-medium text-green-700">Desarrollo</span>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
    </Teleport>
  </ClientOnly>
</template>

<script setup lang="ts">
import { ref, computed, reactive, readonly, onMounted, onUnmounted, watch } from 'vue'
import { useDemoCredentials, type DemoCredential } from '~/layers/shared/composables/auth/useDemoCredentials'
import {
  copyCredentials as copyCredentialsHelper,
  copyAllCredentials as copyAllCredentialsHelper,
  initStats,
  saveStats,
} from '~/layers/shared/composables/auth/useDemoCredentialsHelpers'

// Types
interface DemoCredentials {
  login: string  // Backend usa 'login' (email o username)
  password: string
  remember?: boolean
}

// Props
interface Props {
  autoLogin?: boolean
  modelValue?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  autoLogin: false,
})

const emit = defineEmits<{
  'fillCredentials': [credentials: DemoCredentials]
  'autoLogin': [credentials: DemoCredentials]
  'autoLoginStart': []
  'autoLoginComplete': [success: boolean]
  'update:modelValue': [value: boolean]
}>()

const {
  credentials,
  isLoading: isLoadingCredentials,
  error: credentialsError,
  adminCredentials,
  commerceCredentials,
  customerCredentials,
  playerCredentials,
  totalCredentials,
  isAvailable,
  refresh: refreshCredentials,
} = useDemoCredentials()

// State
const loadingStates = reactive<Record<string, boolean>>({
  admin: false,
  commerce: false,
  customer: false,
  player: false,
})
const selectedPlayerIndex = ref(0)
const showPasswords = ref(false)
const isCollapsed = ref(false)
const internalIsOpen = ref(false)

const isOpen = computed({
  get: () => props.modelValue !== undefined ? props.modelValue : internalIsOpen.value,
  set: (value) => {
    if (props.modelValue !== undefined) {
      emit('update:modelValue', value)
    } else {
      internalIsOpen.value = value
    }
  },
})

interface CredentialConfig {
  id: string
  role: string
  icon: string
  label: string
  description: string
  numberBadge: number
  gradientFrom: string
  gradientTo: string
  borderColor: string
  hoverGradient: string
  bgColor: string
  textColor: string
  credentials: ComputedRef<DemoCredential | null>
  fillMethod: () => Promise<void>
  hasSelector?: boolean
}

// eslint-disable-next-line complexity
const credentialsConfig = computed<CredentialConfig[]>(() => [
  {
    id: 'admin',
    role: 'admin',
    icon: 'shield-check',
    label: currentAdminCredentials.value?.firstName || 'Administrador',
    description: currentAdminCredentials.value?.description || 'Control total del sistema',
    numberBadge: 1,
    gradientFrom: 'from-slate-600',
    gradientTo: 'to-slate-800',
    borderColor: 'border-slate-200/50',
    hoverGradient: 'hover:from-slate-50/50 hover:to-slate-100/50',
    bgColor: 'bg-slate-50',
    textColor: 'text-slate-700',
    credentials: currentAdminCredentials,
    fillMethod: fillAdminCredentials,
  },
  {
    id: 'player',
    role: 'player',
    icon: 'puzzle-piece',
    label: currentPlayerCredentials.value?.firstName || 'Jugador',
    description: `Cuenta de jugador (${playerCredentials.value.length} disponibles)`,
    numberBadge: 2,
    gradientFrom: 'from-pink-600',
    gradientTo: 'to-purple-600',
    borderColor: 'border-pink-200/50',
    hoverGradient: 'hover:from-pink-50/50 hover:to-purple-50/50',
    bgColor: 'bg-pink-50',
    textColor: 'text-pink-600',
    credentials: currentPlayerCredentials,
    fillMethod: fillPlayerCredentials,
    hasSelector: true,
  },
])

const copyFeedback = reactive({
  admin: '',
  commerce: '',
  customer: '',
  player: '',
  all: '',
})
const usageCount = ref(0)
const lastUsedRole = ref('')

const currentAdminCredentials = computed(() => adminCredentials.value[0] || null)
const currentCommerceCredentials = computed(() => commerceCredentials.value[0] || null)
const currentCustomerCredentials = computed(() => customerCredentials.value[0] || null)
const currentPlayerCredentials = computed(() => playerCredentials.value[selectedPlayerIndex.value] || playerCredentials.value[0] || null)

// En Nuxt, siempre mostrar en desarrollo (no en producción)
const isDevelopment = ref(true)
const hasCredentials = computed(() => {
  const available = isAvailable.value
  const total = totalCredentials.value
  const hasProps = props.modelValue === undefined
  console.log('[DemoCredentials] isAvailable:', available, 'totalCredentials:', total, 'hasCredentials:', available && total > 0, 'isDevelopment:', isDevelopment.value, 'props.modelValue:', props.modelValue, 'shouldShow:', isDevelopment.value && available && total > 0 && hasProps)
  return available && total > 0
})
const isReady = computed(() => !isLoadingCredentials.value && hasCredentials.value)

// Auto login - Ejecuta login real con el backend
const performAutoLogin = async (credentials: DemoCredential) => {
  const router = useRouter()
  const { login } = useAuth()

  try {
    // Emitir evento de inicio de auto-login
    emit('autoLoginStart')

    // Ejecutar login real con el backend
    await login({
      login: credentials.email,  // Backend usa 'login' (email o username)
      password: credentials.password,
      remember: true,
    })

    // Emitir evento de completado exitoso
    emit('autoLoginComplete', true)

    // Cerrar modal después de login exitoso
    closeModal()

    console.log('[DemoCredentials] Login exitoso, redirigiendo...')

    // Pequeño delay para mostrar feedback visual
    await new Promise(resolve => setTimeout(resolve, 500))

    // Redirigir al lobby/dashboard correspondiente según contexto
    const currentPath = router.currentRoute.value.path
    if (currentPath.includes('/admin')) {
      await router.push('/admin/dashboard')
    } else {
      await router.push('/player/lobby')
    }

  } catch (error) {
    // eslint-disable-next-line no-console
    console.error('Error en auto-login:', error)

    // Emitir evento de completado con error
    emit('autoLoginComplete', false)

    // Si falla el auto-login, fallback a solo llenar el formulario
    emit('fillCredentials', {
      login: credentials.email,
      password: credentials.password,
      remember: true,
    })
  }
}

// Métodos
const fillAdminCredentials = async () => {
  if (loadingStates.admin || !currentAdminCredentials.value) { return }

  try {
    loadingStates.admin = true

    // Simular pequeño delay para UX
    await new Promise(resolve => setTimeout(resolve, 300))

    const adminCred = currentAdminCredentials.value

    // Siempre ejecutar auto-login al hacer click
    await performAutoLogin(adminCred)

    saveStats('admin', usageCount, lastUsedRole)

    // Mostrar feedback visual
    await new Promise(resolve => setTimeout(resolve, 200))
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error('Error filling admin credentials:', error)
  } finally {
    loadingStates.admin = false
  }
}

const fillCommerceCredentials = async () => {
  if (loadingStates.commerce || !currentCommerceCredentials.value) { return }

  try {
    loadingStates.commerce = true
    await new Promise(resolve => setTimeout(resolve, 300))

    const commerceCred = currentCommerceCredentials.value

    // Siempre ejecutar auto-login al hacer click
    await performAutoLogin(commerceCred)

    saveStats('commerce', usageCount, lastUsedRole)
    await new Promise(resolve => setTimeout(resolve, 200))
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error('Error filling commerce credentials:', error)
  } finally {
    loadingStates.commerce = false
  }
}

const fillCustomerCredentials = async () => {
  if (loadingStates.customer || !currentCustomerCredentials.value) { return }

  try {
    loadingStates.customer = true
    await new Promise(resolve => setTimeout(resolve, 300))

    const customerCred = currentCustomerCredentials.value

    // Siempre ejecutar auto-login al hacer click
    await performAutoLogin(customerCred)

    saveStats('customer', usageCount, lastUsedRole)
    await new Promise(resolve => setTimeout(resolve, 200))
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error('Error filling customer credentials:', error)
  } finally {
    loadingStates.customer = false
  }
}

const fillPlayerCredentials = async () => {
  if (loadingStates.player || !currentPlayerCredentials.value) { return }

  try {
    loadingStates.player = true
    await new Promise(resolve => setTimeout(resolve, 300))

    const playerCred = currentPlayerCredentials.value

    // Siempre ejecutar auto-login al hacer click
    await performAutoLogin(playerCred)

    saveStats(`player (${playerCred.firstName})`, usageCount, lastUsedRole)
    await new Promise(resolve => setTimeout(resolve, 200))
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error('Error filling player credentials:', error)
  } finally {
    loadingStates.player = false
  }
}

const updateSelectedPlayer = () => {
  // Trigger reactivity cuando cambia la selección
}

// Funciones del modal
const toggleModal = () => {
  const newValue = !isOpen.value
  console.log('[DemoCredentials] toggleModal:', {
    currentIsOpen: isOpen.value,
    newValue,
    propsModelValue: props.modelValue,
    internalIsOpen: internalIsOpen.value,
    isDevelopment: isDevelopment.value,
  })
  if (props.modelValue !== undefined) {
    emit('update:modelValue', newValue)
  } else {
    internalIsOpen.value = newValue
  }
  console.log('[DemoCredentials] After toggle, isOpen:', isOpen.value)
}

const closeModal = () => {
  if (props.modelValue !== undefined) {
    emit('update:modelValue', false)
  } else {
    internalIsOpen.value = false
  }
}

const togglePasswordVisibility = () => {
  showPasswords.value = !showPasswords.value
}

const toggleCollapsed = () => {
  isCollapsed.value = !isCollapsed.value
}

// Wrapper para copyCredentials del helper
const copyCredentials = async (role: string) => {
  await copyCredentialsHelper(
    role,
    currentAdminCredentials,
    currentCommerceCredentials,
    currentCustomerCredentials,
    currentPlayerCredentials,
    copyFeedback,
  )
}

// Helper para manejar fill de credenciales con atajo
const handleCredentialShortcut = (fillMethod: () => Promise<void>) => {
  if (!isOpen.value && !props.autoLogin) toggleModal()
  setTimeout(() => fillMethod(), 100)
}

// Manejo de atajos de teclado
const handleKeyboardShortcuts = (event: KeyboardEvent) => {
  // Escape para cerrar modal
  if (event.key === 'Escape' && isOpen.value) {
    closeModal()
    return
  }

  if (!event.ctrlKey || !isDevelopment.value) return

  const keyActions: Record<string, () => void> = {
    d: () => toggleModal(),
    1: () => handleCredentialShortcut(fillAdminCredentials),
    2: () => handleCredentialShortcut(fillPlayerCredentials),
  }

  const action = keyActions[event.key.toLowerCase()]
  if (action) {
    event.preventDefault()
    action()
  }
}

// Wrapper para copyAllCredentials del helper
const copyAllCredentials = async () => {
  await copyAllCredentialsHelper(
    currentAdminCredentials,
    currentCommerceCredentials,
    currentCustomerCredentials,
    playerCredentials,
    copyFeedback,
  )
}

// Inicializar estadísticas al montar el componente
onMounted(() => {
  initStats(usageCount, lastUsedRole)

  // Agregar event listeners para atajos de teclado
  if (import.meta.client) {
    document.addEventListener('keydown', handleKeyboardShortcuts)
  }

  // Debug: log initial state
  console.log('[DemoCredentials] Mounted:', {
    isDevelopment: isDevelopment.value,
    hasCredentials: hasCredentials.value,
    isOpen: isOpen.value,
    totalCredentials: totalCredentials.value,
    isLoadingCredentials: isLoadingCredentials.value,
    propsModelValue: props.modelValue,
  })
})

// Debug: watch isOpen changes
watch(isOpen, (newVal, oldVal) => {
  console.log('[DemoCredentials] isOpen changed:', { oldVal, newVal })
})

// Debug: watch credentials
watch(credentials, (newVal) => {
  console.log('[DemoCredentials] credentials changed:', newVal.length, 'items')
}, { deep: true })

// Limpiar event listeners
onUnmounted(() => {
  if (import.meta.client) {
    document.removeEventListener('keydown', handleKeyboardShortcuts)
  }
})

// Exponer API para testing
defineExpose({
  fillAdminCredentials,
  fillCommerceCredentials,
  fillCustomerCredentials,
  fillPlayerCredentials,
  copyCredentials,
  copyAllCredentials,
  togglePasswordVisibility,
  toggleCollapsed,
  toggleModal,
  closeModal,
  refreshCredentials,
  // Credenciales dinámicas
  credentials: readonly(credentials),
  adminCredentials,
  commerceCredentials,
  customerCredentials,
  playerCredentials,
  currentAdminCredentials,
  currentCommerceCredentials,
  currentCustomerCredentials,
  currentPlayerCredentials,
  // Estado
  selectedPlayerIndex,
  showPasswords: readonly(showPasswords),
  isCollapsed: readonly(isCollapsed),
  isOpen: readonly(isOpen),
  isLoadingCredentials: readonly(isLoadingCredentials),
  isReady,
  hasCredentials,
  totalCredentials,
  copyFeedback: readonly(copyFeedback),
  credentialsError: readonly(credentialsError),
})
</script>

<style scoped>
/* Custom scrollbar */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(107, 114, 128, 0.7);
}
</style>
