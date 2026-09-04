<!--
  Página de estado de una PaymentOrder USDT-TRC20. Ver
  PAYMENTS_USDT_TRC20_DESIGN.md sección 8.

  Muestra la dirección de depósito (QR + texto) y el monto exacto a enviar
  como texto separado — nunca codificado dentro del QR junto con la
  dirección, porque ninguna wallet mayoritaria (TronLink, Trust Wallet,
  imToken, Klever) comparte un esquema de URI común al escanear (ver
  QrCodeService del backend). El estado se sigue vía WebSocket con fallback
  de polling (usePaymentStatus).
-->
<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-900 via-pink-900 to-red-900">
    <header class="bg-black/30 backdrop-blur-md border-b border-white/10">
      <div class="max-w-3xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Pago con USDT (TRON)</h1>
        <NuxtLink to="/player/lobby" class="text-white hover:text-gray-300 transition">
          <Icon name="lucide:arrow-left" class="h-6 w-6 inline mr-2" />
          Volver
        </NuxtLink>
      </div>
    </header>

    <main class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div v-if="loading" class="flex justify-center py-16">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500" />
      </div>

      <div v-else-if="loadError" class="bg-red-500/20 border border-red-500/40 rounded-2xl p-6 text-center">
        <p class="text-red-200">{{ loadError }}</p>
      </div>

      <div v-else class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 p-6 space-y-6">
        <!-- Estado -->
        <div class="flex items-center justify-between">
          <span class="text-gray-300 text-sm">Estado</span>
          <span :class="statusBadgeClass" class="px-3 py-1 rounded-full text-sm font-medium">
            {{ statusLabel }}
          </span>
        </div>

        <template v-if="!isTerminal || order.status === 'CREDITED'">
          <!-- QR + dirección -->
          <div v-if="order.status === 'PENDING' || order.status === 'DETECTED' || order.status === 'CONFIRMING'" class="text-center space-y-4">
            <div class="bg-white rounded-xl p-4 inline-block">
              <img :src="qrUrl(orderId)" alt="QR de depósito" class="w-64 h-64" />
            </div>

            <div>
              <p class="text-gray-300 text-sm mb-1">Enviar exactamente</p>
              <p class="text-3xl font-bold text-white">{{ order.expected_amount }} USDT</p>
              <p class="text-gray-400 text-xs mt-1">Red TRON (TRC20) — el monto debe coincidir exacto</p>
            </div>

            <div class="bg-black/30 rounded-lg p-3">
              <p class="text-gray-400 text-xs mb-1">Dirección de depósito</p>
              <p class="text-white font-mono text-sm break-all">{{ order.deposit_address }}</p>
              <button
                class="mt-2 text-xs text-blue-300 hover:text-blue-200 transition"
                @click="copyAddress"
              >
                <Icon name="lucide:copy" class="inline h-3 w-3 mr-1" />
                {{ copied ? 'Copiado' : 'Copiar dirección' }}
              </button>
            </div>

            <p v-if="order.expires_at" class="text-gray-400 text-xs">
              Expira: {{ formatExpiry(order.expires_at) }}
            </p>
          </div>

          <!-- Éxito -->
          <div v-if="order.status === 'CREDITED'" class="text-center space-y-3 py-6">
            <Icon name="lucide:check-circle" class="h-16 w-16 text-green-400 mx-auto" />
            <p class="text-white text-lg font-semibold">Pago confirmado</p>
            <p class="text-gray-300 text-sm">Tu compra fue acreditada.</p>
          </div>
        </template>

        <!-- Estados terminales sin éxito -->
        <div v-else class="text-center space-y-3 py-6">
          <Icon name="lucide:x-circle" class="h-16 w-16 text-red-400 mx-auto" />
          <p class="text-white text-lg font-semibold">{{ statusLabel }}</p>
          <p v-if="order.status === 'UNDERPAID'" class="text-gray-300 text-sm">
            El monto recibido fue menor al esperado — contactá a soporte con el hash de tu transacción.
          </p>
          <p v-else-if="order.status === 'OVERPAID'" class="text-gray-300 text-sm">
            El monto recibido excede lo esperado — un administrador revisará tu pago manualmente.
          </p>
        </div>

        <p class="text-gray-500 text-xs text-center">
          {{ isConnected ? 'Actualización en tiempo real activa' : 'Verificando estado periódicamente…' }}
        </p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['auth'],
  layout: 'player',
})

const route = useRoute()
const orderId = route.params.id as string

const { getOrder, qrUrl } = usePaymentsApi()
const { order, isConnected, isTerminal, start } = usePaymentStatus(orderId)

const loading = ref(true)
const loadError = ref('')
const copied = ref(false)

const statusLabels: Record<string, string> = {
  PENDING: 'Esperando pago',
  DETECTED: 'Pago detectado',
  CONFIRMING: 'Confirmando en la blockchain',
  CONFIRMED: 'Confirmado',
  UNDERPAID: 'Monto insuficiente',
  OVERPAID: 'Monto excedido — en revisión',
  EXPIRED: 'Expirado',
  FAILED: 'Fallido',
  CANCELLED: 'Cancelado',
  CREDITED: 'Acreditado',
}

const statusLabel = computed(() => statusLabels[order.value.status ?? ''] ?? order.value.status ?? '')

const statusBadgeClass = computed(() => {
  const status = order.value.status
  if (status === 'CREDITED') return 'bg-green-500/20 text-green-300'
  if (['UNDERPAID', 'OVERPAID', 'EXPIRED', 'FAILED', 'CANCELLED'].includes(status ?? '')) {
    return 'bg-red-500/20 text-red-300'
  }
  return 'bg-yellow-500/20 text-yellow-300'
})

const formatExpiry = (iso: string) => new Date(iso).toLocaleString()

const copyAddress = async () => {
  if (!order.value.deposit_address) return
  await navigator.clipboard.writeText(order.value.deposit_address)
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}

onMounted(async () => {
  try {
    const response = await getOrder(orderId)
    order.value = response.data
  } catch (e) {
    loadError.value = 'No se pudo cargar la orden de pago.'
  } finally {
    loading.value = false
  }

  if (!loadError.value) {
    await start()
  }
})
</script>
