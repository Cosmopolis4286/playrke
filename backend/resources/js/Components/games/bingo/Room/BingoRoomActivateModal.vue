<script setup lang="ts">
  import { computed, ref, watch } from 'vue'

  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'

  import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

  const props = defineProps<{
    modelValue: boolean
    room: BingoRoom | null
    activating?: boolean
    scheduling?: boolean
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'activate-now', roomId: string | number): void
    (e: 'schedule-start', roomId: string | number, date: string, time: string): void
  }>()

  type Mode = 'choose' | 'schedule'

  const mode = ref<Mode>('choose')
  const date = ref<string | null>(null)
  const time = ref<string | null>(null)

  const minDate = computed(() => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
  })

  const minTime = computed(() => {
    if (date.value !== minDate.value) return undefined
    const min15 = new Date(Date.now() + 15 * 60 * 1000)
    return `${String(min15.getHours()).padStart(2, '0')}:${String(min15.getMinutes()).padStart(2, '0')}`
  })

  const canSchedule = computed(() => Boolean(date.value && time.value))

  watch(
    () => props.modelValue,
    open => {
      if (open) {
        mode.value = 'choose'
        date.value = null
        time.value = null
      }
    }
  )

  function goToSchedule(): void {
    mode.value = 'schedule'
  }

  function backToChoose(): void {
    mode.value = 'choose'
  }

  function handleActivateNow(): void {
    if (!props.room) return
    emit('activate-now', props.room.id)
  }

  function handleScheduleStart(): void {
    if (!props.room || !date.value || !time.value) return
    emit('schedule-start', props.room.id, date.value, time.value)
  }
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Inicio de la sala"
    icon="fa-play-circle"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="room">
      <p class="mb-3">
        Sala <b>{{ room.uuid_code }}</b> sin horario fijo. Elige cómo deseas iniciarla:
      </p>

      <!-- Selección de acción -->
      <div v-if="mode === 'choose'" class="d-flex flex-column gap-2">
        <button
          type="button"
          class="btn btn-success text-white d-flex align-items-center justify-content-center gap-2"
          :disabled="activating"
          @click="handleActivateNow"
        >
          <i class="fas fa-play"></i>
          {{ activating ? 'Activando...' : 'Activar ahora' }}
        </button>
        <small class="text-muted text-center">El sorteo iniciará de inmediato.</small>

        <hr class="my-2" />

        <button
          type="button"
          class="btn btn-primary d-flex align-items-center justify-content-center gap-2"
          @click="goToSchedule"
        >
          <i class="fas fa-calendar-alt"></i>
          Programar inicio
        </button>
        <small class="text-muted text-center">
          Define una fecha y hora; la sala pasará a "Programada" y se iniciará automáticamente.
        </small>
      </div>

      <!-- Formulario de programación -->
      <div v-else class="row">
        <FormInput
          label="Fecha"
          name="schedule_date"
          type="date"
          required
          :min="minDate"
          col="col-sm-6"
          :modelValue="date ?? ''"
          @update:modelValue="date = $event ? String($event) : null"
        />
        <FormInput
          label="Hora"
          name="schedule_time"
          type="time"
          required
          :min="minTime"
          col="col-sm-6"
          :modelValue="time ?? ''"
          @update:modelValue="time = $event ? String($event) : null"
        />

        <div class="col-12 d-flex justify-content-between mt-2">
          <button type="button" class="btn btn-secondary" @click="backToChoose">
            <i class="fas fa-arrow-left"></i> Volver
          </button>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="!canSchedule || scheduling"
            @click="handleScheduleStart"
          >
            <i class="fas fa-calendar-check"></i>
            {{ scheduling ? 'Programando...' : 'Programar inicio' }}
          </button>
        </div>
      </div>
    </div>
  </Modal>
</template>
