<script setup lang="ts">
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import '@fortawesome/fontawesome-free/css/all.min.css'

/**
 * Compra por cuenta propia de un comprador RECURRENTE desde el enlace
 * público de su vendedor (/comprar/{token}), sin cuenta en la plataforma.
 *
 * Pasos: código (país + teléfono + código que le dio su vendedor) → sala →
 * cartones y comprobante → listo (enlace para ver sus cartones). La compra
 * entra en revisión: los cartones aparecen cuando el comercio confirma el pago.
 *
 * Cliente HTTP propio a propósito: el backendClient global redirige a
 * /login ante un 401, y aquí un 401 solo significa "tu sesión de compra
 * venció, ingresa de nuevo tu código".
 */
interface Country { id: string; name: string; iso_code_2: string }
interface BuyRoom {
  id: string
  uuid_code: string
  time: string | null
  is_manual: boolean
  starts_at: string | null
  sales_close_at: string | null
  prize_pool: number
  total_players: number
}

const props = defineProps<{
  token: string
  sellerName: string
  commerceName: string | null
  countries: Country[]
}>()

const http = axios.create({
  baseURL: `/comprar/${props.token}`,
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
})

// Página pública: siempre oscura, como la vitrina de salas.
const previousTheme = document.documentElement.getAttribute('data-theme')
document.documentElement.setAttribute('data-theme', 'dark')
const previousBodyBg = document.body.style.backgroundColor
document.body.style.backgroundColor = '#060a18'
onUnmounted(() => {
  if (previousTheme) document.documentElement.setAttribute('data-theme', previousTheme)
  else document.documentElement.removeAttribute('data-theme')
  document.body.style.backgroundColor = previousBodyBg
})

type Step = 'verify' | 'buy' | 'done'
const step = ref<Step>('verify')
const error = ref<string | null>(null)
const busy = ref(false)

// ── Paso 1: código ──
const access = reactive({
  country_id: props.countries.length === 1 ? props.countries[0].id : '',
  phone: '',
  code: '',
})
const buyer = reactive({ name: '', remainingUses: 0, expiresAt: '' as string | null })

async function verify(): Promise<void> {
  error.value = null
  busy.value = true
  try {
    const { data } = await http.post('/verify', { ...access, code: access.code.trim() })
    buyer.name = data.buyer_name
    buyer.remainingUses = data.remaining_uses
    buyer.expiresAt = data.expires_at
    step.value = 'buy'
    await loadRooms()
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'No pudimos verificar tu código. Inténtalo de nuevo.'
  } finally {
    busy.value = false
  }
}

/** La sesión de compra venció o el código dejó de servir: volver al paso del código. */
function backToVerify(message: string): void {
  step.value = 'verify'
  access.code = ''
  error.value = message
  selectedRoomId.value = null
}

function handleError(e: any, fallback: string): void {
  const status = e?.response?.status
  const message = e?.response?.data?.message ?? fallback
  if (status === 401) backToVerify(message)
  else error.value = message
}

// ── Paso 2: sala ──
const today = (() => {
  const d = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
})()
const date = ref(today)
const rooms = ref<BuyRoom[]>([])
const loadingRooms = ref(false)
const selectedRoomId = ref<string | null>(null)
const selectedRoom = computed(() => rooms.value.find(r => r.id === selectedRoomId.value) ?? null)

async function loadRooms(): Promise<void> {
  loadingRooms.value = true
  error.value = null
  try {
    const { data } = await http.get('/rooms', { params: { date: date.value } })
    rooms.value = data.rooms ?? []
    if (!rooms.value.some(r => r.id === selectedRoomId.value)) selectedRoomId.value = null
  } catch (e) {
    handleError(e, 'No pudimos cargar las salas.')
  } finally {
    loadingRooms.value = false
  }
}
watch(date, () => { if (step.value === 'buy') loadRooms() })

// ── Paso 3: cartones y comprobante ──
const price = ref<{ card_price: number; currency_symbol: string; exchange_rate: number } | null>(null)
const loadingPrice = ref(false)
const purchase = reactive({
  cardCount: 1,
  paymentMethod: 'mobile_payment' as 'mobile_payment' | 'bank_transfer',
  receiptNumber: '',
  receiptImage: null as File | null,
  paid: false,
})
const QUICK = [1, 2, 5, 10]

watch(selectedRoomId, async id => {
  price.value = null
  if (!id) return
  loadingPrice.value = true
  try {
    const { data } = await http.get(`/rooms/${id}/price`)
    price.value = data
  } catch (e) {
    handleError(e, 'No pudimos obtener el precio del cartón.')
  } finally {
    loadingPrice.value = false
  }
})

const total = computed(() => (price.value?.card_price ?? 0) * purchase.cardCount)
const canSubmit = computed(() =>
  !!selectedRoom.value && !!price.value && purchase.cardCount >= 1 &&
  !!purchase.receiptNumber.trim() && !!purchase.receiptImage && purchase.paid && !busy.value
)

function setCount(n: number): void {
  purchase.cardCount = Math.max(1, Math.min(40, Math.round(n) || 1))
}

function onFile(e: Event): void {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  if (file && !/^image\/(jpeg|jpg|png)$/.test(file.type)) {
    error.value = 'El comprobante debe ser una imagen JPG o PNG.'
    return
  }
  if (file && file.size > 2 * 1024 * 1024) {
    error.value = 'La imagen no puede superar los 2 MB.'
    return
  }
  error.value = null
  purchase.receiptImage = file
}

const result = ref<{ message: string; playerLink: string | null; remaining_uses: number } | null>(null)

async function submit(): Promise<void> {
  if (!canSubmit.value || !selectedRoom.value || !price.value) return
  busy.value = true
  error.value = null

  const form = new FormData()
  form.append('card_count', String(purchase.cardCount))
  form.append('payment_method', purchase.paymentMethod)
  form.append('receipt_number', purchase.receiptNumber.trim())
  form.append('receipt_image', purchase.receiptImage!)
  form.append('expected_card_price', String(price.value.card_price))

  try {
    const { data } = await http.post(`/rooms/${selectedRoom.value.id}/purchase`, form)
    result.value = data
    buyer.remainingUses = data.remaining_uses
    step.value = 'done'
  } catch (e: any) {
    const code = e?.response?.data?.error_code
    handleError(e, 'No pudimos registrar tu compra.')
    // El precio cambió: recargarlo para que confirme el nuevo monto.
    if (code === 'BINGO_CARD_PRICE_CHANGED' && selectedRoomId.value) {
      const id = selectedRoomId.value
      selectedRoomId.value = null
      selectedRoomId.value = id
    }
  } finally {
    busy.value = false
  }
}

function buyAgain(): void {
  result.value = null
  purchase.cardCount = 1
  purchase.receiptNumber = ''
  purchase.receiptImage = null
  purchase.paid = false
  step.value = 'buy'
  loadRooms()
}

async function exit(): Promise<void> {
  try { await http.post('/forget') } catch { /* sin sesión que cerrar */ }
  backToVerify('')
  error.value = null
}

function money(n: number): string {
  return n.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function roomTime(r: BuyRoom): string {
  if (r.is_manual) return 'Inicio manual'
  return r.time ? r.time.slice(0, 5) : '—'
}
function closesAt(r: BuyRoom): string | null {
  return r.sales_close_at ? new Date(r.sales_close_at).toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' }) : null
}
</script>

<template>
  <main class="bbw">
    <div class="bbw__wrap">
      <header class="bbw__header">
        <h1><i class="fas fa-ticket-alt"></i> Compra tus cartones</h1>
        <p>
          Con <strong>{{ sellerName }}</strong><template v-if="commerceName && commerceName !== sellerName"> · {{ commerceName }}</template>
        </p>
      </header>

      <div v-if="error" class="bbw__alert bbw__alert--error" role="alert">
        <i class="fas fa-exclamation-circle"></i><span>{{ error }}</span>
      </div>

      <!-- ── Paso 1: código ── -->
      <section v-if="step === 'verify'" class="bbw__card">
        <h2>Ingresa tu código</h2>
        <p class="bbw__muted">
          Este enlace es para clientes que ya le compraron a {{ sellerName }}. Pídele tu código de compra si aún no lo tienes.
        </p>
        <form @submit.prevent="verify">
          <label class="bbw__label" for="bbw-country">País</label>
          <select id="bbw-country" v-model="access.country_id" class="bbw__input" required>
            <option value="" disabled>Elige tu país</option>
            <option v-for="c in countries" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>

          <label class="bbw__label" for="bbw-phone">Tu teléfono</label>
          <input id="bbw-phone" v-model="access.phone" class="bbw__input" type="tel" inputmode="tel" autocomplete="tel" placeholder="El mismo con el que compraste" required />

          <label class="bbw__label" for="bbw-code">Código de compra</label>
          <input
            id="bbw-code"
            v-model="access.code"
            class="bbw__input bbw__input--code"
            maxlength="12"
            autocomplete="one-time-code"
            autocapitalize="characters"
            placeholder="Ej. K7M2PQ9R"
            required
          />

          <button type="submit" class="bbw__btn bbw__btn--primary" :disabled="busy">
            <span v-if="busy" class="spinner-border spinner-border-sm"></span>
            <i v-else class="fas fa-unlock"></i> Continuar
          </button>
        </form>
      </section>

      <!-- ── Pasos 2 y 3: sala, cartones y comprobante ── -->
      <template v-else-if="step === 'buy'">
        <div class="bbw__buyer">
          <span>Hola, <strong>{{ buyer.name || 'cliente' }}</strong></span>
          <span class="bbw__muted">Te quedan {{ buyer.remainingUses }} compra(s) con tu código</span>
          <button type="button" class="bbw__link" @click="exit">Salir</button>
        </div>

        <section class="bbw__card">
          <h2>1. Elige la sala</h2>
          <input v-model="date" class="bbw__input" type="date" :min="today" aria-label="Fecha de la sala" />

          <p v-if="loadingRooms" class="bbw__muted">Cargando salas…</p>
          <p v-else-if="!rooms.length" class="bbw__muted">No hay salas a la venta para esta fecha.</p>
          <div v-else class="bbw__rooms">
            <button
              v-for="r in rooms"
              :key="r.id"
              type="button"
              class="bbw__room"
              :class="{ 'bbw__room--on': selectedRoomId === r.id }"
              @click="selectedRoomId = r.id"
            >
              <strong>{{ roomTime(r) }}</strong>
              <span>Sala #{{ r.uuid_code.slice(0, 8).toUpperCase() }}</span>
              <span class="bbw__muted">{{ r.total_players }} jugador(es)</span>
              <span v-if="closesAt(r)" class="bbw__muted">Venta hasta {{ closesAt(r) }}</span>
            </button>
          </div>
        </section>

        <section v-if="selectedRoom" class="bbw__card">
          <h2>2. Cartones y pago</h2>

          <p v-if="loadingPrice" class="bbw__muted">Calculando precio…</p>
          <template v-else-if="price">
            <label class="bbw__label">Cantidad de cartones</label>
            <div class="bbw__qty">
              <button type="button" class="bbw__step" :disabled="purchase.cardCount <= 1" @click="setCount(purchase.cardCount - 1)">−</button>
              <input :value="purchase.cardCount" type="number" min="1" max="40" class="bbw__input bbw__input--qty" @change="setCount(Number(($event.target as HTMLInputElement).value))" />
              <button type="button" class="bbw__step" :disabled="purchase.cardCount >= 40" @click="setCount(purchase.cardCount + 1)">+</button>
              <button v-for="n in QUICK" :key="n" type="button" class="bbw__chip" :class="{ 'bbw__chip--on': purchase.cardCount === n }" @click="setCount(n)">{{ n }}</button>
            </div>

            <div class="bbw__total">
              <span>{{ purchase.cardCount }} × {{ price.currency_symbol }} {{ money(price.card_price) }}</span>
              <strong>Total: {{ price.currency_symbol }} {{ money(total) }}</strong>
            </div>

            <label class="bbw__label">¿Cómo pagaste?</label>
            <div class="bbw__chips">
              <button type="button" class="bbw__chip" :class="{ 'bbw__chip--on': purchase.paymentMethod === 'mobile_payment' }" @click="purchase.paymentMethod = 'mobile_payment'">
                <i class="fas fa-mobile-alt"></i> Pago móvil
              </button>
              <button type="button" class="bbw__chip" :class="{ 'bbw__chip--on': purchase.paymentMethod === 'bank_transfer' }" @click="purchase.paymentMethod = 'bank_transfer'">
                <i class="fas fa-university"></i> Transferencia
              </button>
            </div>
            <p class="bbw__muted">Paga el total a los datos de pago que te dio {{ sellerName }} y sube el comprobante.</p>

            <label class="bbw__label" for="bbw-ref">Número de referencia</label>
            <input id="bbw-ref" v-model="purchase.receiptNumber" class="bbw__input" maxlength="100" autocomplete="off" placeholder="Ej. 00123456" />

            <label class="bbw__label" for="bbw-file">Foto del comprobante (JPG o PNG, máx. 2 MB)</label>
            <input id="bbw-file" class="bbw__input" type="file" accept="image/png,image/jpeg" @change="onFile" />

            <label class="bbw__check">
              <input v-model="purchase.paid" type="checkbox" />
              <span>Ya pagué <strong>{{ price.currency_symbol }} {{ money(total) }}</strong>.</span>
            </label>

            <button type="button" class="bbw__btn bbw__btn--primary" :disabled="!canSubmit" @click="submit">
              <span v-if="busy" class="spinner-border spinner-border-sm"></span>
              <i v-else class="fas fa-check"></i> Comprar {{ purchase.cardCount }} cartón(es)
            </button>
          </template>
        </section>
      </template>

      <!-- ── Listo ── -->
      <section v-else-if="step === 'done' && result" class="bbw__card bbw__card--done">
        <i class="fas fa-check-circle bbw__done-icon"></i>
        <h2>¡Recibimos tu compra!</h2>
        <p>{{ result.message }}</p>
        <a v-if="result.playerLink" :href="result.playerLink" class="bbw__btn bbw__btn--primary">
          <i class="fas fa-th"></i> Ver mis cartones
        </a>
        <p class="bbw__muted">Guarda ese enlace: ahí verás tus cartones y el sorteo.</p>
        <button v-if="buyer.remainingUses > 0" type="button" class="bbw__btn" @click="buyAgain">
          Comprar más ({{ buyer.remainingUses }} compra(s) disponibles)
        </button>
        <p v-else class="bbw__muted">Usaste todas las compras de tu código. Pídele uno nuevo a {{ sellerName }}.</p>
      </section>
    </div>
  </main>
</template>

<style scoped>
.bbw {
  min-height: 100vh;
  padding: 24px 16px 48px;
  background: radial-gradient(circle at top, #14204a 0%, #060a18 60%);
  color: #e2e8f0;
  font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
}
.bbw__wrap { max-width: 560px; margin: 0 auto; display: flex; flex-direction: column; gap: 14px; }
.bbw__header h1 { font-size: 24px; margin: 0; display: flex; gap: 10px; align-items: center; }
.bbw__header p { margin: 4px 0 0; color: #94a3b8; }
.bbw__card {
  padding: 18px;
  border-radius: 16px;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.18);
}
.bbw__card h2 { font-size: 17px; margin: 0 0 10px; }
.bbw__card--done { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.bbw__done-icon { font-size: 44px; color: #34d399; }
.bbw__muted { color: #94a3b8; font-size: 13px; margin: 6px 0; }
.bbw__label { display: block; margin: 12px 0 4px; font-size: 13px; font-weight: 600; color: #cbd5e1; }
.bbw__input {
  width: 100%;
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: #0b1224;
  color: #e2e8f0;
  font-size: 15px;
}
.bbw__input:focus { outline: 2px solid #818cf8; outline-offset: 1px; }
.bbw__input--code { text-transform: uppercase; letter-spacing: 3px; font-family: ui-monospace, monospace; }
.bbw__input--qty { width: 70px; text-align: center; }
.bbw__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  margin-top: 16px;
  padding: 12px 16px;
  border-radius: 12px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: transparent;
  color: #e2e8f0;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  text-decoration: none;
}
.bbw__btn--primary { background: linear-gradient(135deg, #667eea, #764ba2); border-color: transparent; color: #fff; }
.bbw__btn:disabled { opacity: 0.45; cursor: not-allowed; }
.bbw__alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 12px; font-size: 14px; }
.bbw__alert--error { background: rgba(248, 113, 113, 0.12); border: 1px solid rgba(248, 113, 113, 0.35); color: #fecaca; }
.bbw__buyer { display: flex; flex-wrap: wrap; gap: 4px 12px; align-items: center; font-size: 14px; }
.bbw__link { margin-left: auto; background: none; border: none; color: #a5b4fc; cursor: pointer; text-decoration: underline; }
.bbw__rooms { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 12px; }
.bbw__room {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 12px;
  border-radius: 12px;
  border: 1px solid rgba(148, 163, 184, 0.25);
  background: #0b1224;
  color: #e2e8f0;
  text-align: left;
  cursor: pointer;
}
.bbw__room--on { border-color: #818cf8; box-shadow: 0 0 0 2px rgba(129, 140, 248, 0.35); }
.bbw__room strong { font-size: 18px; }
.bbw__qty { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.bbw__step { width: 38px; height: 38px; border-radius: 10px; border: 1px solid rgba(148, 163, 184, 0.3); background: #0b1224; color: #e2e8f0; font-size: 18px; cursor: pointer; }
.bbw__step:disabled { opacity: 0.4; }
.bbw__chips { display: flex; flex-wrap: wrap; gap: 6px; }
.bbw__chip { padding: 8px 12px; border-radius: 999px; border: 1px solid rgba(148, 163, 184, 0.3); background: #0b1224; color: #e2e8f0; cursor: pointer; font-size: 13px; }
.bbw__chip--on { background: rgba(129, 140, 248, 0.2); border-color: #818cf8; color: #c7d2fe; }
.bbw__total { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-top: 12px; padding: 10px 12px; border-radius: 10px; background: rgba(129, 140, 248, 0.1); font-size: 14px; }
.bbw__check { display: flex; gap: 8px; align-items: flex-start; margin-top: 14px; font-size: 14px; cursor: pointer; }
.bbw__check input { margin-top: 3px; }
</style>
