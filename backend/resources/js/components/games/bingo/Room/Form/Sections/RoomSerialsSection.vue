<script setup lang="ts" name="RoomSerialsSection">
  import { reactive, watch, computed } from 'vue'

  interface RoomSerialsForm {
    serialStart: number
    serialEnd: number
    totalSerials?: number
    prefix?: string
  }

  const props = defineProps<{
    modelValue: RoomSerialsForm
    errors?: Record<string, string | undefined>
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: RoomSerialsForm): void
  }>()

  /**
   * Estado local
   */
  const localForm = reactive<RoomSerialsForm>({
    serialStart: props.modelValue.serialStart ?? 1,
    serialEnd: props.modelValue.serialEnd ?? 1,
    prefix: props.modelValue.prefix ?? '',
    totalSerials: props.modelValue.totalSerials ?? 0
  })

  /**
   * Calcular total automáticamente
   */
  const calculatedTotal = computed(() => {
    if (localForm.serialEnd < localForm.serialStart) return 0
    return localForm.serialEnd - localForm.serialStart + 1
  })

  watch(
    calculatedTotal,
    total => {
      localForm.totalSerials = total
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

  const startError = computed(() => props.errors?.serialStart)
  const endError = computed(() => props.errors?.serialEnd)
</script>

<template>
  <div class="room-serials-section mt-4">
    <h5 class="mb-3">Configuración de Seriales</h5>

    <div class="row g-3">
      <!-- Prefijo -->
      <div class="col-md-4">
        <label class="form-label">Prefijo (opcional)</label>
        <input
          type="text"
          class="form-control"
          v-model="localForm.prefix"
          placeholder="Ej: BR-2026-"
        />
      </div>

      <!-- Serial inicio -->
      <div class="col-md-4">
        <label class="form-label">Serial inicial</label>
        <input
          type="number"
          min="1"
          class="form-control"
          v-model.number="localForm.serialStart"
          :class="{ 'is-invalid': startError }"
        />
        <div class="invalid-feedback" v-if="startError">
          {{ startError }}
        </div>
      </div>

      <!-- Serial fin -->
      <div class="col-md-4">
        <label class="form-label">Serial final</label>
        <input
          type="number"
          min="1"
          class="form-control"
          v-model.number="localForm.serialEnd"
          :class="{ 'is-invalid': endError }"
        />
        <div class="invalid-feedback" v-if="endError">
          {{ endError }}
        </div>
      </div>
    </div>

    <!-- Total calculado -->
    <div class="bg-light mt-4 rounded border p-3">
      <div class="d-flex justify-content-between">
        <span>Total de cartones generados:</span>
        <strong>{{ calculatedTotal }}</strong>
      </div>

      <div v-if="localForm.serialEnd < localForm.serialStart" class="text-danger small mt-2">
        El serial final no puede ser menor al inicial.
      </div>
    </div>
  </div>
</template>
