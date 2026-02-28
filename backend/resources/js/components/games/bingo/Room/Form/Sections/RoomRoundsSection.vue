<script setup lang="ts" name="RoomRoundsSection">
  import { reactive, watch, computed } from 'vue'

  interface RoundItem {
    index: number
    percentage: number
  }

  interface RoomRoundsForm {
    rounds: number
    roundsPct: number
    roundsDistribution?: RoundItem[]
  }

  const props = defineProps<{
    modelValue: RoomRoundsForm
    errors?: Record<string, string | undefined>
    totalPercentage?: number
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: RoomRoundsForm): void
    (e: 'redistribute'): void
  }>()

  /**
   * Estado local desacoplado
   */
  const localForm = reactive<RoomRoundsForm>({
    rounds: props.modelValue.rounds ?? 1,
    roundsPct: props.modelValue.roundsPct ?? 0,
    roundsDistribution: props.modelValue.roundsDistribution ?? []
  })

  /**
   * Generar distribución automática si cambia cantidad
   */
  watch(
    () => localForm.rounds,
    newRounds => {
      if (!newRounds || newRounds < 1) return

      if (!localForm.roundsDistribution || localForm.roundsDistribution.length !== newRounds) {
        localForm.roundsDistribution = Array.from({ length: newRounds }, (_, i) => ({
          index: i + 1,
          percentage: 0
        }))
      }
    },
    { immediate: true }
  )

  /**
   * Sync hacia padre
   */
  watch(
    localForm,
    () => {
      emit('update:modelValue', { ...localForm })
    },
    { deep: true }
  )

  const roundsError = computed(() => props.errors?.rounds)
  const roundsPctError = computed(() => props.errors?.roundsPct)
</script>

<template>
  <div class="room-rounds-section mt-4">
    <h5 class="mb-3">Configuración de Rondas</h5>

    <div class="row g-3">
      <!-- Cantidad de rondas -->
      <div class="col-md-4">
        <label class="form-label">Cantidad de rondas</label>
        <input
          type="number"
          min="1"
          class="form-control"
          v-model.number="localForm.rounds"
          :class="{ 'is-invalid': roundsError }"
        />
        <div class="invalid-feedback" v-if="roundsError">
          {{ roundsError }}
        </div>
      </div>

      <!-- % total premio -->
      <div class="col-md-4">
        <label class="form-label">% Total Premio</label>
        <input
          type="number"
          min="0"
          max="100"
          class="form-control"
          v-model.number="localForm.roundsPct"
          :class="{ 'is-invalid': roundsPctError }"
        />
        <div class="invalid-feedback" v-if="roundsPctError">
          {{ roundsPctError }}
        </div>
      </div>

      <!-- Total calculado -->
      <div class="col-md-4 d-flex align-items-end">
        <div>
          <div class="small text-muted">Distribución actual</div>
          <strong> {{ totalPercentage ?? 0 }}% </strong>
        </div>
      </div>
    </div>

    <!-- Distribución por ronda -->
    <div v-if="localForm.roundsDistribution?.length" class="mt-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Distribución por ronda</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" @click="$emit('redistribute')">
          Redistribuir automático
        </button>
      </div>

      <div
        v-for="round in localForm.roundsDistribution"
        :key="round.index"
        class="row align-items-center mb-2"
      >
        <div class="col-3">
          <strong>Ronda {{ round.index }}</strong>
        </div>

        <div class="col-9">
          <input
            type="number"
            min="0"
            max="100"
            class="form-control"
            v-model.number="round.percentage"
          />
        </div>
      </div>
    </div>
  </div>
</template>
