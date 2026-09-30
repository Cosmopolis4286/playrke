<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { usePos } from '@/js/composables/games/bingo/card/pos/posContext'
import { MAX_CARDS, PAYMENT_METHODS } from '@/js/composables/games/bingo/card/pos/usePosSale'
import { showToast } from '@/js/utils/toastNotify'

/**
 * Paso 3: cantidad de cartones (stepper + atajos), método de pago y
 * comprobante. El comprobante solo se pide para pago móvil / transferencia
 * (efectivo no tiene); acepta clic, arrastrar y soltar, pegar (Ctrl+V desde
 * WhatsApp Web / captura) y cámara en el teléfono.
 */
const { sale } = usePos()

const QUICK_COUNTS = [1, 2, 5, 10, 20]
const MAX_BYTES = 2 * 1024 * 1024

const locked = computed(() => !sale.buyerReady)
const fileInput = ref<HTMLInputElement | null>(null)
const dragging = ref(false)
const preview = ref<string | null>(null)
const zoom = ref(false)

watch(() => sale.receiptImage, file => {
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = file ? URL.createObjectURL(file) : null
})

function acceptFile(file: File | null | undefined): void {
  if (!file) return
  if (!/^image\/(jpeg|jpg|png)$/.test(file.type)) {
    showToast('El comprobante debe ser una imagen JPG o PNG.', 'error')
    return
  }
  if (file.size > MAX_BYTES) {
    showToast('La imagen no puede superar los 2 MB.', 'error')
    return
  }
  sale.receiptImage = file
}

function onDrop(e: DragEvent): void {
  dragging.value = false
  acceptFile(e.dataTransfer?.files?.[0])
}

/** Pegar una captura en cualquier parte de la página mientras el paso esté activo. */
function onPaste(e: ClipboardEvent): void {
  if (locked.value || !sale.requiresReceipt) return
  const item = Array.from(e.clipboardData?.items ?? []).find(i => i.type.startsWith('image/'))
  if (!item) return
  e.preventDefault()
  const blob = item.getAsFile()
  if (!blob) return
  const ext = blob.type === 'image/png' ? 'png' : 'jpg'
  acceptFile(new File([blob], `comprobante-${Date.now()}.${ext}`, { type: blob.type }))
  showToast('Comprobante pegado', 'success')
}

function clearFile(): void {
  sale.receiptImage = null
  if (fileInput.value) fileInput.value.value = ''
}

onMounted(() => window.addEventListener('paste', onPaste))
onBeforeUnmount(() => {
  window.removeEventListener('paste', onPaste)
  if (preview.value) URL.revokeObjectURL(preview.value)
})

const stepState = computed(() => (sale.paymentReady && !locked.value) ? 'done' : locked.value ? 'idle' : 'active')

function money(n: number): string {
  return n.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<template>
  <section class="pos-card" :class="{ 'pos-card--locked': locked }" :aria-disabled="locked">
    <div class="pos-step">
      <span class="pos-step__num" :class="{ 'pos-step__num--done': stepState === 'done', 'pos-step__num--active': stepState === 'active' }">
        <i v-if="stepState === 'done'" class="fas fa-check"></i><template v-else>3</template>
      </span>
      <div>
        <h2 class="pos-step__title">Cartones y pago</h2>
        <p class="pos-step__hint">Genera solo después de recibir el pago.</p>
      </div>
    </div>

    <template v-if="!locked">
      <!-- ── Cantidad ── -->
      <span class="pos-label">Cantidad de cartones</span>
      <div class="pps-qty">
        <div class="pps-stepper">
          <button type="button" class="pps-stepper__btn" aria-label="Quitar un cartón" :disabled="sale.cardCount <= 1" @click="sale.setCardCount(sale.cardCount - 1)">
            <i class="fas fa-minus"></i>
          </button>
          <input
            type="number"
            inputmode="numeric"
            min="1"
            :max="MAX_CARDS"
            :value="sale.cardCount"
            aria-label="Cantidad de cartones"
            @change="sale.setCardCount(Number(($event.target as HTMLInputElement).value))"
          />
          <button type="button" class="pps-stepper__btn" aria-label="Agregar un cartón" :disabled="sale.cardCount >= MAX_CARDS" @click="sale.setCardCount(sale.cardCount + 1)">
            <i class="fas fa-plus"></i>
          </button>
        </div>
        <div class="pos-chips">
          <button
            v-for="n in QUICK_COUNTS"
            :key="n"
            type="button"
            class="pos-chip"
            :class="{ 'pos-chip--on': sale.cardCount === n }"
            @click="sale.setCardCount(n)"
          >
            {{ n }}
          </button>
        </div>
        <span v-if="sale.cardPriceData" class="pps-subtotal pos-money">
          {{ sale.cardCount }} × {{ sale.currencySymbol }} {{ money(sale.cardPrice) }}
          = <strong>{{ sale.currencySymbol }} {{ money(sale.totalAmount) }}</strong>
        </span>
      </div>

      <!-- ── Método de pago ── -->
      <span class="pos-label">¿Cómo pagó?</span>
      <div class="pos-chips pps-methods" role="radiogroup" aria-label="Método de pago">
        <button
          v-for="method in PAYMENT_METHODS"
          :key="method.value"
          type="button"
          role="radio"
          class="pos-chip"
          :class="{ 'pos-chip--on': sale.paymentMethod === method.value }"
          :aria-checked="sale.paymentMethod === method.value"
          @click="sale.paymentMethod = method.value"
        >
          <i :class="method.icon" class="me-1"></i>{{ method.label }}
        </button>
      </div>

      <p v-if="!sale.requiresReceipt" class="pps-cash-note">
        <i class="fas fa-info-circle me-1"></i>
        Pago en efectivo: no hace falta comprobante. La venta queda <strong>en revisión</strong> hasta que tu comercio
        confirme que recibió el dinero.
      </p>

      <!-- ── Comprobante (solo pago móvil / transferencia) ── -->
      <div v-else class="pps-proof">
        <div>
          <span class="pos-label">Foto del comprobante</span>
          <div
            v-if="!preview"
            class="pps-drop"
            :class="{ 'pps-drop--over': dragging }"
            role="button"
            tabindex="0"
            @click="fileInput?.click()"
            @keydown.enter.prevent="fileInput?.click()"
            @keydown.space.prevent="fileInput?.click()"
            @dragover.prevent="dragging = true"
            @dragleave="dragging = false"
            @drop.prevent="onDrop"
          >
            <i class="fas fa-cloud-upload-alt"></i>
            <strong>Toca para subir o toma una foto</strong>
            <span class="pos-muted">Arrastra la imagen o pégala con <kbd>Ctrl</kbd>+<kbd>V</kbd> · JPG/PNG, máx. 2 MB</span>
          </div>

          <div v-else class="pps-preview">
            <button type="button" class="pps-preview__img" title="Ver en grande" @click="zoom = true">
              <img :src="preview" alt="Comprobante de pago" />
            </button>
            <div class="pps-preview__meta">
              <span class="pos-pill pos-pill--success"><i class="fas fa-check"></i> Comprobante listo</span>
              <span class="pos-muted text-truncate">{{ sale.receiptImage?.name }}</span>
              <div class="d-flex gap-2">
                <button type="button" class="pos-btn pos-btn--ghost pos-btn--sm" @click="fileInput?.click()"><i class="fas fa-redo"></i>Cambiar</button>
                <button type="button" class="pos-btn pos-btn--ghost pos-btn--sm" @click="clearFile"><i class="fas fa-trash"></i>Quitar</button>
              </div>
            </div>
          </div>

          <input
            ref="fileInput"
            type="file"
            accept="image/png,image/jpeg"
            class="d-none"
            @change="acceptFile(($event.target as HTMLInputElement).files?.[0])"
          />
        </div>

        <div>
          <label class="pos-label" for="pos-receipt-number">Número de comprobante / referencia</label>
          <input
            id="pos-receipt-number"
            v-model="sale.receiptNumber"
            type="text"
            class="form-control"
            maxlength="100"
            autocomplete="off"
            placeholder="Ej. 00123456"
          />
          <p class="pos-muted mt-1 mb-0">Cada comprobante sirve para una sola venta.</p>
        </div>
      </div>
    </template>

    <p v-else class="pos-muted mb-0"><i class="fas fa-lock me-1"></i>Completa los datos del comprador.</p>

    <Teleport to="body">
      <div v-if="zoom && preview" class="pps-zoom" role="dialog" aria-label="Comprobante" @click="zoom = false">
        <img :src="preview" alt="Comprobante de pago" />
      </div>
    </Teleport>
  </section>
</template>

<style scoped>
.pps-qty {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 16px;
  margin-bottom: 18px;
}
.pps-stepper {
  display: inline-flex;
  align-items: stretch;
  border: 1px solid var(--ds-border-2);
  border-radius: 12px;
  overflow: hidden;
  background: var(--ds-surface-2);
}
.pps-stepper__btn {
  width: 44px;
  border: none;
  background: transparent;
  color: var(--ds-text);
  font-size: 14px;
  cursor: pointer;
}
.pps-stepper__btn:hover:not(:disabled) { background: var(--pos-accent-soft); color: var(--pos-accent-strong); }
.pps-stepper__btn:disabled { opacity: .35; cursor: not-allowed; }
.pps-stepper input {
  width: 64px;
  border: none;
  border-left: 1px solid var(--ds-border);
  border-right: 1px solid var(--ds-border);
  background: var(--ds-surface);
  color: var(--ds-text);
  text-align: center;
  font-size: 20px;
  font-weight: 800;
  padding: 8px 0;
  -moz-appearance: textfield;
}
.pps-stepper input::-webkit-outer-spin-button,
.pps-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.pps-subtotal { font-size: 13px; color: var(--ds-text-2); }

.pps-methods { margin-bottom: 16px; }
.pps-cash-note {
  margin: 0;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 13px;
  color: var(--ds-text-2);
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
}

.pps-proof {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
  gap: 16px;
  align-items: start;
}

.pps-drop {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  min-height: 130px;
  padding: 16px;
  text-align: center;
  border: 2px dashed var(--ds-border-2);
  border-radius: 12px;
  background: var(--ds-surface-2);
  cursor: pointer;
  transition: border-color .15s, background .15s;
}
.pps-drop > i { font-size: 26px; color: var(--pos-accent); }
.pps-drop:hover, .pps-drop:focus-visible, .pps-drop--over {
  border-color: var(--pos-accent);
  background: var(--pos-accent-soft);
  outline: none;
}
.pps-drop kbd { font-size: 10px; padding: 1px 4px; }

.pps-preview {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 10px;
  border-radius: 12px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface-2);
}
.pps-preview__img {
  width: 96px;
  height: 96px;
  flex-shrink: 0;
  padding: 0;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  overflow: hidden;
  background: var(--ds-surface);
  cursor: zoom-in;
}
.pps-preview__img img { width: 100%; height: 100%; object-fit: cover; }
.pps-preview__meta { display: flex; flex-direction: column; gap: 6px; min-width: 0; align-items: flex-start; }

.pps-zoom {
  position: fixed;
  inset: 0;
  z-index: 1060;
  display: grid;
  place-items: center;
  padding: 24px;
  background: rgba(15, 23, 42, .8);
  cursor: zoom-out;
}
.pps-zoom img { max-width: 100%; max-height: 100%; border-radius: 12px; }

@media (max-width: 768px) {
  .pps-proof { grid-template-columns: 1fr; }
}
</style>
