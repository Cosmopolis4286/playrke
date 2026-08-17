<script setup lang="ts" name="BingoRoomReviewStep">
import { computed, reactive, watch } from 'vue'
import type { BingoRoomFormData, BingoRoomLastUpdatedBy } from '@/js/types/games/bingo/room/bingo-room'
import type { SelectOption } from '@/js/types/common/form/form'
import { STEP_LABELS, STEP_ERROR_KEYS, getStepStatus, type BingoRoomStep } from '@/js/composables/games/bingo/room/shared/useBingoRoomStepper'
import { getCurrencyLabel } from '../Composables/useRoomCurrency'

const props = defineProps<{
  form:   BingoRoomFormData
  errors: Record<string, unknown>
  currencyOptions?: SelectOption[]
  roomTypeOptions?: SelectOption[]
  connectionTypeOptions?: SelectOption[]
  gameModeOptions?: SelectOption[]
  prizePoolTypeOptions?: SelectOption[]
  /** Etiqueta del anfitrión ya asignado (para mostrar el nombre en vez del id crudo). */
  hostUserLabel?: string | null
  /** true si la sala tiene jugadores: el estado no se puede cambiar desde el formulario. */
  lockActivateRoom?: boolean
  /** Metadatos de solo lectura de la sala en edición (null en modo creación). */
  roomMeta?: { created_at?: string | null; updated_at?: string | null; last_updated_by?: BingoRoomLastUpdatedBy | null } | null
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

/** Semáforo de 3 estados (error/incomplete/complete) — misma función pura que usa la barra de tabs (BingoRoomForm.vue), para que ambos indicadores coincidan siempre. */
function sectionStatus(step: StepKey) {
  return getStepStatus(step, props.errors, props.form)
}

const stepsWithErrors = computed(() => STEPS.filter(stepHasErrors))
const isReady = computed(() => stepsWithErrors.value.length === 0)

/** Progreso: cuántas de las 4 secciones editables (todas menos Activación, que no valida) están sin errores. */
const completedCount = computed(() => STEPS.length - stepsWithErrors.value.length)

/**
 * Acordeón: cada sección arranca colapsada si ya está sin errores (nada que
 * revisar de urgencia) y expandida si tiene errores (el usuario necesita
 * verla). Solo se fija el valor INICIAL con este watch — después el usuario
 * puede expandir/colapsar libremente sin que se le pise el click.
 */
const expanded = reactive<Record<StepKey, boolean>>({
  basic: stepHasErrors('basic'),
  currencies: stepHasErrors('currencies'),
  rounds: stepHasErrors('rounds'),
  options: stepHasErrors('options'),
})

function toggleSection(step: StepKey): void {
  expanded[step] = !expanded[step]
}

// Si una sección pasa a tener errores (el usuario los introdujo editando otro
// paso y volvió a Revisión), se re-expande automáticamente para que no quede
// escondida con un error que el usuario no ve porque la dejó colapsada antes.
watch(stepsWithErrors, (withErrors) => {
  withErrors.forEach(s => { expanded[s] = true })
})

/** Mensaje de error de un campo puntual, si existe, para resaltarlo en la propia fila del resumen (no solo el badge genérico del título). */
function fieldError(key: string): string | null {
  const value = props.errors[key]
  return typeof value === 'string' ? value : null
}

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

/** "dd/mm/aaaa hh:mm" en horario local — mismo formato usado para la fecha del borrador local. */
function formatDateTime(iso: string | null | undefined): string | null {
  if (!iso) return null
  const parsed = new Date(iso)
  if (isNaN(parsed.getTime())) return null
  return parsed.toLocaleString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const createdAtLabel = computed(() => formatDateTime(props.roomMeta?.created_at))
const updatedAtLabel = computed(() => formatDateTime(props.roomMeta?.updated_at))
const lastEditor = computed(() => props.roomMeta?.last_updated_by ?? null)
const lastEditorLabel = computed(() => lastEditor.value?.username ?? 'Sin ediciones registradas')

/** Resuelve el label legible de un id contra una lista de opciones (roomType/connectionType/gameMode/prizePoolType viajan como ids). */
function optionLabel(id: string | number | null | undefined, options?: SelectOption[]): string | null {
  if (id == null) return null
  return options?.find(o => String(o.value) === String(id))?.label ?? String(id)
}

const roomTypeLabel = computed(() => optionLabel(props.form.roomType, props.roomTypeOptions))
const connectionTypeLabel = computed(() => optionLabel(props.form.connectionType, props.connectionTypeOptions))
const gameModeLabel = computed(() => optionLabel(props.form.gameMode, props.gameModeOptions))
const prizePoolTypeLabel = computed(() => optionLabel(props.form.prizePoolType, props.prizePoolTypeOptions))

const isPrivateRoom = computed(() => {
  const selected = props.roomTypeOptions?.find(o => String(o.value) === String(props.form.roomType))
  return selected?.code === 'PRIVATE'
})

const GAME_VARIANT_LABELS: Record<string, string> = {
  BINGO_75: 'Bingo 75 bolas',
  BINGO_90: 'Bingo 90 bolas',
  BINGO_80: 'Bingo 80 bolas',
  BINGO_30: 'Speed Bingo 30 bolas',
}
const gameVariantLabel = computed(() => {
  const v = props.form.gameVariant
  return v ? (GAME_VARIANT_LABELS[v] ?? v) : null
})

/** "Programado: dd/mm hh:mm" o "Manual (sin horario fijo)". */
const scheduleLabel = computed(() => {
  if (props.form.startMode === 'MANUAL') return 'Manual (sin horario fijo)'
  const { date, time } = props.form
  if (!date || !time) return '—'
  const parsed = new Date(`${date}T${time}:00`)
  if (isNaN(parsed.getTime())) return '—'
  return parsed.toLocaleString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
})
</script>

<template>
  <div class="bingo-review-step">
    <!-- Progreso: semáforo por sección (mismo criterio que la barra de tabs) —
         rojo = error, amarillo = válida pero con opcionales sin completar, verde = completa -->
    <div class="bingo-review-step__progress">
      <div class="bingo-review-step__progress-dots">
        <span
          v-for="s in STEPS"
          :key="s"
          class="bingo-review-step__progress-dot"
          :class="`bingo-review-step__progress-dot--${sectionStatus(s)}`"
          :title="STEP_LABELS[s]"
        />
      </div>
      <span class="bingo-review-step__progress-label">{{ completedCount }} de {{ STEPS.length }} secciones completas</span>
    </div>

    <!-- Alerta consolidada: una sola caja con todas las secciones pendientes (antes se repetía una caja idéntica por cada sección con error) -->
    <div v-if="!isReady" class="bingo-review-step__alert bingo-review-step__alert--error">
      <span class="bingo-review-step__alert-icon">⚠</span>
      <div class="bingo-review-step__alert-body">
        <span>Faltan datos o hay errores en:</span>
        <div class="bingo-review-step__alert-chips">
          <button
            v-for="step in stepsWithErrors"
            :key="step"
            type="button"
            class="bingo-review-step__alert-chip"
            @click="goToStep(step)"
          >
            {{ STEP_LABELS[step] }} <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      </div>
    </div>

    <div v-else class="bingo-review-step__ready">
      <span class="bingo-review-step__ready-icon">✓</span>
      <span>Todo listo. Revisa el resumen y guarda la sala.</span>
    </div>

    <div class="bingo-review-step__sections">
      <!-- Metadatos (solo edición) -->
      <section v-if="roomMeta" class="bingo-review-step__section bingo-review-step__section--meta">
        <h4 class="bingo-review-step__section-title">
          <i class="fa-solid fa-clock-rotate-left bingo-review-step__section-icon"></i>
          Metadatos
        </h4>
        <dl class="bingo-review-step__dl">
          <template v-if="createdAtLabel">
            <dt>Creada el</dt>
            <dd>{{ createdAtLabel }}</dd>
          </template>
          <dt>Última actualización</dt>
          <dd>{{ updatedAtLabel ?? '—' }}</dd>
          <dt>Editado por</dt>
          <dd>{{ lastEditorLabel }}</dd>
        </dl>
      </section>

      <!-- Datos básicos -->
      <section class="bingo-review-step__section" :class="{ 'bingo-review-step__section--collapsed': !expanded.basic }">
        <h4 class="bingo-review-step__section-title" @click="toggleSection('basic')">
          <i class="fa-solid fa-chevron-right bingo-review-step__chevron" :class="{ 'bingo-review-step__chevron--open': expanded.basic }"></i>
          <i class="fa-solid fa-sliders bingo-review-step__section-icon"></i>
          Datos básicos
          <span v-if="stepHasErrors('basic')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
          <span v-else class="bingo-review-step__badge bingo-review-step__badge--ok"><i class="fa-solid fa-check"></i></span>
          <button type="button" class="bingo-review-step__edit-btn" @click.stop="goToStep('basic')">
            <i class="fa-solid fa-pen"></i> Editar
          </button>
        </h4>
        <div v-show="expanded.basic">
          <dl class="bingo-review-step__dl">
            <dt>Tipo de sala</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('roomType') }">{{ roomTypeLabel ?? (fieldError('roomType') ?? '—') }}</dd>
            <dt>Tipo de conexión</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('connectionType') }">{{ connectionTypeLabel ?? (fieldError('connectionType') ?? '—') }}</dd>
            <dt>Modo de juego</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('gameMode') }">{{ gameModeLabel ?? (fieldError('gameMode') ?? '—') }}</dd>
            <dt>Variante</dt>
            <dd>{{ gameVariantLabel ?? '—' }}</dd>
            <dt>Tipo de bote</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('prizePoolType') }">{{ prizePoolTypeLabel ?? (fieldError('prizePoolType') ?? '—') }}</dd>
            <template v-if="form.amountAddedPrizePool != null">
              <dt>Valor fijo/extra al bote</dt>
              <dd>${{ form.amountAddedPrizePool }}</dd>
            </template>
            <dt>Horario</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('date') || fieldError('time') }">{{ scheduleLabel }}</dd>
            <dt>Rondas</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('rounds') }">{{ form.rounds ?? (fieldError('rounds') ?? '—') }}</dd>
            <dt>Comisión de sala</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('roomCommn') }">{{ form.roomCommn != null ? form.roomCommn + '%' : (fieldError('roomCommn') ?? '—') }}</dd>
            <dt>Jackpot</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('jackpotPct') }">{{ form.jackpotPct != null ? form.jackpotPct + '%' : '—' }}</dd>
            <dt>Tiempo por ficha</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('timeChip') }">{{ form.timeChip != null ? form.timeChip + 's' : (fieldError('timeChip') ?? '—') }}</dd>
          </dl>

          <template v-if="isPrivateRoom">
            <hr style="margin: 0.6rem 0" />
            <p class="bingo-review-step__subtitle">
              <i class="fa-solid fa-lock" style="opacity: 0.7"></i> Sala privada
            </p>
            <dl class="bingo-review-step__dl">
              <dt>Código de acceso</dt>
              <dd :class="{ 'bingo-review-step__dd--error': fieldError('accessCode') }">{{ form.accessCode ?? (fieldError('accessCode') ?? '—') }}</dd>
              <dt>Contraseña</dt>
              <dd :class="{ 'bingo-review-step__dd--error': fieldError('roomPassword') }">{{ form.roomPassword ? 'Se actualizará al guardar' : (fieldError('roomPassword') ?? 'Sin cambios') }}</dd>
              <dt>Anfitrión</dt>
              <dd :class="{ 'bingo-review-step__dd--error': fieldError('hostUserId') }">{{ (form.hostUserId ? (hostUserLabel ?? String(form.hostUserId)) : null) ?? (fieldError('hostUserId') ?? '—') }}</dd>
            </dl>
          </template>

          <hr style="margin: 0.6rem 0" />
          <p class="bingo-review-step__subtitle">
            <i class="fa-solid fa-gauge-high" style="opacity: 0.7"></i> Capacidad
          </p>
          <dl
            v-if="form.minCardsToStart != null || form.maxCardsToStart != null || form.minPlayers != null || form.maxPlayers != null"
            class="bingo-review-step__dl"
          >
            <template v-if="form.minCardsToStart != null">
              <dt>Mín. cartones para iniciar</dt>
              <dd>{{ form.minCardsToStart }}</dd>
            </template>
            <template v-if="form.maxCardsToStart != null">
              <dt>Máx. cartones</dt>
              <dd>{{ form.maxCardsToStart }}</dd>
            </template>
            <template v-if="form.minPlayers != null">
              <dt>Mín. jugadores para iniciar</dt>
              <dd>{{ form.minPlayers }}</dd>
            </template>
            <template v-if="form.maxPlayers != null">
              <dt>Cupo máx. jugadores</dt>
              <dd>{{ form.maxPlayers }}</dd>
            </template>
          </dl>
          <p v-else class="bingo-review-step__empty">Sin límites configurados — cartones y jugadores ilimitados.</p>
        </div>
      </section>

      <!-- Monedas -->
      <section class="bingo-review-step__section" :class="{ 'bingo-review-step__section--collapsed': !expanded.currencies }">
        <h4 class="bingo-review-step__section-title" @click="toggleSection('currencies')">
          <i class="fa-solid fa-chevron-right bingo-review-step__chevron" :class="{ 'bingo-review-step__chevron--open': expanded.currencies }"></i>
          <i class="fa-solid fa-coins bingo-review-step__section-icon"></i>
          Monedas y precios
          <span v-if="stepHasErrors('currencies')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
          <span v-else class="bingo-review-step__badge bingo-review-step__badge--ok"><i class="fa-solid fa-check"></i></span>
          <button type="button" class="bingo-review-step__edit-btn" @click.stop="goToStep('currencies')">
            <i class="fa-solid fa-pen"></i> Editar
          </button>
        </h4>
        <dl v-show="expanded.currencies" class="bingo-review-step__dl">
          <dt>Monedas permitidas</dt>
          <dd :class="{ 'bingo-review-step__dd--error': fieldError('allowedCurrencies') }">{{ currencyPriceRows.map(r => r.iso).join(', ') || (fieldError('allowedCurrencies') ?? '—') }}</dd>
          <template v-if="form.useBaseUsd">
            <dt>Precio base (USD)</dt>
            <dd :class="{ 'bingo-review-step__dd--error': fieldError('cardPriceUsd') }">{{ form.cardPriceUsd != null ? '$' + form.cardPriceUsd : (fieldError('cardPriceUsd') ?? '—') }}</dd>
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
      <section class="bingo-review-step__section" :class="{ 'bingo-review-step__section--collapsed': !expanded.rounds }">
        <h4 class="bingo-review-step__section-title" @click="toggleSection('rounds')">
          <i class="fa-solid fa-chevron-right bingo-review-step__chevron" :class="{ 'bingo-review-step__chevron--open': expanded.rounds }"></i>
          <i class="fa-solid fa-list-ol bingo-review-step__section-icon"></i>
          Rondas
          <span v-if="stepHasErrors('rounds')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
          <span v-else class="bingo-review-step__badge bingo-review-step__badge--ok"><i class="fa-solid fa-check"></i></span>
          <button type="button" class="bingo-review-step__edit-btn" @click.stop="goToStep('rounds')">
            <i class="fa-solid fa-pen"></i> Editar
          </button>
        </h4>
        <dl v-show="expanded.rounds" class="bingo-review-step__dl">
          <dt>% rondas</dt>
          <dd :class="{ 'bingo-review-step__dd--error': fieldError('roundsPct') }">{{ form.roundsPct != null ? form.roundsPct + '%' : (fieldError('roundsPct') ?? '—') }}</dd>
          <dt>% seriales</dt>
          <dd>{{ form.serialsPct != null ? form.serialsPct + '%' : '—' }}</dd>
        </dl>
      </section>

      <!-- Más / Opcional -->
      <section class="bingo-review-step__section" :class="{ 'bingo-review-step__section--collapsed': !expanded.options }">
        <h4 class="bingo-review-step__section-title" @click="toggleSection('options')">
          <i class="fa-solid fa-chevron-right bingo-review-step__chevron" :class="{ 'bingo-review-step__chevron--open': expanded.options }"></i>
          <i class="fa-solid fa-star bingo-review-step__section-icon"></i>
          Más / Opcional
          <span v-if="stepHasErrors('options')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
          <span v-else class="bingo-review-step__badge bingo-review-step__badge--ok"><i class="fa-solid fa-check"></i></span>
          <button type="button" class="bingo-review-step__edit-btn" @click.stop="goToStep('options')">
            <i class="fa-solid fa-pen"></i> Editar
          </button>
        </h4>
        <div v-show="expanded.options">
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
        </div>
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

/* Progreso */
.bingo-review-step__progress {
  display: flex; align-items: center; gap: .6rem;
}
.bingo-review-step__progress-dots { display: flex; gap: .3rem; }
.bingo-review-step__progress-dot {
  width: .55rem; height: .55rem; border-radius: 50%; flex-shrink: 0;
}
.bingo-review-step__progress-dot--error { background: var(--bs-danger, #dc3545); }
.bingo-review-step__progress-dot--incomplete { background: var(--bs-warning, #ffc107); }
.bingo-review-step__progress-dot--complete { background: var(--bs-success, #198754); }
.bingo-review-step__progress-label { font-size: .78rem; color: var(--bs-secondary, #6c757d); }

/* Alerta consolidada de secciones pendientes */
.bingo-review-step__alert {
  display: flex; align-items: flex-start; gap: .5rem;
  padding: .6rem 1rem; border-radius: .4rem;
  background: color-mix(in srgb, var(--bs-danger, #dc3545) 12%, transparent);
  color: var(--bs-danger, #dc3545);
}
.bingo-review-step__alert-body { display: flex; flex-direction: column; gap: .4rem; font-size: .8125rem; }
.bingo-review-step__alert-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
.bingo-review-step__alert-chip {
  display: inline-flex; align-items: center; gap: .3rem;
  border: none;
  background: var(--bs-danger, #dc3545);
  color: #fff;
  font-size: .75rem;
  font-weight: 600;
  padding: .25rem .6rem;
  border-radius: .3rem;
  cursor: pointer;
  white-space: nowrap;
  transition: filter .15s;
}
.bingo-review-step__alert-chip:hover { filter: brightness(1.1); }

.bingo-review-step__ready {
  display: flex; align-items: center; gap: .5rem;
  padding: .6rem 1rem; border-radius: .4rem;
  background: color-mix(in srgb, var(--bs-success, #198754) 12%, transparent);
  color: var(--bs-success, #198754);
}
.bingo-review-step__sections { display: flex; flex-direction: column; gap: 1.25rem; }
.bingo-review-step__section { border: 1px solid var(--bs-border-color, #dee2e6); border-radius: .4rem; padding: 1rem; }
.bingo-review-step__section--collapsed { padding-bottom: 1rem; }
.bingo-review-step__section-title {
  font-size: .875rem; font-weight: 600; margin-bottom: .75rem;
  display: flex; align-items: center; gap: .5rem;
  cursor: pointer; user-select: none;
}
.bingo-review-step__section--collapsed .bingo-review-step__section-title { margin-bottom: 0; }
.bingo-review-step__chevron {
  font-size: .7rem; color: var(--bs-secondary, #6c757d);
  transition: transform .15s ease;
}
.bingo-review-step__chevron--open { transform: rotate(90deg); }
.bingo-review-step__section-icon {
  font-size: .8rem;
  color: var(--bs-primary, #4a6fff);
  opacity: .85;
  width: 1rem;
  text-align: center;
}
.bingo-review-step__badge {
  display: inline-flex; align-items: center;
  font-size: .7rem; font-weight: 600; padding: .15rem .5rem; border-radius: 999px;
}
.bingo-review-step__badge--error { background: var(--bs-danger, #dc3545); color: #fff; }
.bingo-review-step__badge--ok { background: color-mix(in srgb, var(--bs-success, #198754) 15%, transparent); color: var(--bs-success, #198754); padding: .15rem .4rem; }
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
.bingo-review-step__dd--error { color: var(--bs-danger, #dc3545); font-weight: 600; }
.bingo-review-step__empty { margin: 0; font-size: .8125rem; color: var(--bs-secondary, #6c757d); font-style: italic; }
.bingo-review-step__subtitle {
  font-size: .8rem; font-weight: 600; margin: 0 0 .5rem;
  display: flex; align-items: center; gap: .4rem;
  color: var(--bs-secondary, #6c757d);
}
.bingo-review-step__section--meta {
  background: color-mix(in srgb, var(--bs-secondary, #6c757d) 5%, transparent);
}
</style>
