<template>
  <Teleport to="body">
    <!-- Botón flotante -->
    <div class="fixed bottom-4 right-4 z-50">
      <div class="relative group">
        <div
          v-show="!isOpen"
          class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap pointer-events-none"
        >
          Demo Login
          <kbd class="ml-1 px-1.5 py-0.5 bg-white/20 rounded text-xs font-mono">Ctrl+D</kbd>
          <div class="absolute top-full right-4 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900" />
        </div>
        <button
          :title="isOpen ? 'Cerrar' : 'Cuentas demo'"
          class="relative bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white shadow-lg hover:shadow-xl transition-all duration-300 rounded-full p-3 hover:scale-110"
          type="button"
          @click="toggleModal"
        >
          <i :class="isOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-key'" />
          <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse border-2 border-white" />
        </button>
      </div>
    </div>

    <!-- Modal -->
    <Transition
        enter-active-class="duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="isOpen"
          class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4"
          @click="closeModal"
        >
          <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" />

          <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
          >
            <div
              v-if="isOpen"
              class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-md"
              @click.stop
            >
              <!-- Header -->
              <div class="flex items-center justify-between p-5 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50 rounded-t-2xl">
                <div class="flex items-center gap-3">
                  <div class="p-2 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl shadow">
                    <i class="fa-solid fa-key text-white text-sm" />
                  </div>
                  <div>
                    <h3 class="font-bold text-gray-900 text-sm">Cuentas Demo</h3>
                    <p class="text-xs text-gray-500">{{ credentials.length }} roles disponibles · Solo en desarrollo</p>
                  </div>
                </div>
                <button
                  class="p-1.5 hover:bg-white/70 rounded-lg transition-colors text-gray-500 hover:text-gray-700"
                  type="button"
                  @click="closeModal"
                >
                  <i class="fa-solid fa-xmark text-sm" />
                </button>
              </div>

              <!-- Content -->
              <div class="p-4 space-y-2 max-h-[70vh] overflow-y-auto">
                <!-- Loading -->
                <div v-if="isLoading" class="flex items-center justify-center py-8">
                  <div class="w-7 h-7 border-4 border-orange-200 border-t-orange-500 rounded-full animate-spin" />
                </div>

                <!-- Error -->
                <div v-else-if="error" class="text-center py-4">
                  <i class="fa-solid fa-triangle-exclamation text-red-400 text-xl mb-2" />
                  <p class="text-sm text-red-600 mb-3">{{ error }}</p>
                  <button class="text-xs text-red-600 underline hover:no-underline" type="button" @click="refresh">
                    Reintentar
                  </button>
                </div>

                <!-- Lista de credenciales -->
                <template v-else-if="credentials.length">
                  <div
                    v-for="cred in credentials"
                    :key="cred.role"
                    class="group relative p-3.5 border border-gray-200/70 rounded-xl hover:shadow-md transition-all duration-200 cursor-pointer hover:border-gray-300"
                    @click="handleFill(cred)"
                  >
                    <div class="flex items-center justify-between gap-3">
                      <!-- Izquierda: icono + info -->
                      <div class="flex items-center gap-3 min-w-0">
                        <div
                          class="w-9 h-9 rounded-xl flex items-center justify-center shadow flex-shrink-0"
                          :style="getGradientStyle(cred.role)"
                        >
                          <i class="fa-solid fa-user text-white text-sm" />
                        </div>
                        <div class="min-w-0">
                          <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-sm text-gray-900">{{ cred.label }}</span>
                            <span
                              class="text-[10px] font-bold px-1.5 py-0.5 rounded-full uppercase tracking-wide"
                              :style="getBadgeStyle(cred.role)"
                            >
                              {{ ROLE_LABELS[cred.role?.toLowerCase()] ?? cred.role }}
                            </span>
                          </div>
                          <div class="text-xs text-gray-500 truncate">{{ cred.email }}</div>
                          <div class="text-xs text-gray-400 truncate">{{ cred.description }}</div>
                          <Transition
                            enter-active-class="duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                          >
                            <div
                              v-if="showPasswordFor === cred.id"
                              class="mt-1 text-xs font-mono text-orange-700 bg-orange-50 border border-orange-200 rounded px-2 py-0.5 inline-flex items-center gap-1"
                            >
                              <i class="fa-solid fa-key text-[10px]" /> {{ cred.password }}
                            </div>
                          </Transition>
                        </div>
                      </div>

                      <!-- Derecha: acciones -->
                      <div class="flex items-center gap-1 flex-shrink-0">
                        <button
                          :title="showPasswordFor === cred.id ? 'Ocultar' : 'Mostrar contraseña'"
                          class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                          type="button"
                          @click.stop="togglePassword(cred.id)"
                        >
                          <i :class="showPasswordFor === cred.id ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm" />
                        </button>
                        <button
                          :disabled="fillingId === cred.id"
                          title="Rellenar formulario"
                          class="p-1.5 rounded-lg text-orange-500 hover:text-orange-700 hover:bg-orange-100 transition-all disabled:opacity-50"
                          type="button"
                          @click.stop="handleFill(cred)"
                        >
                          <i v-if="fillingId === cred.id" class="fa-solid fa-spinner animate-spin text-sm" />
                          <i v-else class="fa-solid fa-right-to-bracket text-sm" />
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Footer hint -->
                  <div class="flex items-center justify-between text-xs text-gray-400 pt-1 px-1">
                    <span>Click en una cuenta para rellenar el formulario</span>
                    <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-200 rounded font-mono text-gray-500">Ctrl+D</kbd>
                  </div>
                </template>

                <!-- Sin credenciales -->
                <div v-else class="text-center py-6 text-gray-400 text-sm">
                  No hay cuentas demo disponibles
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useDemoCredentials, type DemoCredential } from '@js/composables/auth/useDemoCredentials'
import { ROLE_COLORS, ROLE_BADGE, ROLE_LABELS } from '@js/composables/auth/useDemoCredentialsHelpers'

const emit = defineEmits<{
  fillCredentials: [{ login: string; password: string }]
}>()

const { credentials, isLoading, error, refresh } = useDemoCredentials()

const page = usePage<{ app: { env: string; debug: boolean } }>()
const isDev = computed(() => ['local', 'testing'].includes(page.props.app?.env ?? ''))
const isOpen = ref(false)
const showPasswordFor = ref<string | null>(null)
const fillingId = ref<string | null>(null)

const toggleModal = () => { isOpen.value = !isOpen.value }
const closeModal  = () => { isOpen.value = false }

const togglePassword = (id: string) => {
  showPasswordFor.value = showPasswordFor.value === id ? null : id
}

const handleFill = async (cred: DemoCredential) => {
  if (fillingId.value) return
  fillingId.value = cred.id
  await new Promise(resolve => setTimeout(resolve, 150))
  emit('fillCredentials', { login: cred.email, password: cred.password })
  closeModal()
  fillingId.value = null
}

const handleKeydown = (e: KeyboardEvent) => {
  if (!isDev.value) return
  if (e.key === 'Escape' && isOpen.value) { closeModal(); return }
  if (e.ctrlKey && e.key.toLowerCase() === 'd') {
    e.preventDefault()
    toggleModal()
  }
}

const getGradientStyle = (role: string) => {
  const gradients: Record<string, string> = {
    admin: 'linear-gradient(to bottom right, #3b82f6, #1d4ed8)',
    commerce: 'linear-gradient(to bottom right, #fb923c, #f59e0b)',
    seller: 'linear-gradient(to bottom right, #14b8a6, #06b6d4)',
    customer: 'linear-gradient(to bottom right, #8b5cf6, #7c3aed)',
    player: 'linear-gradient(to bottom right, #9ca3af, #4b5563)',
  }
  return {
    background: gradients[role?.toLowerCase()] || gradients.player
  }
}

const getBadgeStyle = (role: string) => {
  const badges: Record<string, { bg: string; color: string }> = {
    admin: { bg: '#dbeafe', color: '#1d4ed8' },
    commerce: { bg: '#fed7aa', color: '#ea580c' },
    seller: { bg: '#ccfbf1', color: '#0d9488' },
    customer: { bg: '#ede9fe', color: '#6d28d9' },
    player: { bg: '#f3f4f6', color: '#4b5563' },
  }
  const badge = badges[role?.toLowerCase()] || badges.player
  return {
    backgroundColor: badge.bg,
    color: badge.color
  }
}

onMounted(() => document.addEventListener('keydown', handleKeydown))
onUnmounted(() => document.removeEventListener('keydown', handleKeydown))
</script>
