<script setup lang="ts">
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

  defineProps<{
    modelValue: BingoRoomFormData
    errors: Record<string, any>
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: BingoRoomFormData): void
  }>()
</script>

<template>
  <div style="display: contents">
    <div class="col-12 mb-2">
      <small class="text-muted d-block" style="font-size: 0.74rem">
        <i class="fa-solid fa-circle-info fa-fw"></i>
        Todos los límites son opcionales e independientes — podés dejar cualquiera
        vacío, fijar solo uno de cada par, o ambos (el mínimo no puede superar al máximo).
      </small>
    </div>

    <div class="col-12">
      <p class="mb-2" style="font-weight: 500; font-size: 0.8rem">
        <i class="fa-solid fa-layer-group mr-1" style="opacity: 0.6"></i>
        Cartones
      </p>
      <div class="row">
        <FormInput
          label="Mín. Cartones"
          name="min_cards_to_start"
          :error="(errors as any).minCardsToStart"
          type="number"
          placeholder="Sin condición"
          :step="1"
          :min="1"
          :max="10000"
          col="col-sm-6 col-md-3"
          :modelValue="(modelValue as any).minCardsToStart ?? null"
          @update:modelValue="
            emit('update:modelValue', {
              ...modelValue,
              minCardsToStart: $event === '' || $event === null ? null : Number($event)
            } as any)
          "
        />
        <small class="col-sm-6 col-md-3 d-block text-muted" style="font-size: 0.74rem; margin-top: -0.6rem; margin-bottom: 0.75rem">
          El sorteo no inicia hasta vender esta cantidad de cartones.
        </small>

        <FormInput
          label="Máx. Cartones"
          name="max_cards_to_start"
          :error="(errors as any).maxCardsToStart"
          type="number"
          placeholder="Sin límite"
          :step="1"
          :min="1"
          :max="10000"
          col="col-sm-6 col-md-3"
          :modelValue="(modelValue as any).maxCardsToStart ?? null"
          @update:modelValue="
            emit('update:modelValue', {
              ...modelValue,
              maxCardsToStart: $event === '' || $event === null ? null : Number($event)
            } as any)
          "
        />
        <small class="col-sm-6 col-md-3 d-block text-muted" style="font-size: 0.74rem; margin-top: -0.6rem; margin-bottom: 0.75rem">
          Tope de cartones vendibles en la sala (informativo, no bloquea la venta).
        </small>
      </div>
    </div>

    <div class="col-12">
      <hr style="margin: 0.25rem 0 0.75rem" />
      <p class="mb-2" style="font-weight: 500; font-size: 0.8rem">
        <i class="fa-solid fa-users mr-1" style="opacity: 0.6"></i>
        Jugadores
      </p>
      <div class="row">
        <FormInput
          label="Mín. Jugadores"
          name="min_players"
          :error="(errors as any).minPlayers"
          type="number"
          placeholder="Sin condición"
          :step="1"
          :min="1"
          :max="100000"
          col="col-sm-6 col-md-3"
          :modelValue="(modelValue as any).minPlayers ?? null"
          @update:modelValue="
            emit('update:modelValue', {
              ...modelValue,
              minPlayers: $event === '' || $event === null ? null : Number($event)
            } as any)
          "
        />
        <small class="col-sm-6 col-md-3 d-block text-muted" style="font-size: 0.74rem; margin-top: -0.6rem; margin-bottom: 0.75rem">
          El sorteo no inicia hasta alcanzar este número de jugadores.
        </small>

        <FormInput
          label="Máx. Jugadores"
          name="max_players"
          :error="(errors as any).maxPlayers"
          type="number"
          placeholder="Sin límite"
          :step="1"
          :min="1"
          :max="100000"
          col="col-sm-6 col-md-3"
          :modelValue="(modelValue as any).maxPlayers ?? null"
          @update:modelValue="
            emit('update:modelValue', {
              ...modelValue,
              maxPlayers: $event === '' || $event === null ? null : Number($event)
            } as any)
          "
        />
        <small class="col-sm-6 col-md-3 d-block text-muted" style="font-size: 0.74rem; margin-top: -0.6rem; margin-bottom: 0.75rem">
          Cupo máximo de la sala — se bloquea la entrada de nuevos jugadores al alcanzarlo.
        </small>
      </div>
    </div>
  </div>
</template>
