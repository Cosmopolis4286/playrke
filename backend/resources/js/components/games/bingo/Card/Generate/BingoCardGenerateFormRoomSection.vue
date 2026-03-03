<script setup lang="ts" name="BingoCardGenerateFormRoomSection">
  import { computed } from 'vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  import type { Room } from '@/js/types/games/bingo/card/bingo-card-generate'

  const props = defineProps<{
    rooms: Room[]
    selectedRoom: Room | null
    numCtns: number
    roomCtnPrice: number
    date: string
  }>()

  const emit = defineEmits<{
    (e: 'update:selectedRoom', value: Room | null): void
    (e: 'update:numCtns', value: number): void
    (e: 'update:date', value: string): void
  }>()

  const minDate = computed(() => new Date().toISOString().split('T')[0])

  const roomOptions = computed(() =>
    props.rooms.map(room => ({
      id: room.id,
      name: room.uuid_code
    }))
  )

  function handleNumCtns(value: string | number) {
    const raw = Number(value)
    const clamped = Math.max(1, Math.min(40, raw || 1))
    emit('update:numCtns', clamped)
  }
</script>

<template>
  <div class="row">
    <!-- Fecha -->
    <FormInput
      label="Fecha"
      name="date"
      type="date"
      required
      :min="minDate"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="date"
      @update:modelValue="emit('update:date', $event)"
    />

    <!-- Sala -->
    <FormSelect
      label="Sala de Bingo"
      name="room_id"
      :options="roomOptions"
      optionValue="id"
      optionLabel="name"
      placeholder="Seleccione una sala"
      col="col-12 col-md-6"
      :modelValue="selectedRoom?.id ?? null"
      @update:modelValue="emit('update:selectedRoom', rooms.find(r => r.id === $event) ?? null)"
    />

    <!-- Precio -->
    <FormInput
      label="Precio Cartón (USD)"
      name="room_price"
      type="number"
      col="col-6 col-md-3"
      :modelValue="roomCtnPrice"
      readonly
      inputValidState="valid"
    />

    <!-- Cantidad -->
    <FormInput
      label="# Cartones"
      name="num_ctns"
      type="number"
      min="1"
      max="40"
      col="col-6 col-md-3"
      :disabled="!selectedRoom"
      :modelValue="numCtns"
      @update:modelValue="handleNumCtns"
    />
  </div>
</template>
