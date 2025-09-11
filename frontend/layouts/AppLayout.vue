<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-blue-900">
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
        v-if="sidebarOpen"
        class="fixed inset-y-0 left-0 z-40 w-64 bg-white/80 backdrop-blur-xl border-r border-gray-200/50 shadow-xl lg:relative lg:translate-x-0"
      >
        <!-- Logo -->
        <div class="flex items-center gap-3 p-6 border-b border-gray-200/50">
          <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl text-white font-bold text-lg">
            P
          </div>
          <div>
            <h1 class="text-xl font-bold text-gray-900">PlayRKE</h1>
            <p class="text-xs text-gray-500">Gaming Platform</p>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-2">
          <NavItem
            v-for="item in navigation"
            :key="item.name"
            :item="item"
            :active="isActive(item.href)"
          />
        </nav>

        <!-- User Section -->
        <div class="p-4 border-t border-gray-200/50">
          <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
            <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
              {{ user?.name?.charAt(0) || 'U' }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ user?.name || 'Usuario' }}
              </p>
              <p class="text-xs text-gray-500 truncate">
                {{ user?.email || 'usuario@playrke.com' }}
              </p>
            </div>
            <button
              @click="logout"
              class="p-2 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors"
              title="Cerrar Sesión"
            >
              <ArrowRightOnRectangleIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </aside>
    </Transition>

    <!-- Mobile backdrop -->
    <div
      v-if="sidebarOpen && isMobile"
      class="fixed inset-0 z-30 bg-black/20 backdrop-blur-sm lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Main Content -->
    <div class="flex flex-col lg:ml-64 min-h-screen">
      <!-- Header -->
      <header class="bg-white/80 backdrop-blur-xl border-b border-gray-200/50 shadow-sm sticky top-0 z-20">
        <div class="flex items-center justify-between p-4">
          <!-- Mobile menu button -->
          <button
            class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors lg:hidden"
            @click="sidebarOpen = !sidebarOpen"
          >
            <Bars3Icon class="w-6 h-6" />
          </button>

          <!-- Page Title -->
          <div class="flex-1 lg:ml-0 ml-4">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ pageTitle }}
            </h1>
            <p v-if="pageSubtitle" class="text-sm text-gray-500 mt-1">
              {{ pageSubtitle }}
            </p>
          </div>

          <!-- Header Actions -->
          <div class="flex items-center gap-3">
            <!-- Notifications -->
            <button
              class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
              @click="showNotifications = !showNotifications"
            >
              <BellIcon class="w-6 h-6" />
              <span
                v-if="notificationCount > 0"
                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse"
              >
                {{ notificationCount > 9 ? '9+' : notificationCount }}
              </span>
            </button>

            <!-- Theme Toggle -->
            <button
              class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
              @click="toggleTheme"
            >
              <SunIcon v-if="isDark" class="w-6 h-6" />
              <MoonIcon v-else class="w-6 h-6" />
            </button>

            <!-- Settings -->
            <button
              class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <CogIcon class="w-6 h-6" />
            </button>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-6">
        <Transition
          enter-active-class="transition-all duration-500 ease-out"
          enter-from-class="opacity-0 translate-y-4"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-300 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-4"
          mode="out-in"
        >
          <slot />
        </Transition>
      </main>

      <!-- Footer -->
      <footer class="bg-white/50 backdrop-blur-sm border-t border-gray-200/50 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="text-sm text-gray-500">
            © {{ currentYear }} PlayRKE. Todos los derechos reservados.
          </div>
          <div class="flex items-center gap-4">
            <a href="#" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
              Términos
            </a>
            <a href="#" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
              Privacidad
            </a>
            <a href="#" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
              Soporte
            </a>
          </div>
        </div>
      </footer>
    </div>

    <!-- Notification Panel -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 translate-x-full"
      enter-to-class="opacity-100 translate-x-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-x-0"
      leave-to-class="opacity-0 translate-x-full"
    >
      <NotificationPanel
        v-if="showNotifications"
        @close="showNotifications = false"
      />
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useBreakpoints, breakpointsTailwind } from '@vueuse/core'
import {
  Bars3Icon,
  BellIcon,
  SunIcon,
  MoonIcon,
  CogIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'

// Components
import NavItem from '../components/layout/NavItem.vue'
import NotificationPanel from '../components/layout/NotificationPanel.vue'

// Props
interface Props {
  pageTitle?: string
  pageSubtitle?: string
}

const props = withDefaults(defineProps<Props>(), {
  pageTitle: 'Dashboard'
})

// Composables
const page = usePage()
const breakpoints = useBreakpoints(breakpointsTailwind)

// State
const sidebarOpen = ref(false)
const showNotifications = ref(false)
const isDark = ref(false)

// Computed
const user = computed(() => page.props.auth?.user)
const isMobile = computed(() => !breakpoints.lg.value)
const currentYear = computed(() => new Date().getFullYear())
const notificationCount = ref(3) // Mock data

// Navigation items
const navigation = [
  {
    name: 'Dashboard',
    href: '/dashboard',
    icon: 'HomeIcon'
  },
  {
    name: 'Juegos',
    href: '/games',
    icon: 'GameController2Icon',
    children: [
      { name: 'Bingo', href: '/games/bingo' },
      { name: 'Casino', href: '/games/casino' },
      { name: 'Deportes', href: '/games/sports' }
    ]
  },
  {
    name: 'Transacciones',
    href: '/transactions',
    icon: 'CreditCardIcon'
  },
  {
    name: 'Perfil',
    href: '/profile',
    icon: 'UserIcon'
  },
  {
    name: 'Configuración',
    href: '/settings',
    icon: 'CogIcon'
  }
]

// Methods
const isActive = (href: string) => {
  return page.url.startsWith(href)
}

const toggleTheme = () => {
  isDark.value = !isDark.value
  document.documentElement.setAttribute('data-theme', isDark.value ? 'dark' : 'light')
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
}

const logout = () => {
  router.post('/logout')
}

// Lifecycle
onMounted(() => {
  // Load theme preference
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme === 'dark') {
    isDark.value = true
    document.documentElement.setAttribute('data-theme', 'dark')
  }

  // Auto-close sidebar on desktop
  if (!isMobile.value) {
    sidebarOpen.value = true
  }
})
</script>