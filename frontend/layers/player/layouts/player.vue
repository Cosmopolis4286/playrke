<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-900/80 backdrop-blur-lg border-b border-white/10 sticky top-0 z-40">
      <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
          <!-- Logo y nombre -->
          <div class="flex items-center gap-4">
            <button
              @click="toggleSidebar"
              class="lg:hidden text-white hover:text-purple-400 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <NuxtLink to="/player/lobby" class="flex items-center gap-2">
              <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-xl">B</span>
              </div>
              <span class="text-white font-bold text-xl hidden md:block">PlayRKE Bingo</span>
            </NuxtLink>
          </div>

          <!-- User menu -->
          <div class="flex items-center gap-4">
            <!-- Balance -->
            <div class="hidden md:flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2">
              <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
              </svg>
              <span class="text-white font-semibold">{{ formatBalance }}</span>
            </div>

            <!-- Notifications -->
            <button
              @click="toggleNotifications"
              class="relative text-white hover:text-purple-400 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span
                v-if="unreadNotifications > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"
              >
                {{ unreadNotifications }}
              </span>
            </button>

            <!-- User dropdown -->
            <div class="relative">
              <button
                @click="toggleUserMenu"
                class="flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2 hover:bg-white/20 transition-colors"
              >
                <img
                  :src="user?.avatar || '/images/default-avatar.png'"
                  alt="Avatar"
                  class="w-8 h-8 rounded-full"
                />
                <span class="text-white font-medium hidden md:block">{{ user?.username }}</span>
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Dropdown menu -->
              <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <div
                  v-if="showUserMenu"
                  class="absolute right-0 mt-2 w-48 bg-slate-800 rounded-lg shadow-xl border border-white/10 overflow-hidden"
                >
                  <NuxtLink
                    to="/player/profile"
                    class="block px-4 py-3 text-sm text-white hover:bg-white/10 transition-colors"
                    @click="showUserMenu = false"
                  >
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      Mi Perfil
                    </div>
                  </NuxtLink>
                  <NuxtLink
                    to="/player/wallet"
                    class="block px-4 py-3 text-sm text-white hover:bg-white/10 transition-colors"
                    @click="showUserMenu = false"
                  >
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                      </svg>
                      Mi Billetera
                    </div>
                  </NuxtLink>
                  <div class="border-t border-white/10">
                    <button
                      @click="handleLogout"
                      class="block w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-white/10 transition-colors"
                    >
                      <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Cerrar Sesión
                      </div>
                    </button>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main layout -->
    <div class="flex">
      <!-- Sidebar -->
      <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="-translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-300 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="-translate-x-full"
      >
        <aside
          v-if="isSidebarOpen"
          class="fixed lg:sticky top-16 left-0 h-[calc(100vh-4rem)] w-64 bg-slate-900/80 backdrop-blur-lg border-r border-white/10 z-30 overflow-y-auto"
        >
          <nav class="p-4 space-y-2">
            <NuxtLink
              to="/player/lobby"
              class="flex items-center gap-3 px-4 py-3 rounded-lg text-white hover:bg-white/10 transition-all"
              active-class="bg-purple-600 hover:bg-purple-700"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              <span class="font-medium">Lobby</span>
            </NuxtLink>

            <NuxtLink
              to="/player/history"
              class="flex items-center gap-3 px-4 py-3 rounded-lg text-white hover:bg-white/10 transition-all"
              active-class="bg-purple-600 hover:bg-purple-700"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="font-medium">Historial</span>
            </NuxtLink>

            <NuxtLink
              to="/player/wallet"
              class="flex items-center gap-3 px-4 py-3 rounded-lg text-white hover:bg-white/10 transition-all"
              active-class="bg-purple-600 hover:bg-purple-700"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              <span class="font-medium">Billetera</span>
            </NuxtLink>

            <NuxtLink
              to="/player/profile"
              class="flex items-center gap-3 px-4 py-3 rounded-lg text-white hover:bg-white/10 transition-all"
              active-class="bg-purple-600 hover:bg-purple-700"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span class="font-medium">Mi Perfil</span>
            </NuxtLink>

            <div class="border-t border-white/10 my-4"></div>

            <!-- Quick stats -->
            <div class="px-4 py-3">
              <p class="text-xs text-slate-400 mb-2">Estadísticas Rápidas</p>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-300">Juegos:</span>
                  <span class="text-white font-semibold">{{ playerStats.gamesPlayed }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-300">Victorias:</span>
                  <span class="text-green-400 font-semibold">{{ playerStats.wins }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-300">Tasa:</span>
                  <span class="text-purple-400 font-semibold">{{ playerStats.winRate }}%</span>
                </div>
              </div>
            </div>
          </nav>
        </aside>
      </Transition>

      <!-- Mobile overlay -->
      <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="isSidebarOpen"
          class="fixed inset-0 bg-black/50 backdrop-blur-sm z-20 lg:hidden"
          @click="closeSidebar"
        ></div>
      </Transition>

      <!-- Content -->
      <main class="flex-1 min-h-[calc(100vh-4rem)]">
        <slot />
      </main>
    </div>

    <!-- Global notifications -->
    <div class="fixed top-20 right-4 z-50 space-y-2 max-w-sm">
      <TransitionGroup
        enter-active-class="transition-all duration-300"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition-all duration-300"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
      >
        <div
          v-for="notification in activeNotifications"
          :key="notification.id"
          :class="[
            'p-4 rounded-lg shadow-xl border backdrop-blur-sm',
            notification.type === 'success' && 'bg-green-500/90 border-green-400',
            notification.type === 'error' && 'bg-red-500/90 border-red-400',
            notification.type === 'warning' && 'bg-yellow-500/90 border-yellow-400',
            notification.type === 'info' && 'bg-blue-500/90 border-blue-400'
          ]"
        >
          <div class="flex items-start gap-3">
            <div class="flex-1">
              <p class="text-white font-semibold">{{ notification.title }}</p>
              <p class="text-white/90 text-sm">{{ notification.message }}</p>
            </div>
            <button
              @click="removeNotification(notification.id)"
              class="text-white/80 hover:text-white"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useWalletStore } from '../../shared/stores/wallet'
import { useUIStore } from '../../shared/stores/ui'

const { user, logout } = useAuth()
const walletStore = useWalletStore()
const uiStore = useUIStore()

// Estados locales
const showUserMenu = ref(false)
const showNotifications = ref(false)
const isSidebarOpen = ref(true)
const unreadNotifications = ref(3)

// Player stats (ejemplo - deberían venir de una API)
const playerStats = ref({
  gamesPlayed: 156,
  wins: 23,
  winRate: 15
})

// Computed
const formatBalance = computed(() => {
  const mainWallet = walletStore.wallets.find(w => w.currency === 'USD')
  if (!mainWallet) return '$0.00'

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(mainWallet.balance)
})

const activeNotifications = computed(() => uiStore.activeNotifications)

// Métodos
const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
  if (showUserMenu.value) {
    showNotifications.value = false
  }
}

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value) {
    showUserMenu.value = false
  }
}

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const closeSidebar = () => {
  isSidebarOpen.value = false
}

const handleLogout = async () => {
  showUserMenu.value = false
  await logout()
}

const removeNotification = (id: string) => {
  uiStore.removeNotification(id)
}

// Click outside para cerrar menus
onMounted(() => {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement
    if (!target.closest('.relative')) {
      showUserMenu.value = false
      showNotifications.value = false
    }
  })
})

// Responsive sidebar
onMounted(() => {
  const handleResize = () => {
    if (window.innerWidth >= 1024) {
      isSidebarOpen.value = true
    } else {
      isSidebarOpen.value = false
    }
  }

  handleResize()
  window.addEventListener('resize', handleResize)

  onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
  })
})
</script>
