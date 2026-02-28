<script setup lang="ts">
  import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

  const props = defineProps<{
    modelValue: BingoRoomFormData
    errors: Record<string, any>
    percentageLimits: { serialsPct: { min: number; max: number } }
    serialsPct: string | number
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: BingoRoomFormData): void
    (e: 'update:serialsPct', value: string | number): void
  }>()
</script>

<template>
  <div class="col-12">
    <hr style="margin: 0.25rem 0 0.75rem" />
    <div class="d-flex align-items-center flex-wrap" style="gap: 1.25rem">
      <!-- Toggle -->
      <div class="custom-control custom-switch" style="padding-left: 2.25rem; flex-shrink: 0">
        <input
          id="use_serials"
          name="use_serials"
          type="checkbox"
          class="custom-control-input"
          :checked="modelValue.useSerials ?? false"
          @change="emit('update:modelValue', { ...modelValue, useSerials: ($event.target as HTMLInputElement).checked })"
        />
        <label class="custom-control-label" for="use_serials" style="font-weight: 500">
          ¿Usar Seriales?
        </label>
      </div>

      <template v-if="modelValue.useSerials">
        <!-- %Bote Seriales -->
        <div>
          <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
            %Bote Seriales <span class="text-danger">*</span>
          </label>
          <div class="input-group input-group-sm" style="width: 130px">
            <input
              name="serials_pct"
              type="number"
              :class="['form-control', { 'is-invalid': errors.serialsPct }]"
              placeholder="Ej: 5"
              :step="0.25"
              :min="percentageLimits.serialsPct.min"
              :max="percentageLimits.serialsPct.max"
              :value="serialsPct"
              @change="emit('update:serialsPct', Number(($event.target as HTMLInputElement).value) as any)"
            />
            <div class="input-group-append">
              <span class="input-group-text">%</span>
            </div>
          </div>
          <small v-if="errors.serialsPct" class="text-danger" style="font-size: 0.72rem">
            {{ errors.serialsPct }}
          </small>
        </div>

        <!-- #Máx. Seriales -->
        <div>
          <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
            #Máx. Seriales <span class="text-danger">*</span>
          </label>
          <input
            name="num_serials"
            type="number"
            :class="['form-control form-control-sm', { 'is-invalid': errors.numSerials }]"
            placeholder="Ej: 10"
            :step="1"
            :min="1"
            :max="30"
            style="width: 110px"
            :value="modelValue.numSerials ?? ''"
            @change="emit('update:modelValue', { ...modelValue, numSerials: Number(($event.target as HTMLInputElement).value) || null })"
          />
          <small v-if="errors.numSerials" class="text-danger" style="font-size: 0.72rem">
            {{ errors.numSerials }}
          </small>
        </div>
      </template>
    </div>
  </div>

  <!-- Alerta suma de porcentajes -->
  <div v-if="errors.percentageSum" class="col-12">
    <div class="alert alert-danger mb-0 px-3 py-2" style="font-size: 0.82rem">
      <i class="fa-solid fa-triangle-exclamation"></i>
      {{ errors.percentageSum }}
      <small class="d-block mt-1" style="opacity: 0.85">
        %Sala ({{ modelValue.roomCommn ?? 0 }}) + %Rondas ({{ modelValue.roundsPct ?? 0 }})
        <template v-if="modelValue.useSerials"> + %Seriales ({{ modelValue.serialsPct ?? 0 }})</template>
        + %Jackpot ({{ modelValue.jackpotPct ?? 0 }}) =
        {{
          (
            Number(modelValue.roomCommn) +
            Number(modelValue.roundsPct) +
            (modelValue.useSerials ? Number(modelValue.serialsPct) : 0) +
            Number(modelValue.jackpotPct)
          ).toFixed(2)
        }}%
      </small>
    </div>
  </div>
</template>
