<script setup lang="ts">
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import { BINGO_ROOM_INT_LIMITS } from '@/js/composables/games/bingo/room/shared/bingoRoomFormSchema'
  import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

  const props = defineProps<{
    modelValue: BingoRoomFormData
    errors: Record<string, any>
    /** Piso de jugadores según el modo de juego (2; 1 en Solitario). */
    playersFloor: number
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: BingoRoomFormData): void
  }>()

  const C = BINGO_ROOM_INT_LIMITS.cards
  const P = BINGO_ROOM_INT_LIMITS.players

  type CapacityField = 'minCardsToStart' | 'maxCardsToStart' | 'minPlayers' | 'maxPlayers'

  function update(field: CapacityField, value: string | number | null) {
    emit('update:modelValue', {
      ...props.modelValue,
      [field]: value === '' || value === null ? null : Number(value)
    } as BingoRoomFormData)
  }
</script>

<template>
  <div style="display: contents">
    <div class="col-12 mb-2">
      <small class="text-muted d-block" style="font-size: 0.74rem">
        <i class="fa-solid fa-circle-info fa-fw"></i>
        Los mínimos son obligatorios: si al llegar la hora del sorteo no se alcanzaron,
        la sala se cancela y se reembolsa a los jugadores. Los máximos son opcionales
        (vacío = sin límite).
      </small>
    </div>

    <!-- Jugadores primero: el mínimo de cartones depende del de jugadores. -->
    <div class="col-12">
      <p class="mb-2" style="font-weight: 500; font-size: 0.8rem">
        <i class="fa-solid fa-users mr-1" style="opacity: 0.6"></i>
        Jugadores
      </p>
      <div class="row">
        <FormInput
          label="Mín. Jugadores"
          name="min_players"
          :error="(errors as any).minPlayers"
          :hint="playersFloor > 1
            ? `El sorteo no inicia hasta alcanzar este número de jugadores (mínimo ${playersFloor}: un bingo necesita competencia).`
            : 'El sorteo no inicia hasta alcanzar este número de jugadores (modo Solitario: desde 1).'"
          type="number"
          integer
          required
          :placeholder="`Ej: ${playersFloor}`"
          :step="1"
          :min="playersFloor"
          :max="P.max"
          col="col-sm-6"
          :modelValue="modelValue.minPlayers ?? ''"
          @update:modelValue="update('minPlayers', $event)"
        />

        <FormInput
          label="Máx. Jugadores"
          name="max_players"
          :error="(errors as any).maxPlayers"
          hint="Cupo de la sala: al alcanzarlo se bloquea la entrada de nuevos jugadores. Vacío = sin límite."
          type="number"
          integer
          placeholder="Sin límite"
          :step="1"
          :min="P.min"
          :max="P.max"
          col="col-sm-6"
          :modelValue="modelValue.maxPlayers ?? ''"
          @update:modelValue="update('maxPlayers', $event)"
        />
      </div>
    </div>

    <div class="col-12">
      <hr style="margin: 0.25rem 0 0.75rem" />
      <p class="mb-2" style="font-weight: 500; font-size: 0.8rem">
        <i class="fa-solid fa-layer-group mr-1" style="opacity: 0.6"></i>
        Cartones
      </p>
      <div class="row">
        <FormInput
          label="Mín. Cartones"
          name="min_cards_to_start"
          :error="(errors as any).minCardsToStart"
          hint="El sorteo no inicia hasta vender esta cantidad. No puede ser menor al mínimo de jugadores (cada uno tiene al menos un cartón)."
          type="number"
          integer
          required
          :placeholder="`Ej: ${modelValue.minPlayers ?? playersFloor}`"
          :step="1"
          :min="C.min"
          :max="C.max"
          col="col-sm-6"
          :modelValue="modelValue.minCardsToStart ?? ''"
          @update:modelValue="update('minCardsToStart', $event)"
        />

        <FormInput
          label="Máx. Cartones"
          name="max_cards_to_start"
          :error="(errors as any).maxCardsToStart"
          hint="Tope de cartones vendibles: al alcanzarlo se bloquea la venta. Debe cubrir el máximo de jugadores. Vacío = sin límite."
          type="number"
          integer
          placeholder="Sin límite"
          :step="1"
          :min="C.min"
          :max="C.max"
          col="col-sm-6"
          :modelValue="modelValue.maxCardsToStart ?? ''"
          @update:modelValue="update('maxCardsToStart', $event)"
        />
      </div>
    </div>
  </div>
</template>
