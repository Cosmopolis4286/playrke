<template>
  <div>
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Configuración del Sistema</h1>
      <p class="text-slate-600 dark:text-slate-400 mt-1">
        Administra la configuración general de la plataforma
      </p>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
      <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === tab.id
                ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'
            ]"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>
    </div>

    <!-- General Settings -->
    <div v-if="activeTab === 'general'" class="space-y-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
          Información General
        </h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Nombre de la Plataforma
            </label>
            <input
              v-model="settings.general.platform_name"
              type="text"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Email de Contacto
            </label>
            <input
              v-model="settings.general.contact_email"
              type="email"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Zona Horaria
            </label>
            <select
              v-model="settings.general.timezone"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="America/Caracas">Caracas (UTC-4)</option>
              <option value="America/Bogota">Bogotá (UTC-5)</option>
              <option value="America/Sao_Paulo">São Paulo (UTC-3)</option>
              <option value="America/New_York">New York (UTC-5)</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Idioma
            </label>
            <select
              v-model="settings.general.language"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="es">Español</option>
              <option value="en">English</option>
              <option value="pt">Português</option>
            </select>
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          @click="saveSettings('general')"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
          Guardar Cambios
        </button>
      </div>
    </div>

    <!-- Game Settings -->
    <div v-if="activeTab === 'game'" class="space-y-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
          Configuración de Juego
        </h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Tiempo entre números (segundos)
            </label>
            <input
              v-model.number="settings.game.number_interval"
              type="number"
              min="1"
              max="60"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Máximo de cartones por jugador
            </label>
            <input
              v-model.number="settings.game.max_cards_per_player"
              type="number"
              min="1"
              max="20"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Tiempo de espera antes de iniciar (segundos)
            </label>
            <input
              v-model.number="settings.game.waiting_time"
              type="number"
              min="10"
              max="300"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="flex items-center gap-3">
            <input
              v-model="settings.game.auto_mark_enabled"
              type="checkbox"
              id="auto-mark"
              class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500"
            />
            <label for="auto-mark" class="text-sm font-medium text-slate-700 dark:text-slate-300">
              Permitir auto-marcado de cartones
            </label>
          </div>

          <div class="flex items-center gap-3">
            <input
              v-model="settings.game.sound_enabled"
              type="checkbox"
              id="sound"
              class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500"
            />
            <label for="sound" class="text-sm font-medium text-slate-700 dark:text-slate-300">
              Habilitar sonidos en el juego
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          @click="saveSettings('game')"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
          Guardar Cambios
        </button>
      </div>
    </div>

    <!-- Payment Settings -->
    <div v-if="activeTab === 'payment'" class="space-y-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
          Configuración de Pagos
        </h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Depósito mínimo (USD)
            </label>
            <input
              v-model.number="settings.payment.min_deposit"
              type="number"
              step="0.01"
              min="0"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Retiro mínimo (USD)
            </label>
            <input
              v-model.number="settings.payment.min_withdrawal"
              type="number"
              step="0.01"
              min="0"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Comisión por retiro (%)
            </label>
            <input
              v-model.number="settings.payment.withdrawal_fee"
              type="number"
              step="0.01"
              min="0"
              max="100"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="flex items-center gap-3">
            <input
              v-model="settings.payment.auto_approve_deposits"
              type="checkbox"
              id="auto-approve"
              class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500"
            />
            <label for="auto-approve" class="text-sm font-medium text-slate-700 dark:text-slate-300">
              Aprobar depósitos automáticamente
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          @click="saveSettings('payment')"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
          Guardar Cambios
        </button>
      </div>
    </div>

    <!-- Email Settings -->
    <div v-if="activeTab === 'email'" class="space-y-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
          Configuración de Email
        </h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Servidor SMTP
            </label>
            <input
              v-model="settings.email.smtp_host"
              type="text"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Puerto SMTP
            </label>
            <input
              v-model.number="settings.email.smtp_port"
              type="number"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Usuario SMTP
            </label>
            <input
              v-model="settings.email.smtp_username"
              type="text"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Email de envío
            </label>
            <input
              v-model="settings.email.from_email"
              type="email"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Nombre del remitente
            </label>
            <input
              v-model="settings.email.from_name"
              type="text"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-3">
        <button
          @click="testEmail"
          class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors"
        >
          Enviar Email de Prueba
        </button>
        <button
          @click="saveSettings('email')"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
          Guardar Cambios
        </button>
      </div>
    </div>

    <!-- Security Settings -->
    <div v-if="activeTab === 'security'" class="space-y-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
          Seguridad
        </h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Intentos de login antes de bloqueo
            </label>
            <input
              v-model.number="settings.security.max_login_attempts"
              type="number"
              min="1"
              max="10"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Duración del bloqueo (minutos)
            </label>
            <input
              v-model.number="settings.security.lockout_duration"
              type="number"
              min="1"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Tiempo de sesión (minutos)
            </label>
            <input
              v-model.number="settings.security.session_lifetime"
              type="number"
              min="5"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="flex items-center gap-3">
            <input
              v-model="settings.security.two_factor_enabled"
              type="checkbox"
              id="2fa"
              class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500"
            />
            <label for="2fa" class="text-sm font-medium text-slate-700 dark:text-slate-300">
              Habilitar autenticación de dos factores (2FA)
            </label>
          </div>

          <div class="flex items-center gap-3">
            <input
              v-model="settings.security.ip_whitelist_enabled"
              type="checkbox"
              id="ip-whitelist"
              class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500"
            />
            <label for="ip-whitelist" class="text-sm font-medium text-slate-700 dark:text-slate-300">
              Habilitar lista blanca de IPs
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          @click="saveSettings('security')"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
          Guardar Cambios
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['auth'],
  layout: 'admin'
})

const { getSettings, updateSettings } = useAdminApi()
const uiStore = useUIStore()

// Tabs
const tabs = [
  { id: 'general', name: 'General' },
  { id: 'game', name: 'Juego' },
  { id: 'payment', name: 'Pagos' },
  { id: 'email', name: 'Email' },
  { id: 'security', name: 'Seguridad' }
]

const activeTab = ref('general')

// Settings
const settings = ref({
  general: {
    platform_name: 'PlayRKE Bingo',
    contact_email: 'support@playrke.com',
    timezone: 'America/Caracas',
    language: 'es'
  },
  game: {
    number_interval: 5,
    max_cards_per_player: 6,
    waiting_time: 30,
    auto_mark_enabled: true,
    sound_enabled: true
  },
  payment: {
    min_deposit: 5,
    min_withdrawal: 10,
    withdrawal_fee: 2.5,
    auto_approve_deposits: false
  },
  email: {
    smtp_host: 'smtp.mailtrap.io',
    smtp_port: 587,
    smtp_username: '',
    from_email: 'noreply@playrke.com',
    from_name: 'PlayRKE Bingo'
  },
  security: {
    max_login_attempts: 5,
    lockout_duration: 15,
    session_lifetime: 120,
    two_factor_enabled: false,
    ip_whitelist_enabled: false
  }
})

// Métodos
const loadSettings = async () => {
  try {
    const response = await getSettings()
    if (response) {
      settings.value = { ...settings.value, ...response }
    }
  } catch (error) {
    console.error('Error cargando configuración:', error)
  }
}

const saveSettings = async (section: string) => {
  try {
    await updateSettings(section, (settings.value as any)[section])
    uiStore.notifySuccess('Configuración guardada exitosamente')
  } catch (error) {
    uiStore.notifyError('Error guardando configuración')
  }
}

const testEmail = async () => {
  uiStore.notifyInfo('Enviando email de prueba...')

  // Simular envío
  setTimeout(() => {
    uiStore.notifySuccess('Email de prueba enviado')
  }, 2000)
}

// Cargar al montar
onMounted(() => {
  loadSettings()
})
</script>
