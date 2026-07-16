<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@js/Components/Common/Modal/Modal.vue'
import type { Wallet, WalletOperation, UserDirectory, PendingOperation } from '@js/types/wallet'
import type { WalletStep } from '@js/composables/wallet/useWallet'

const props = defineProps<{
  modelValue: boolean
  operation: WalletOperation
  step: WalletStep
  activeWallet: Wallet | null
  formattedBalance: string

  amount: string
  selectedQuickAmount: number | null
  receipt: File | null
  reference: string
  notes: string

  directories: UserDirectory[]
  directoriesLoading: boolean
  selectedDirectory: UserDirectory | null

  pendingOperation: PendingOperation | null
  pendingLoading: boolean

  loading: boolean
  errorMsg: string | null
  successMsg: string | null

  parsedAmount: number
  canGoToStep2: boolean
  canSubmitDeposit: boolean
  canSubmitWithdraw: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'update:amount', v: string): void
  (e: 'update:receipt', v: File | null): void
  (e: 'update:reference', v: string): void
  (e: 'update:notes', v: string): void
  (e: 'update:selectedDirectory', v: UserDirectory | null): void
  (e: 'select-quick-amount', v: number): void
  (e: 'clear-quick-amount'): void
  (e: 'go-to-step1'): void
  (e: 'go-to-step2'): void
  (e: 'submit'): void
  (e: 'close'): void
}>()

const quickAmounts = [10, 25, 50, 100, 200, 500]

const modalTitle = computed(() => {
  if (props.step === 3) return 'Solicitud enviada'
  if (props.step === 2) return props.operation === 'deposit' ? 'Comprobante de pago' : 'Seleccionar destino'
  return props.operation === 'deposit' ? 'Depositar fondos' : 'Retirar fondos'
})

const modalIcon = computed(() => {
  if (props.step === 3) return 'fa-check-circle'
  return props.operation === 'deposit' ? 'fa-arrow-down' : 'fa-arrow-up'
})

function formatAmount(value: number): string {
  return new Intl.NumberFormat('es-VE', {
    minimumFractionDigits: props.activeWallet?.currency.decimal_places ?? 2,
    maximumFractionDigits: props.activeWallet?.currency.decimal_places ?? 2,
  }).format(value)
}

function formatDate(iso: string): string {
  const date = new Date(iso)
  const now  = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  const diffH   = diffMs / 3600000
  const timeStr = date.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' })
  const dateStr = new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
  if (diffMin < 1)  return 'Ahora mismo'
  if (diffMin < 60) return `Hace ${diffMin} min · ${timeStr}`
  if (diffH < 24)   return `Hoy, ${timeStr}`
  if (diffH < 48)   return `Ayer, ${timeStr}`
  return `${dateStr} · ${timeStr}`
}

const receiptPreviewUrl = computed(() => {
  if (!props.receipt) return null
  if (!props.receipt.type.startsWith('image/')) return null
  return URL.createObjectURL(props.receipt)
})

function onAmountInput(e: Event) {
  emit('update:amount', (e.target as HTMLInputElement).value)
  emit('clear-quick-amount')
}

function onReceiptChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  emit('update:receipt', file)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    :title="modalTitle"
    :icon="modalIcon"
    size="sm"
    :static="true"
    @update:model-value="emit('update:modelValue', $event)"
  >

    <!-- ── Indicador de pasos (solo pasos 1 y 2) ────────────────────── -->
    <div v-if="step < 3" class="op-steps">
      <div class="op-steps__track">
        <div
          class="op-steps__item"
          :class="{ 'op-steps__item--active': step >= 1, 'op-steps__item--done': step > 1 }"
        >
          <div class="op-steps__circle">
            <i v-if="step > 1" class="fas fa-check"></i>
            <span v-else>1</span>
          </div>
          <span class="op-steps__label">Monto</span>
        </div>
        <div class="op-steps__line" :class="{ 'op-steps__line--done': step > 1 }"></div>
        <div
          class="op-steps__item"
          :class="{ 'op-steps__item--active': step >= 2 }"
        >
          <div class="op-steps__circle">
            <span>2</span>
          </div>
          <span class="op-steps__label">
            {{ operation === 'deposit' ? 'Comprobante' : 'Destino' }}
          </span>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════
         PASO 1 — Monto
         ════════════════════════════════════════════════════════════════ -->
    <template v-if="step === 1">

      <!-- Verificando pendiente -->
      <div v-if="pendingLoading" class="op-pending-checking">
        <span class="op-spinner op-spinner--indigo"></span>
        Verificando solicitudes activas...
      </div>

      <!-- BLOQUEADO: ya tiene pendiente -->
      <template v-else-if="pendingOperation">
        <div class="op-pending-block">
          <div class="op-pending-block__icon">
            <i class="fas fa-clock"></i>
          </div>
          <div class="op-pending-block__body">
            <p class="op-pending-block__title">Solicitud en cola</p>
            <p class="op-pending-block__text">
              Tienes {{ pendingOperation.type === 'DEPOSIT' ? 'un depósito' : 'un retiro' }} de
              <strong>{{ activeWallet?.currency.symbol }} {{ formatAmount(pendingOperation.amount) }}</strong>
              pendiente de confirmación.
            </p>
            <p class="op-pending-block__hint">
              No puedes hacer otra solicitud hasta que un administrador la procese.
            </p>
          </div>
        </div>

        <!-- Detalle del movimiento pendiente -->
        <div class="op-pending-detail">
          <div class="op-pending-detail__row">
            <span>Tipo</span>
            <span :class="pendingOperation.type === 'DEPOSIT' ? 'text--deposit' : 'text--withdraw'">
              <i :class="pendingOperation.type === 'DEPOSIT' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i>
              {{ pendingOperation.type === 'DEPOSIT' ? 'Depósito' : 'Retiro' }}
            </span>
          </div>
          <div class="op-pending-detail__row">
            <span>Monto</span>
            <strong>{{ activeWallet?.currency.symbol }} {{ formatAmount(pendingOperation.amount) }}</strong>
          </div>
          <div class="op-pending-detail__row">
            <span>Estado</span>
            <span class="op-pending-detail__badge">
              <span class="op-pending-detail__dot"></span> En revisión
            </span>
          </div>
          <div class="op-pending-detail__row">
            <span>Solicitado</span>
            <span>{{ formatDate(pendingOperation.created_at) }}</span>
          </div>
        </div>
      </template>

      <!-- LIBRE: puede solicitar -->
      <template v-else>
        <!-- Saldo actual -->
        <div class="op-balance">
          <div>
            <p class="op-balance__label">Saldo disponible</p>
            <p class="op-balance__amount">{{ activeWallet?.currency.symbol }} {{ formattedBalance }}</p>
          </div>
          <div class="op-balance__icon"><i class="fas fa-wallet"></i></div>
        </div>

        <!-- Input de monto -->
        <div class="op-field">
          <label class="op-field__label">
            Monto a {{ operation === 'deposit' ? 'depositar' : 'retirar' }}
          </label>
          <div class="op-field__input-wrap">
            <span class="op-field__symbol">{{ activeWallet?.currency.symbol }}</span>
            <input
              :value="amount"
              type="number"
              min="0.01"
              step="0.01"
              placeholder="0.00"
              class="op-field__input"
              :class="operation === 'deposit' ? 'op-input--deposit' : 'op-input--withdraw'"
              @input="onAmountInput"
            />
          </div>

          <!-- Montos rápidos -->
          <div class="op-quick">
            <p class="op-quick__label">Montos rápidos:</p>
            <div class="op-quick__grid">
              <button
                v-for="qa in quickAmounts"
                :key="qa"
                type="button"
                class="op-quick__btn"
                :class="{ 'op-quick__btn--active': selectedQuickAmount === qa }"
                @click="emit('select-quick-amount', qa)"
              >
                {{ activeWallet?.currency.symbol }} {{ qa }}
              </button>
            </div>
          </div>
        </div>

        <!-- Error paso 1 -->
        <Transition name="msg-fade">
          <div v-if="errorMsg" class="op-msg op-msg--error">
            <div class="op-msg__icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div><p class="op-msg__title">Atención</p><p>{{ errorMsg }}</p></div>
          </div>
        </Transition>
      </template>
    </template>

    <!-- ════════════════════════════════════════════════════════════════
         PASO 2A — Depósito: comprobante
         ════════════════════════════════════════════════════════════════ -->
    <template v-else-if="step === 2 && operation === 'deposit'">
      <!-- Resumen del monto -->
      <div class="op-summary">
        <i class="fas fa-arrow-down op-summary__icon op-summary__icon--deposit"></i>
        <div>
          <p class="op-summary__label">Depositando</p>
          <p class="op-summary__amount">{{ activeWallet?.currency.symbol }} {{ formatAmount(parsedAmount) }}</p>
        </div>
      </div>

      <div class="op-field">
        <label class="op-field__label">
          Comprobante de pago <span class="op-required">*</span>
        </label>
        <label class="op-file-drop" :class="{ 'op-file-drop--selected': receipt }">
          <input type="file" accept="image/jpeg,image/png,application/pdf" class="op-file-input" @change="onReceiptChange" />
          <template v-if="receipt">
            <i class="fas fa-file-check op-file-drop__icon op-file-drop__icon--ok"></i>
            <p class="op-file-drop__name">{{ receipt.name }}</p>
            <p class="op-file-drop__hint">Haz clic para cambiar</p>
          </template>
          <template v-else>
            <i class="fas fa-cloud-upload-alt op-file-drop__icon"></i>
            <p class="op-file-drop__name">Selecciona o arrastra el comprobante</p>
            <p class="op-file-drop__hint">JPG, PNG o PDF · máx. 5 MB</p>
          </template>
        </label>

        <!-- Previsualización de imagen -->
        <div v-if="receiptPreviewUrl" class="op-receipt-preview">
          <img :src="receiptPreviewUrl" alt="Vista previa del comprobante" class="op-receipt-preview__img" />
        </div>
      </div>

      <div class="op-field">
        <label class="op-field__label">Número de referencia <span class="op-optional">(opcional)</span></label>
        <input
          :value="reference"
          type="text"
          class="op-field__input op-input--deposit"
          placeholder="Ej: 00123456"
          maxlength="100"
          @input="emit('update:reference', ($event.target as HTMLInputElement).value)"
        />
      </div>

      <div class="op-field">
        <label class="op-field__label">Notas <span class="op-optional">(opcional)</span></label>
        <textarea
          :value="notes"
          class="op-field__textarea"
          rows="2"
          placeholder="Información adicional para el administrador..."
          maxlength="500"
          @input="emit('update:notes', ($event.target as HTMLTextAreaElement).value)"
        ></textarea>
      </div>

      <Transition name="msg-fade">
        <div v-if="errorMsg" class="op-msg op-msg--error">
          <div class="op-msg__icon"><i class="fas fa-exclamation-triangle"></i></div>
          <div><p class="op-msg__title">Error</p><p>{{ errorMsg }}</p></div>
        </div>
      </Transition>
    </template>

    <!-- ════════════════════════════════════════════════════════════════
         PASO 2B — Retiro: seleccionar directorio
         ════════════════════════════════════════════════════════════════ -->
    <template v-else-if="step === 2 && operation === 'withdraw'">
      <!-- Resumen del monto -->
      <div class="op-summary">
        <i class="fas fa-arrow-up op-summary__icon op-summary__icon--withdraw"></i>
        <div>
          <p class="op-summary__label">Retirando</p>
          <p class="op-summary__amount">{{ activeWallet?.currency.symbol }} {{ formatAmount(parsedAmount) }}</p>
        </div>
      </div>

      <!-- Lista de directorios -->
      <div class="op-field">
        <label class="op-field__label">¿A dónde enviamos el dinero?</label>

        <div v-if="directoriesLoading" class="op-dirs-loading">
          <span class="op-spinner op-spinner--indigo"></span>
          Cargando tus cuentas...
        </div>

        <div v-else-if="directories.length === 0" class="op-dirs-empty">
          <i class="fas fa-exclamation-circle op-dirs-empty__icon"></i>
          <div>
            <p class="op-dirs-empty__text">No tienes cuentas registradas para esta moneda.</p>
            <a href="/directory" class="op-dirs-empty__link">
              <i class="fas fa-plus-circle"></i> Ir a Directorio y agregar una
            </a>
          </div>
        </div>

        <div v-else class="op-dirs-list">
          <label
            v-for="dir in directories"
            :key="dir.id"
            class="op-dir-card"
            :class="{ 'op-dir-card--selected': selectedDirectory?.id === dir.id }"
          >
            <input
              type="radio"
              :value="dir"
              :checked="selectedDirectory?.id === dir.id"
              class="op-dir-card__radio"
              @change="emit('update:selectedDirectory', dir)"
            />
            <div class="op-dir-card__icon">
              <i :class="dir.type === 'pago_movil' ? 'fas fa-mobile-alt' : dir.type === 'pix' ? 'fas fa-barcode' : 'fab fa-bitcoin'"></i>
            </div>
            <div class="op-dir-card__info">
              <p class="op-dir-card__label">{{ dir.label }}</p>
              <p class="op-dir-card__detail">{{ dir.detail }}</p>
              <p v-if="dir.holder" class="op-dir-card__holder">{{ dir.holder }}</p>
            </div>
            <i v-if="selectedDirectory?.id === dir.id" class="fas fa-check-circle op-dir-card__check"></i>
          </label>
        </div>
      </div>

      <div class="op-field">
        <label class="op-field__label">Notas <span class="op-optional">(opcional)</span></label>
        <textarea
          :value="notes"
          class="op-field__textarea"
          rows="2"
          placeholder="Información adicional..."
          maxlength="500"
          @input="emit('update:notes', ($event.target as HTMLTextAreaElement).value)"
        ></textarea>
      </div>

      <Transition name="msg-fade">
        <div v-if="errorMsg" class="op-msg op-msg--error">
          <div class="op-msg__icon"><i class="fas fa-exclamation-triangle"></i></div>
          <div><p class="op-msg__title">Error</p><p>{{ errorMsg }}</p></div>
        </div>
      </Transition>
    </template>

    <!-- ════════════════════════════════════════════════════════════════
         PASO 3 — Confirmación
         ════════════════════════════════════════════════════════════════ -->
    <template v-else-if="step === 3">
      <div class="op-done">
        <div class="op-done__icon">
          <i class="fas fa-check"></i>
        </div>
        <h3 class="op-done__title">¡Solicitud enviada!</h3>
        <p class="op-done__text">{{ successMsg }}</p>
        <div class="op-done__detail">
          <i class="fas fa-info-circle"></i>
          {{ operation === 'deposit'
            ? 'Un administrador revisará tu comprobante y acreditará el monto a tu billetera.'
            : 'Un administrador procesará la transferencia a tu cuenta registrada.' }}
        </div>
      </div>
    </template>

    <!-- ── Footer dinámico ──────────────────────────────────────────── -->
    <template #footer>
      <!-- Paso 3: solo cerrar -->
      <template v-if="step === 3">
        <button type="button" class="op-btn op-btn--deposit" style="flex:1" @click="emit('close')">
          <i class="fas fa-check"></i> Entendido
        </button>
      </template>

      <!-- Paso 2: volver + confirmar -->
      <template v-else-if="step === 2">
        <button type="button" class="op-btn op-btn--cancel" :disabled="loading" @click="emit('go-to-step1')">
          <i class="fas fa-arrow-left"></i> Volver
        </button>
        <button
          type="button"
          class="op-btn"
          :class="operation === 'deposit' ? 'op-btn--deposit' : 'op-btn--withdraw'"
          :disabled="loading || (operation === 'deposit' ? !canSubmitDeposit : !canSubmitWithdraw)"
          @click="emit('submit')"
        >
          <span v-if="loading" class="op-btn__loading">
            <span class="op-spinner op-spinner--white"></span> Enviando...
          </span>
          <span v-else>
            <i class="fas fa-paper-plane"></i>
            {{ operation === 'deposit' ? 'Enviar solicitud' : 'Confirmar retiro' }}
          </span>
        </button>
      </template>

      <!-- Paso 1: cancelar + siguiente -->
      <template v-else>
        <button type="button" class="op-btn op-btn--cancel" @click="emit('close')">
          <i class="fas fa-times"></i> Cerrar
        </button>
        <button
          v-if="!pendingOperation && !pendingLoading"
          type="button"
          class="op-btn"
          :class="operation === 'deposit' ? 'op-btn--deposit' : 'op-btn--withdraw'"
          :disabled="!canGoToStep2"
          @click="emit('go-to-step2')"
        >
          Siguiente <i class="fas fa-arrow-right"></i>
        </button>
      </template>
    </template>
  </Modal>
</template>

<style scoped>
/* ── Verificando pendiente ── */
.op-pending-checking {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--ds-text-muted);
  font-size: 0.85rem;
  padding: 1.5rem 0;
  justify-content: center;
}

/* ── Bloqueo por pendiente ── */
.op-pending-block {
  display: flex;
  gap: 14px;
  background: rgba(245, 158, 11, .12);
  border: 1.5px solid rgba(245, 158, 11, .4);
  border-radius: 14px;
  padding: 1.1rem 1.25rem;
  margin-bottom: 1rem;
}

.op-pending-block__icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(245, 158, 11, .2);
  color: #d97706;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.op-pending-block__title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #d97706;
  margin: 0 0 4px;
}

.op-pending-block__text {
  font-size: 0.82rem;
  color: var(--ds-text-2);
  margin: 0 0 4px;
  line-height: 1.5;
}

.op-pending-block__hint {
  font-size: 0.75rem;
  color: #d97706;
  margin: 0;
}

/* ── Detalle del movimiento pendiente ── */
.op-pending-detail {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 0.25rem;
}

.op-pending-detail__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 14px;
  font-size: 0.82rem;
  color: var(--ds-text-2);
  border-bottom: 1px solid var(--ds-border);
}
.op-pending-detail__row:last-child { border-bottom: none; }
.op-pending-detail__row span:first-child { color: var(--ds-text-muted); }
.op-pending-detail__row strong { color: var(--ds-text); }

.op-pending-detail__badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(245, 158, 11, .2);
  color: #d97706;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 2px 9px;
  border-radius: 20px;
}

.op-pending-detail__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #f59e0b;
  animation: pulse-dot 1.5s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.3; }
}

.text--deposit  { color: #16a34a; font-weight: 600; }
.text--withdraw { color: #d97706; font-weight: 600; }

/* ── Indicador de pasos ── */
.op-steps {
  margin-bottom: 1.25rem;
}

.op-steps__track {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
}

.op-steps__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.op-steps__circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid var(--ds-border);
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  font-weight: 700;
  transition: all 0.2s;
}

.op-steps__item--active .op-steps__circle {
  border-color: #667eea;
  background: #667eea;
  color: #fff;
}

.op-steps__item--done .op-steps__circle {
  border-color: #22c55e;
  background: #22c55e;
  color: #fff;
}

.op-steps__label {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--ds-text-muted);
}

.op-steps__item--active .op-steps__label,
.op-steps__item--done .op-steps__label {
  color: var(--ds-text-2);
}

.op-steps__line {
  flex: 1;
  height: 2px;
  background: var(--ds-border);
  margin: 0 8px;
  margin-bottom: 18px;
  transition: background 0.2s;
}

.op-steps__line--done { background: #22c55e; }

/* ── Resumen de monto (paso 2) ── */
.op-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  padding: 0.875rem 1rem;
  margin-bottom: 1.25rem;
}

.op-summary__icon {
  font-size: 1.25rem;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.op-summary__icon--deposit  { background: rgba(34, 197, 94, .14); color: #16a34a; }
.op-summary__icon--withdraw { background: rgba(245, 158, 11, .14); color: #d97706; }

.op-summary__label  { font-size: 0.72rem; color: var(--ds-text-muted); margin: 0; }
.op-summary__amount { font-size: 1.2rem; font-weight: 800; color: var(--ds-text); margin: 0; }

/* ── File drop ── */
.op-file-drop {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: 2px dashed #c7d2fe;
  border-radius: 12px;
  padding: 1.5rem 1rem;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  background: var(--ds-surface-2);
  text-align: center;
}

.op-file-drop:hover       { border-color: #667eea; background: rgba(102, 126, 234, .1); }
.op-file-drop--selected   { border-color: #22c55e; border-style: solid; background: rgba(34, 197, 94, .12); }

.op-file-input { display: none; }

.op-file-drop__icon {
  font-size: 1.75rem;
  color: #a5b4fc;
}

.op-file-drop__icon--ok { color: #22c55e; }

.op-file-drop__name {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--ds-text-2);
  margin: 0;
  word-break: break-all;
}

.op-file-drop__hint {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  margin: 0;
}

/* ── Receipt preview (paso 2) ── */
.op-receipt-preview {
  margin-top: 10px;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid rgba(34, 197, 94, .35);
  background: rgba(34, 197, 94, .1);
  text-align: center;
}
.op-receipt-preview__img {
  display: block;
  max-width: 100%;
  max-height: 220px;
  object-fit: contain;
  margin: 0 auto;
}

/* ── Textarea ── */
.op-field__textarea {
  width: 100%;
  border: 2px solid var(--ds-border);
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 0.875rem;
  color: var(--ds-text);
  outline: none;
  transition: border-color 0.15s;
  resize: vertical;
  box-sizing: border-box;
  font-family: inherit;
}

.op-field__textarea:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }

/* ── Labels auxiliares ── */
.op-required { color: #e11d48; font-weight: 700; }
.op-optional { color: var(--ds-text-muted); font-weight: 400; font-size: 0.75em; }

/* ── Directorios ── */
.op-dirs-loading {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--ds-text-muted);
  font-size: 0.85rem;
  padding: 1rem 0;
}

.op-dirs-empty {
  background: rgba(245, 158, 11, .12);
  border: 1px solid rgba(245, 158, 11, .4);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  color: #d97706;
  font-size: 0.82rem;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.op-dirs-empty__icon { margin-top: 2px; flex-shrink: 0; font-size: 1rem; }

.op-dirs-empty__text { margin: 0 0 6px; line-height: 1.4; }

.op-dirs-empty__link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #667eea;
  text-decoration: none;
  background: rgba(102, 126, 234, .14);
  padding: 4px 10px;
  border-radius: 8px;
  transition: background 0.15s;
}
.op-dirs-empty__link:hover { background: rgba(102, 126, 234, .22); }

.op-dirs-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 220px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}

.op-dir-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 2px solid var(--ds-border);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.15s;
  background: var(--ds-surface);
}

.op-dir-card:hover            { border-color: #a5b4fc; background: var(--ds-surface-2); }
.op-dir-card--selected        { border-color: #667eea; background: rgba(102, 126, 234, .1); }

.op-dir-card__radio { display: none; }

.op-dir-card__icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(102, 126, 234, .14);
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.op-dir-card--selected .op-dir-card__icon { background: #667eea; color: #fff; }

.op-dir-card__info  { flex: 1; min-width: 0; }
.op-dir-card__label { font-size: 0.85rem; font-weight: 700; color: var(--ds-text-2); margin: 0; }
.op-dir-card__detail { font-size: 0.75rem; color: var(--ds-text-muted); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.op-dir-card__holder { font-size: 0.72rem; color: var(--ds-text-muted); margin: 0; }

.op-dir-card__check { color: #667eea; font-size: 1.1rem; flex-shrink: 0; }

/* ── Confirmación (paso 3) ── */
.op-done {
  text-align: center;
  padding: 1rem 0 0.5rem;
}

.op-done__icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  margin: 0 auto 1rem;
  box-shadow: 0 4px 16px rgba(34,197,94,0.3);
}

.op-done__title {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--ds-text);
  margin: 0 0 0.5rem;
}

.op-done__text {
  color: var(--ds-text-muted);
  font-size: 0.875rem;
  margin: 0 0 1rem;
}

.op-done__detail {
  background: rgba(59, 130, 246, .12);
  border: 1px solid rgba(59, 130, 246, .35);
  border-radius: 12px;
  padding: 0.875rem 1rem;
  font-size: 0.78rem;
  color: #38bdf8;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  text-align: left;
}

.op-done__detail i { margin-top: 2px; flex-shrink: 0; }

/* ── Spinner variants ── */
.op-spinner--indigo {
  border-color: rgba(102,126,234,0.25);
  border-top-color: #667eea;
}

/* ── Contenido del modal (op-* para evitar colisión con Modal.vue) ── */
.op-balance {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.25rem;
}

.op-balance__label  { font-size: 0.78rem; color: var(--ds-text-muted); margin: 0 0 2px; }
.op-balance__amount { font-size: 1.35rem; font-weight: 800; color: var(--ds-text); margin: 0; }

.op-balance__icon {
  width: 36px;
  height: 36px;
  background: rgba(102, 126, 234, .14);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #667eea;
  font-size: 0.9rem;
}

.op-field { margin-bottom: 1.25rem; }

.op-field__label {
  display: block;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin-bottom: 8px;
}

.op-field__input-wrap { position: relative; }

.op-field__symbol {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-weight: 700;
  font-size: 1rem;
  color: var(--ds-text-muted);
  pointer-events: none;
}

.op-field__input {
  width: 100%;
  border: 2px solid var(--ds-border);
  border-radius: 12px;
  padding: 12px 14px 12px 44px;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--ds-text);
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}

.op-input--deposit:focus  { border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.15); }
.op-input--withdraw:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }

/* ── Quick amounts ── */
.op-quick { margin-top: 10px; }

.op-quick__label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--ds-text-muted);
  margin: 0 0 8px;
}

.op-quick__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.op-quick__btn {
  padding: 8px;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface);
  color: var(--ds-text-2);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.op-quick__btn:hover          { border-color: #667eea; color: #667eea; background: rgba(102, 126, 234, .1); }
.op-quick__btn--active        { background: #667eea; color: #fff; border-color: #667eea; }

/* ── Mensajes ── */
.op-msg {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  border-radius: 12px;
  font-size: 0.82rem;
  margin-bottom: 0.75rem;
}

.op-msg--error   { background: rgba(220, 38, 38, .14); border: 1px solid rgba(220, 38, 38, .35); color: #f87171; }
.op-msg--success { background: rgba(34, 197, 94, .14); border: 1px solid rgba(34, 197, 94, .35); color: #4ade80; }

.op-msg__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.op-msg--error   .op-msg__icon { background: rgba(220, 38, 38, .2); }
.op-msg--success .op-msg__icon { background: rgba(34, 197, 94, .2); }

.op-msg__title { font-weight: 700; margin: 0 0 2px; }

/* ── Footer del modal ── */
.op-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 9px 20px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
}
.op-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.op-btn--cancel {
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  border: 1.5px solid var(--ds-border);
}
.op-btn--cancel:hover:not(:disabled) { background: var(--ds-surface-2); }

.op-btn--deposit {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
}
.op-btn--deposit:hover:not(:disabled)  { opacity: 0.9; transform: translateY(-1px); }

.op-btn--withdraw {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff;
}
.op-btn--withdraw:hover:not(:disabled) { opacity: 0.9; transform: translateY(-1px); }

.op-btn__loading { display: flex; align-items: center; gap: 8px; }

.op-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Transición mensajes ── */
.msg-fade-enter-active, .msg-fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.msg-fade-enter-from,   .msg-fade-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
