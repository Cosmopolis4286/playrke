<script setup lang="ts" name="BingoRoomReviewStep">
import { computed } from 'vue'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
import { STEP_LABELS, STEP_ERROR_KEYS, type BingoRoomStep } from '@/js/composables/games/bingo/room/shared/useBingoRoomStepper'

const props = defineProps<{
  form:   BingoRoomFormData
  errors: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update:form', value: BingoRoomFormData): void
}>()

type StepKey = Exclude<BingoRoomStep, 'review'>

const STEPS: StepKey[] = ['basic', 'currencies', 'rounds']

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
          Datos básicos
          <span v-if="stepHasErrors('basic')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
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
        </dl>
      </section>

      <!-- Monedas -->
      <section class="bingo-review-step__section">
        <h4 class="bingo-review-step__section-title">
          Monedas y precios
          <span v-if="stepHasErrors('currencies')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
        </h4>
        <dl class="bingo-review-step__dl">
          <dt>Monedas permitidas</dt>
          <dd>{{ (form.allowedCurrencies ?? []).join(', ') || '—' }}</dd>
          <dt>Precio base (USD)</dt>
          <dd>{{ form.cardPriceUsd != null ? '$' + form.cardPriceUsd : '—' }}</dd>
        </dl>
      </section>

      <!-- Rondas -->
      <section class="bingo-review-step__section">
        <h4 class="bingo-review-step__section-title">
          Rondas
          <span v-if="stepHasErrors('rounds')" class="bingo-review-step__badge bingo-review-step__badge--error">Con errores</span>
        </h4>
        <dl class="bingo-review-step__dl">
          <dt>% rondas</dt>
          <dd>{{ form.roundsPct != null ? form.roundsPct + '%' : '—' }}</dd>
          <dt>% seriales</dt>
          <dd>{{ form.serialsPct != null ? form.serialsPct + '%' : '—' }}</dd>
        </dl>
      </section>

      <!-- Activación -->
      <section class="bingo-review-step__section">
        <h4 class="bingo-review-step__section-title">Activación</h4>
        <div class="custom-control custom-switch">
          <input
            type="checkbox"
            class="custom-control-input"
            id="activateRoom"
            :disabled="isScheduledDateInPast"
            :checked="form.activateRoom ?? false"
            @change="onActivateRoomChange(($event.target as HTMLInputElement).checked)"
          />
          <label class="custom-control-label" for="activateRoom">¿Activar sala?</label>
        </div>
        <small v-if="isScheduledDateInPast" class="text-danger d-block mt-1">
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
.bingo-review-step__badge {
  font-size: .7rem; font-weight: 600; padding: .15rem .5rem; border-radius: 999px;
}
.bingo-review-step__badge--error { background: var(--bs-danger, #dc3545); color: #fff; }
.bingo-review-step__dl { display: grid; grid-template-columns: auto 1fr; gap: .25rem .75rem; margin: 0; }
.bingo-review-step__dl dt { font-weight: 500; color: var(--bs-secondary, #6c757d); font-size: .8125rem; }
.bingo-review-step__dl dd { margin: 0; font-size: .8125rem; }
</style>
