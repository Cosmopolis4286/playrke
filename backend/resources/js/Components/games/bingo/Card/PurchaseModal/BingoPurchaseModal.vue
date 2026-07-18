<script setup lang="ts">
  /**
   * BingoPurchaseModal
   *
   * Modal de compra de cartones de bingo desde game-rooms (2 pasos):
   *  - Step 1 "country" → seleccionar país y ver precio
   *  - Step 2 "confirm" → ajustar cantidad y confirmar compra
   *
   * El jugador ya está autenticado — no se requieren sus datos.
   */
  import { computed, ref } from 'vue'
  import { router } from '@inertiajs/vue3'
  import PurchaseModalHeader from './PurchaseModalHeader.vue'
  import PurchaseModalRoomBanner from './PurchaseModalRoomBanner.vue'
  import PurchaseModalCountryStep from './PurchaseModalCountryStep.vue'
  import PurchaseModalConfirmStep from './PurchaseModalConfirmStep.vue'
  import PurchaseModalFooter from './PurchaseModalFooter.vue'
  import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'
  import type {
    BingoAvailableCountry,
    PurchaseModalStep
  } from '@/js/types/games/bingo/card/purchase-modal/bingo-purchase-modal'

  // ─────────────────────────────────────────────────────────────────────────────
  // Props
  // ─────────────────────────────────────────────────────────────────────────────

  const props = defineProps<{
    isOpen: boolean
    step: PurchaseModalStep
    selectedRoom: BingoGameRoom | null
    countries: BingoAvailableCountry[]
    loadingCountries: boolean
    selectedCountry: BingoAvailableCountry | null
    cardPrice: number
    currencySymbol: string
    loadingPrice: boolean
    numCtns: number
    totalAmount: number
    canAdvance: boolean
    purchasing: boolean
    exchangeRate: number
    isPlayer: boolean | null
    canCustomize: boolean
    customizeCard: boolean
    customCardNumbers: Record<string, (number | null)[]>[]
    /** Índice del cartón activo en el picker (0-based). */
    activeCardIndex: number
    /** True mientras el config de la sala aún no se ha cargado (spinner en el toggle). */
    configLoading: boolean
    /** Por cartón: true si el usuario tocó ≥1 casilla manualmente. */
    manualEdits: boolean[]
    /** Modalidad de cartón personalizado de la sala. */
    customCardMode?: 'paid' | 'every_x' | 'free' | null
    /** Recargo % para cartones personalizados (0 = sin recargo, solo aplica en modo 'paid'). */
    customPricePct: number
    /** Máximo de cartones personalizables permitidos en esta compra (modo 'every_x'). */
    maxCustomCardsAllowed?: number
    /** True si el jugador personalizó más cartones de los permitidos. */
    exceedsCustomCardLimit?: boolean
    /** Precio base por cartón (sin recargo). */
    basePricePerCard: number
    /** Precio por cartón personalizado (con recargo). */
    customPricePerCard: number
    /** Cuántos cartones tienen edición manual real. */
    manualCardCount: number
    /** Pregunta al usuario si autocompletar o seguir editando; resuelve true si puede continuar la compra. */
    confirmIncompleteCustomCard: (missingCount: number, autoFill: () => void) => Promise<boolean>
  }>()

  // ─────────────────────────────────────────────────────────────────────────────
  // Emits
  // ─────────────────────────────────────────────────────────────────────────────

  const emit = defineEmits<{
    (e: 'close'): void
    (e: 'select-country', country: BingoAvailableCountry): void
    (e: 'next'): void
    (e: 'prev'): void
    (e: 'increment'): void
    (e: 'decrement'): void
    (e: 'set-ctns', value: number): void
    (e: 'toggle-customize'): void
    (e: 'update:custom-card-numbers', value: Record<string, (number | null)[]>[]): void
    (e: 'update:manual-edits', value: boolean[]): void
    (e: 'set-active-card-index', index: number): void
    (e: 'purchase'): void
  }>()

  const confirmStep = ref<InstanceType<typeof PurchaseModalConfirmStep> | null>(null)

  function handlePurchaseClick(): void {
    confirmStep.value?.handlePurchaseClick()
  }

  function goToLobby() {
    if (!props.selectedRoom) return
    emit('close')
    router.visit(`/bingo/${props.selectedRoom.id}/lobby`)
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Computed
  // ─────────────────────────────────────────────────────────────────────────────

  const stepIndex = computed(() => {
    if (props.isPlayer === null) return 0 // Loading state
    if (props.isPlayer) return 0 // Players only have one step
    return props.step === 'country' ? 0 : 1
  })

  const stepperLabels = computed(() => {
    if (props.isPlayer === null) return ['Cargando...'] // Loading state
    if (props.isPlayer) return ['Confirmar'] // Players only see confirm step
    return ['País', 'Confirmar'] // Other roles see both steps
  })

  const prizeFormatted = computed(() => {
    if (!props.selectedRoom) return '0.00'
    return (props.selectedRoom.prize_pool * props.exchangeRate).toFixed(2)
  })

  const currentCtns = computed(() => {
    return props.selectedRoom?.players_room?.[0]?.ctns ?? 0
  })
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="isOpen" class="pm-backdrop" @click.self="emit('close')">
        <div class="pm-dialog" role="dialog" aria-modal="true">
          <!-- ── Glow decorativo ── -->
          <div class="pm-dialog__glow" />

          <PurchaseModalHeader
            :step="step"
            :step-index="stepIndex"
            :stepper-labels="stepperLabels"
            :purchasing="purchasing"
            @close="emit('close')"
          />

          <template v-if="step !== 'loading'">
            <PurchaseModalRoomBanner
              :selected-room="selectedRoom"
              :prize-formatted="prizeFormatted"
              :is-player="isPlayer"
              :currency-symbol="currencySymbol"
              :selected-country="selectedCountry"
            />

            <!-- Overlay visual absoluto sobre todo el dialog mientras purchasing.
                 position:absolute + inset:0 ancla al .pm-dialog (position:relative).
                 overflow:hidden del dialog recorta el border-radius automáticamente. -->
            <Transition name="fade">
              <div v-if="purchasing" class="pm-processing-overlay" aria-hidden="true">
                <span class="pm-spinner pm-spinner--lg" />
                <span class="pm-processing-overlay__label">Procesando…</span>
              </div>
            </Transition>

            <!-- ═══════ Bloqueo nativo de inputs/botones durante procesamiento ════════ -->
            <fieldset :disabled="purchasing" style="display:contents;border:none;padding:0;margin:0">

            <!-- ═══════════════════ BODY ═══════════════════ -->
            <div class="pm-body">
              <PurchaseModalCountryStep
                :is-player="isPlayer"
                :step="step"
                :loading-countries="loadingCountries"
                :countries="countries"
                :selected-country="selectedCountry"
                :card-price="cardPrice"
                :loading-price="loadingPrice"
                :currency-symbol="currencySymbol"
                @select-country="emit('select-country', $event)"
              />

              <PurchaseModalConfirmStep
                ref="confirmStep"
                :step="step"
                :current-ctns="currentCtns"
                :num-ctns="numCtns"
                :can-customize="canCustomize"
                :customize-card="customizeCard"
                :config-loading="configLoading"
                :custom-card-numbers="customCardNumbers"
                :manual-edits="manualEdits"
                :active-card-index="activeCardIndex"
                :variant="selectedRoom?.config?.game_variant"
                :custom-card-mode="customCardMode"
                :custom-price-pct="customPricePct"
                :max-custom-allowed="maxCustomCardsAllowed"
                :exceeds-custom-card-limit="exceedsCustomCardLimit"
                :manual-card-count="manualCardCount"
                :confirm-incomplete-custom-card="confirmIncompleteCustomCard"
                @increment="emit('increment')"
                @decrement="emit('decrement')"
                @set-ctns="emit('set-ctns', $event)"
                @toggle-customize="emit('toggle-customize')"
                @update:custom-card-numbers="emit('update:custom-card-numbers', $event)"
                @update:manual-edits="emit('update:manual-edits', $event)"
                @set-active-card-index="emit('set-active-card-index', $event)"
                @purchase="emit('purchase')"
              />
            </div>

            <PurchaseModalFooter
              :is-player="isPlayer"
              :step="step"
              :current-ctns="currentCtns"
              :purchasing="purchasing"
              :can-advance="canAdvance"
              :loading-price="loadingPrice"
              :customize-card="customizeCard"
              :manual-card-count="manualCardCount"
              :base-price-per-card="basePricePerCard"
              :custom-price-per-card="customPricePerCard"
              :custom-card-mode="customCardMode"
              :custom-price-pct="customPricePct"
              :exceeds-custom-card-limit="exceedsCustomCardLimit"
              :num-ctns="numCtns"
              :total-amount="totalAmount"
              :currency-symbol="currencySymbol"
              @prev="emit('prev')"
              @next="emit('next')"
              @purchase="handlePurchaseClick"
              @lobby="goToLobby"
            />

            </fieldset><!-- /purchasing lock -->
          </template>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
  /* ═══════════════════════════════════════════════════════
   BACKDROP
═══════════════════════════════════════════════════════ */
  .pm-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(2, 5, 20, 0.75);
    backdrop-filter: blur(6px);
    padding: 16px;
  }

  /* ═══════════════════════════════════════════════════════
   DIALOG
═══════════════════════════════════════════════════════ */
  .pm-dialog {
    position: relative;
    width: 100%;
    max-width: 480px;
    border-radius: 22px;
    overflow: hidden;
    background: linear-gradient(160deg, #0d1633 0%, #080f26 100%);
    border: 1px solid rgba(100, 140, 255, 0.2);
    box-shadow:
      0 24px 80px rgba(0, 0, 0, 0.75),
      0 0 0 1px rgba(80, 120, 255, 0.08) inset;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
  }

  .pm-dialog__glow {
    position: absolute;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    width: 260px;
    height: 120px;
    background: radial-gradient(ellipse, rgba(42, 245, 152, 0.18) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
  }

  /* ═══════════════════════════════════════════════════════
   BODY
═══════════════════════════════════════════════════════ */
  .pm-body {
    flex: 1;
    overflow-y: auto;
    padding: 8px 14px;
    scrollbar-width: thin;
    scrollbar-color: #7b4fff transparent;
  }
  .pm-body::-webkit-scrollbar {
    width: 4px;
  }
  .pm-body::-webkit-scrollbar-track {
    background: transparent;
  }
  .pm-body::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #7b4fff, #009efd);
    border-radius: 10px;
  }

  /* ═══════════════════════════════════════════════════════
   SPINNER
═══════════════════════════════════════════════════════ */
  .pm-spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-top-color: #2af598;
    border-radius: 50%;
    animation: pm-spin 0.7s linear infinite;
  }
  .pm-spinner--lg {
    width: 36px;
    height: 36px;
    border-width: 3px;
  }

  /* ═══════════════════════════════════════════════════════
   PROCESSING OVERLAY
═══════════════════════════════════════════════════════ */
  .pm-processing-overlay {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: rgba(8, 15, 38, 0.72);
    backdrop-filter: blur(3px);
    /* overflow:hidden del .pm-dialog ya recorta el border-radius */
    pointer-events: all;
  }
  .pm-processing-overlay__label {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    letter-spacing: 0.03em;
  }

  /* fieldset[disabled] quita pointer-events pero no la apariencia; lo completamos aquí */
  fieldset[disabled] :deep(.pm-qty-btn),
  fieldset[disabled] :deep(.pm-qty-input),
  fieldset[disabled] :deep(.pm-country-btn),
  fieldset[disabled] :deep(.pm-customize-toggle),
  fieldset[disabled] :deep(.pm-btn) {
    opacity: 0.45;
    cursor: not-allowed;
  }

  @keyframes pm-spin {
    to {
      transform: rotate(360deg);
    }
  }

  /* ═══════════════════════════════════════════════════════
   TRANSICIONES
═══════════════════════════════════════════════════════ */
  .modal-fade-enter-active,
  .modal-fade-leave-active {
    transition:
      opacity 0.25s ease,
      transform 0.25s ease;
  }
  .modal-fade-enter-from,
  .modal-fade-leave-to {
    opacity: 0;
    transform: scale(0.96);
  }

  .fade-enter-active,
  .fade-leave-active {
    transition: opacity 0.2s ease;
  }
  .fade-enter-from,
  .fade-leave-to {
    opacity: 0;
  }
</style>
