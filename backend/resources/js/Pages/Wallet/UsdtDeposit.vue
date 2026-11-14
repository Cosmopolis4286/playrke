<script setup lang="ts">
/**
 * Depósito USDT-TRC20 con monto único: el jugador pide un monto, paga EXACTO
 * el monto mostrado a la dirección (QR) y el servidor lo detecta en la red y
 * acredita solo. Si pagó otro monto o tarde, reporta el txid y tesorería lo
 * revisa. Ver UsdtDepositService / UsdtDepositWatcher.
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

type OrderStatus = 'PENDING' | 'CONFIRMING' | 'CREDITED' | 'EXPIRED' | 'MANUAL_REVIEW' | 'REJECTED'

interface DepositOrder {
  id: string
  status: OrderStatus
  requested_amount: number
  pay_amount: string
  deposit_address: string
  tx_hash: string | null
  received_amount: number | null
  credited_amount: number | null
  review_reason: string | null
  expires_at: string | null
  created_at: string | null
}

const props = defineProps<{
  enabled: boolean
  network: string
  windowMinutes: number
  order: DepositOrder | null
}>()

const order = ref<DepositOrder | null>(props.order)
const amount = ref<number | null>(null)
const creating = ref(false)
const txHash = ref('')
const claiming = ref(false)
const now = ref(Date.now())

let pollTimer: ReturnType<typeof setInterval> | null = null
let clockTimer: ReturnType<typeof setInterval> | null = null

const isWaiting = computed(() => order.value?.status === 'PENDING' || order.value?.status === 'CONFIRMING')
const canClaim = computed(() => order.value?.status === 'PENDING' || order.value?.status === 'EXPIRED')
const secondsLeft = computed(() => {
  if (!order.value?.expires_at) return 0
  return Math.max(0, Math.floor((new Date(order.value.expires_at).getTime() - now.value) / 1000))
})
const countdown = computed(() => {
  const m = Math.floor(secondsLeft.value / 60)
  const s = secondsLeft.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})
const qrUrl = computed(() => order.value ? `/wallet/usdt-deposits/${order.value.id}/qr` : '')

function errorMessage(error: unknown, fallback: string): string {
  const data = (error as { response?: { data?: { message?: string } } })?.response?.data
  return data?.message ?? fallback
}

async function createOrder() {
  if (!amount.value || amount.value <= 0) return
  creating.value = true
  try {
    const { data } = await backendClient.post<{ data: DepositOrder }>('/wallet/usdt-deposits', { amount: amount.value })
    order.value = data.data
    txHash.value = ''
  } catch (error) {
    showToast(errorMessage(error, 'No se pudo generar el pago.'), 'error')
  } finally {
    creating.value = false
  }
}

async function refresh() {
  if (!order.value) return
  try {
    const { data } = await backendClient.get<{ data: DepositOrder }>(`/wallet/usdt-deposits/${order.value.id}`)
    const previous = order.value.status
    order.value = data.data
    if (previous !== 'CREDITED' && data.data.status === 'CREDITED') {
      showToast(`¡Listo! Acreditamos ${data.data.credited_amount} USDT en tu billetera.`, 'success')
    }
  } catch {
    // silencioso: se reintenta en el próximo ciclo
  }
}

async function claim() {
  if (!order.value || !txHash.value.trim()) return
  claiming.value = true
  try {
    const { data } = await backendClient.post<{ data: DepositOrder, message: string }>(
      `/wallet/usdt-deposits/${order.value.id}/claim`,
      { tx_hash: txHash.value.trim() }
    )
    order.value = data.data
    showToast(data.message, 'success')
  } catch (error) {
    showToast(errorMessage(error, 'No se pudo enviar el reporte.'), 'error')
  } finally {
    claiming.value = false
  }
}

async function copy(text: string, label: string) {
  try {
    await navigator.clipboard.writeText(text)
    showToast(`${label} copiado.`, 'success')
  } catch {
    showToast('No se pudo copiar; selecciónalo y cópialo a mano.', 'warning')
  }
}

function startNew() {
  order.value = null
  amount.value = null
  txHash.value = ''
}

function syncPolling() {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = isWaiting.value || order.value?.status === 'MANUAL_REVIEW' ? setInterval(refresh, 10_000) : null
}

watch(() => order.value?.status, syncPolling)

onMounted(() => {
  syncPolling()
  clockTimer = setInterval(() => { now.value = Date.now() }, 1000)
})

onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (clockTimer) clearInterval(clockTimer)
})
</script>

<template>
  <PageHeader
    icon="fa-coins"
    title="Depositar USDT"
    :subtitle="`Recarga tu billetera con USDT por la red ${network}.`"
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Mi Billetera', route: '/wallet' },
      { label: 'Depositar USDT' },
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div v-if="!enabled" class="alert alert-info">
        <i class="fas fa-info-circle mr-1" /> Los depósitos con USDT no están disponibles por ahora.
      </div>

      <!-- Paso 1: monto -->
      <div v-else-if="!order" class="card">
        <div class="card-body">
          <label for="usdt-amount" class="font-weight-bold">¿Cuántos USDT quieres depositar?</label>
          <div class="input-group mb-2">
            <input
              id="usdt-amount"
              v-model.number="amount"
              type="number"
              min="1"
              step="0.01"
              class="form-control form-control-lg"
              placeholder="Ej. 10"
              @keyup.enter="createOrder"
            >
            <div class="input-group-append">
              <span class="input-group-text">USDT</span>
            </div>
          </div>
          <small class="text-muted d-block mb-3">
            Te daremos un monto exacto con decimales únicos para identificar tu pago. Tendrás {{ windowMinutes }} minutos para enviarlo.
          </small>
          <button
            type="button"
            class="btn btn-primary btn-block"
            :disabled="creating || !amount"
            @click="createOrder"
          >
            <i class="fas" :class="creating ? 'fa-spinner fa-spin' : 'fa-qrcode'" /> Generar pago
          </button>
        </div>
      </div>

      <template v-else>
        <!-- Esperando el pago -->
        <div v-if="order.status === 'PENDING'" class="card">
          <div class="card-body text-center">
            <p class="mb-1 text-muted">
              Envía <strong>exactamente</strong>
            </p>
            <h2 class="mb-1 font-weight-bold usdt-amount">
              {{ order.pay_amount }} USDT
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary ml-1"
                title="Copiar monto"
                @click="copy(order.pay_amount, 'Monto')"
              >
                <i class="fas fa-copy" />
              </button>
            </h2>
            <p class="small text-muted mb-3">
              Red <strong>{{ network }}</strong> · vence en <strong>{{ countdown }}</strong>
            </p>

            <img
              :src="qrUrl"
              alt="QR de la dirección de depósito"
              class="usdt-qr mb-3"
              width="220"
              height="220"
            >

            <div class="input-group mb-3">
              <input
                :value="order.deposit_address"
                type="text"
                class="form-control text-monospace"
                readonly
                aria-label="Dirección de depósito"
              >
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" @click="copy(order.deposit_address, 'Dirección')">
                  <i class="fas fa-copy" /> Copiar
                </button>
              </div>
            </div>

            <div class="alert alert-warning text-left small mb-0">
              <ul class="pl-3 mb-0">
                <li>Usa solo la red <strong>TRC20 (TRON)</strong>; otra red hace que el pago se pierda.</li>
                <li>El monto debe <strong>llegar</strong> exacto, con todos sus decimales. Si tu exchange descuenta comisión de retiro, súmala al enviar.</li>
                <li>Acreditamos {{ order.requested_amount }} USDT apenas el pago se confirma (1-3 minutos).</li>
              </ul>
            </div>
          </div>
        </div>

        <div v-else-if="order.status === 'CONFIRMING'" class="alert alert-info">
          <i class="fas fa-spinner fa-spin mr-1" /> Detectamos tu pago de {{ order.received_amount }} USDT. Esperando confirmaciones de la red…
        </div>

        <div v-else-if="order.status === 'CREDITED'" class="alert alert-success">
          <i class="fas fa-check-circle mr-1" /> Acreditamos <strong>{{ order.credited_amount }} USDT</strong> en tu billetera.
          <div class="mt-2">
            <a href="/wallet" class="btn btn-sm btn-success mr-2">Ir a mi billetera</a>
            <button type="button" class="btn btn-sm btn-outline-success" @click="startNew">
              Nuevo depósito
            </button>
          </div>
        </div>

        <div v-else-if="order.status === 'EXPIRED'" class="alert alert-secondary">
          <i class="fas fa-clock mr-1" /> Este pago venció sin recibir el monto exacto.
          <button type="button" class="btn btn-sm btn-outline-secondary ml-2" @click="startNew">
            Generar otro
          </button>
        </div>

        <div v-else-if="order.status === 'MANUAL_REVIEW'" class="alert alert-info">
          <i class="fas fa-user-check mr-1" /> Tu pago está en revisión de tesorería. Te avisaremos cuando se acredite.
          <small v-if="order.tx_hash" class="d-block text-monospace text-break mt-1">txid: {{ order.tx_hash }}</small>
        </div>

        <div v-else-if="order.status === 'REJECTED'" class="alert alert-danger">
          <i class="fas fa-times-circle mr-1" /> Tesorería no pudo acreditar este pago.
          <span v-if="order.review_reason">Motivo: {{ order.review_reason }}</span>
          <button type="button" class="btn btn-sm btn-outline-danger ml-2" @click="startNew">
            Nuevo depósito
          </button>
        </div>

        <!-- Reportar txid -->
        <div v-if="canClaim" class="card">
          <div class="card-body">
            <p class="mb-2">
              <strong>¿Ya pagaste y no se acredita?</strong>
              <span class="text-muted">Pega el txid (hash de la transacción) y tesorería lo revisa.</span>
            </p>
            <div class="input-group">
              <input
                v-model="txHash"
                type="text"
                class="form-control text-monospace"
                placeholder="txid de 64 caracteres"
                aria-label="txid"
              >
              <div class="input-group-append">
                <button
                  type="button"
                  class="btn btn-outline-primary"
                  :disabled="claiming || !txHash.trim()"
                  @click="claim"
                >
                  <i class="fas" :class="claiming ? 'fa-spinner fa-spin' : 'fa-paper-plane'" /> Reportar
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.usdt-amount {
  word-break: break-all;
}

.usdt-qr {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}
</style>
