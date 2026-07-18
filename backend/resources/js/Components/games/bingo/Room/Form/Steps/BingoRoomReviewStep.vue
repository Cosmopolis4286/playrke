<script setup lang="ts" name="BingoRoomReviewStep">
import { computed } from 'vue'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
import type { SelectOption } from '@/js/types/common/form/form'
import { STEP_LABELS, STEP_ERROR_KEYS, type BingoRoomStep } from '@/js/composables/games/bingo/room/shared/useBingoRoomStepper'
import { getCurrencyLabel } from '../Composables/useRoomCurrency'

const props = defineProps<{
  form:   BingoRoomFormData
  errors: Record<string, unknown>
  currencyOptions?: SelectOption[]
  /** true si la sala tiene jugadores: el estado no se puede cambiar desde el formulario. */
  lockActivateRoom?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:form', value: BingoRoomFormData): void
  (e: 'goto-step', step: Exclude<BingoRoomStep, 'review'>): void
}>()

type StepKey = Exclude<BingoRoomStep, 'review'>

const STEPS: StepKey[] = ['basic', 'currencies', 'rounds', 'options']

function stepHasErrors(step: StepKey): boolean {
  return STEP_ERROR_KEYS[step].some(k => k in props.errors)
}

const stepsWithErrors = computed(() => STEPS.filter(stepHasErrors))
const isReady = computed(() => stepsWithErrors.value.length === 0)

/**
 * Una sala programada (no manual) cuya fecha/hora ya pasó no debería poder
 * activarse — en edición no se fuerza a corregir la fecha vencida (el usuario
 * puede editar otros campos igual), así que este chequeo evita marcarla para
 * activarse con un horario que ya no tiene sentido.
 */
const isScheduledDateInPast = computed(() => {
  if (props.form.startMode !== 'SCHEDULED') return false
  const { date, time } = props.form
  if (!date || !time) return false
  return new Date(`${date}T${time}:00`) < new Date()
})

function onActivateRoomChange(checked: boolean): void {
  emit('update:form', { ...props.form, activateRoom: checked })
}

function goToStep(step: StepKey): void {
  emit('goto-step', step)
}

/**
 * Precio del cartón por moneda permitida: si la sala usa base USD, el mismo
 * precio se convierte a todas las monedas con la tasa de cambio (se muestra
 * el precio base); si no, cada moneda tiene su propio precio manual.
 */
const currencyPriceRows = computed(() => {
  const allowed = props.form.allowedCurrencies ?? []
  return allowed.map(cur => {
    const iso = props.currencyOptions?.length
      ? getCurrencyLabel(cur, props.currencyOptions)
      : String(cur)
    const price = props.form.useBaseUsd
      ? props.form.cardPriceUsd
      : props.form.cardPriceByCurrency?.[String(cur)]
    return { iso, price: price != null ? Number(price) : null }
  })
})

/**
 * Con 2+ monedas permitidas, la sala puede repartir un solo bote entre todas
 * (sala única) o crear una sala independiente por cada moneda (roomsPerCurrency).
 */
const roomModeLabel = computed(() => {
  if ((props.form.allowedCurrencies?.length ?? 0) <= 1) return null
  return props.form.roomsPerCurrency
    ? 'Salas separadas por moneda'
    : 'Sala única (bote compartido)'
})

const CUSTOM_CARD_MODE_LABELS: Record<string, string> = {
  paid:     'Con costo adicional',
  every_x:  'Cada X cartones',
  free:     'Uso libre',
}

const customCardModeLabel = computed(() => {
  const mode = props.form.customCardMode
  return mode ? (CUSTOM_CARD_MODE_LABELS[mode] ?? mode) : null
})

const hasOptionalConfig = computed(() =>
  !!props.form.useSerials || !!props.form.allowCustomCard || !!props.form.allowSpecialCard
)
</script>

<template>
  <div class="bingo-review-step">
    <div v-if="!isReady" class="bingo-review-step__alerts">
      <div
        v-for="step in stepsWithErrors"
        :key="step"
        class="bingo-review-step__alert bingo-review-step__alert--error"
      >
        <span class="bingo-review-step__alert-icon">⚠</span>
        <span>Corrige los errores en <strong>{{ STEP_LABELS[step] }}</strong> antes de guardar.</span>
        <button type="button" class="bingo-review-step__alert-fix" @click="goToStep(step)">Ir a corregir</button>
      </div>
    </div>

    <div v-else class="bingo-review-step__ready">
      <span class="bingo-review-step__ready-icon">✓</span>
      <span>Todo listo. Revisa el resumen y guarda la sala.</span>
    </div>

    <div class="bingo-review-step__sections">
      <!-- Datos básicos -->
      <section class="bingo-review-step__section">
        <h4 class="bingo-review-step__section-title">
          <i class="fa-solid fa-sliders bingo-review-step__section-icon"></i>
          Datos básicos
          <span v-if="stepHasErrors('basic')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
          <button type="button" class="bingo-review-step__edit-btn" @click="goToStep('basic')">
            <i class="fa-solid fa-pen"></i> Editar
          </button>
        </h4>
        <dl class="bingo-review-step__dl">
          <dt>Rondas</dt>
          <dd>{{ form.rounds ?? '—' }}</dd>
          <dt>Comisión</dt>
          <dd>{{ form.roomCommn != null ? form.roomCommn + '%' : '—' }}</dd>
          <dt>Jackpot</dt>
          <dd>{{ form.jackpotPct != null ? form.jackpotPct + '%' : '—' }}</dd>
          <dt>Tiempo por ficha</dt>
          <dd>{{ form.timeChip != null ? form.timeChip + 's' : '—' }}</dd>
          <template v-if="form.minCardsToStart != null">
            <dt>Mín. cartones para iniciar</dt>
            <dd>{{ form.minCardsToStart }}</dd>
          </template>
          <template v-if="form.maxPlayers != null">
            <dt>Cupo máx. jugadores</dt>
            <dd>{{ form.maxPlayers }}</dd>
          </template>
        </dl>
      </section>

      <!-- Monedas -->
      <section class="bingo-review-step__section">
        <h4 class="bingo-review-step__section-title">
          <i class="fa-solid fa-coins bingo-review-step__section-icon"></i>
          Monedas y precios
          <span v-if="stepHasErrors('currencies')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
          <button type="button" class="bingo-review-step__edit-btn" @click="goToStep('currencies')">
            <i class="fa-solid fa-pen"></i> Editar
          </button>
        </h4>
        <dl class="bingo-review-step__dl">
          <dt>Monedas permitidas</dt>
          <dd>{{ currencyPriceRows.map(r => r.iso).join(', ') || '—' }}</dd>
          <template v-if="form.useBaseUsd">
            <dt>Precio base (USD)</dt>
            <dd>{{ form.cardPriceUsd != null ? '$' + form.cardPriceUsd : '—' }}</dd>
          </template>
          <template v-else>
            <dt>Precio del cartón</dt>
            <dd>
              <span v-if="currencyPriceRows.length === 0">—</span>
              <span v-else>
                <span v-for="(row, i) in currencyPriceRows" :key="row.iso">
                  {{ row.iso }}: {{ row.price != null ? row.price : '—' }}<template v-if="i < currencyPriceRows.length - 1">, </template>
                </span>
              </span>
            </dd>
          </template>
          <template v-if="roomModeLabel">
            <dt>Modo de sala</dt>
            <dd>{{ roomModeLabel }}</dd>
          </template>
        </dl>
      </section>

      <!-- Rondas -->
      <section class="bingo-review-step__section">
        <h4 class="bingo-review-step__section-title">
          <i class="fa-solid fa-list-ol bingo-review-step__section-icon"></i>
          Rondas
          <span v-if="stepHasErrors('rounds')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
          <button type="button" class="bingo-review-step__edit-btn" @click="goToStep('rounds')">
            <i class="fa-solid fa-pen"></i> Editar
          </button>
        </h4>
        <dl class="bingo-review-step__dl">
          <dt>% rondas</dt>
          <dd>{{ form.roundsPct != null ? form.roundsPct + '%' : '—' }}</dd>
          <dt>% seriales</dt>
          <dd>{{ form.serialsPct != null ? form.serialsPct + '%' : '—' }}</dd>
        </dl>
      </section>

      <!-- Más / Opcional -->
      <section class="bingo-review-step__section">
        <h4 class="bingo-review-step__section-title">
          <i class="fa-solid fa-star bingo-review-step__section-icon"></i>
          Más / Opcional
          <span v-if="stepHasErrors('options')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
          <button type="button" class="bingo-review-step__edit-btn" @click="goToStep('options')">
            <i class="fa-solid fa-pen"></i> Editar
          </button>
        </h4>
        <dl v-if="hasOptionalConfig" class="bingo-review-step__dl">
          <template v-if="form.useSerials">
            <dt>Seriales</dt>
            <dd>
              Sí — {{ form.numSerials ?? '—' }} seriales
              <template v-if="form.serialsPct != null"> ({{ form.serialsPct }}% bote)</template>
            </dd>
          </template>
          <template v-if="form.allowCustomCard">
            <dt>Cartón personalizado</dt>
            <dd>
              {{ customCardModeLabel ?? '—' }}
              <template v-if="form.customCardMode === 'paid' && form.customCardPricePct != null">
                ({{ form.customCardPricePct }}% recargo)
              </template>
              <template v-if="form.customCardMode === 'every_x' && form.customCardEveryX != null">
                (cada {{ form.customCardEveryX }} cartones)
              </template>
            </dd>
          </template>
          <template v-if="form.allowSpecialCard">
            <dt>Cartón especial</dt>
            <dd>Sí — máx. {{ form.maxSpecialCards ?? '—' }}</dd>
          </template>
        </dl>
        <p v-else class="bingo-review-step__empty">Sin opciones adicionales configuradas.</p>
      </section>

      <!-- Activación -->
      <section class="bingo-review-step__section">
        <h4 class="bingo-review-step__section-title">
          <i class="fa-solid fa-power-off bingo-review-step__section-icon"></i>
          Activación
        </h4>
        <div class="custom-control custom-switch">
          <input
            type="checkbox"
            class="custom-control-input"
            id="activateRoom"
            :disabled="isScheduledDateInPast || lockActivateRoom"
            :checked="form.activateRoom ?? false"
            @change="onActivateRoomChange(($event.target as HTMLInputElement).checked)"
          />
          <label class="custom-control-label" for="activateRoom">¿Activar sala?</label>
        </div>
        <small v-if="lockActivateRoom" class="text-muted d-block mt-1">
          <i class="fas fa-lock mr-1"></i>
          La sala tiene jugadores registrados: usa "Alternar Estado" en la tabla para cambiar el estado.
        </small>
        <small v-else-if="isScheduledDateInPast" class="text-danger d-block mt-1">
          <i class="fas fa-exclamation-triangle mr-1"></i>
          La fecha/hora programada ya pasó. Actualízala antes de activar la sala.
        </small>
      </section>
    </div>
  </div>
</template>

<style scoped>
.bingo-review-step { display: flex; flex-direction: column; gap: 1rem; }
.bingo-review-step__alerts { display: flex; flex-direction: column; gap: .5rem; }
.bingo-review-step__alert {
  display: flex; align-items: center; gap: .5rem;
  padding: .6rem 1rem; border-radius: .4rem;
  background: color-mix(in srgb, var(--bs-danger, #dc3545) 12%, transparent);
  color: var(--bs-danger, #dc3545);
}
.bingo-review-step__alert-fix {
  margin-left: auto;
  flex-shrink: 0;
  border: none;
  background: var(--bs-danger, #dc3545);
  color: #fff;
  font-size: .75rem;
  font-weight: 600;
  padding: .25rem .6rem;
  border-radius: .3rem;
  cursor: pointer;
  white-space: nowrap;
}
.bingo-review-step__alert-fix:hover { filter: brightness(1.1); }
.bingo-review-step__ready {
  display: flex; align-items: center; gap: .5rem;
  padding: .6rem 1rem; border-radius: .4rem;
  background: color-mix(in srgb, var(--bs-success, #198754) 12%, transparent);
  color: var(--bs-success, #198754);
}
.bingo-review-step__sections { display: flex; flex-direction: column; gap: 1.25rem; }
.bingo-review-step__section { border: 1px solid var(--bs-border-color, #dee2e6); border-radius: .4rem; padding: 1rem; }
.bingo-review-step__section-title {
  font-size: .875rem; font-weight: 600; margin-bottom: .75rem;
  display: flex; align-items: center; gap: .5rem;
}
.bingo-review-step__section-icon {
  font-size: .8rem;
  color: var(--bs-primary, #4a6fff);
  opacity: .85;
  width: 1rem;
  text-align: center;
}
.bingo-review-step__badge {
  font-size: .7rem; font-weight: 600; padding: .15rem .5rem; border-radius: 999px;
}
.bingo-review-step__badge--error { background: var(--bs-danger, #dc3545); color: #fff; }
.bingo-review-step__edit-btn {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  border: none;
  background: transparent;
  color: var(--bs-primary, #4a6fff);
  font-size: .75rem;
  font-weight: 600;
  padding: .15rem .4rem;
  border-radius: .3rem;
  cursor: pointer;
  transition: background .15s;
}
.bingo-review-step__edit-btn:hover { background: color-mix(in srgb, var(--bs-primary, #4a6fff) 10%, transparent); }
.bingo-review-step__dl { display: grid; grid-template-columns: auto 1fr; gap: .25rem .75rem; margin: 0; }
.bingo-review-step__dl dt { font-weight: 500; color: var(--bs-secondary, #6c757d); font-size: .8125rem; }
.bingo-review-step__dl dd { margin: 0; font-size: .8125rem; }
.bingo-review-step__empty { margin: 0; font-size: .8125rem; color: var(--bs-secondary, #6c757d); font-style: italic; }
</style>
