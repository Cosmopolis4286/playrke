<script setup lang="ts">
  import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
  import {
    BINGO_ROOM_INT_LIMITS,
    BINGO_ROOM_AMOUNT_LIMITS
  } from '@/js/composables/games/bingo/room/shared/bingoRoomFormSchema'
  import { sanitizeNumericInput } from '../Utils/numericInput'

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

  type IntField = 'customCardEveryX' | 'numSerials' | 'maxSpecialCards'
  type PctField = 'customCardPricePct' | 'moveFreeSpacePricePct'

  /** Entero saneado al escribir/pegar (ver sanitizeNumericInput); commit=true en change/blur. */
  function onIntInput(field: IntField, event: Event, commit = false): void {
    const value = sanitizeNumericInput(event, { ...BINGO_ROOM_INT_LIMITS[field], integer: true }, commit)
    emit('update:modelValue', { ...props.modelValue, [field]: value } as BingoRoomFormData)
  }

  function onPctInput(field: PctField, event: Event, commit = false): void {
    const value = sanitizeNumericInput(event, BINGO_ROOM_AMOUNT_LIMITS[field], commit)
    emit('update:modelValue', { ...props.modelValue, [field]: value } as BingoRoomFormData)
  }

  function onSerialsPctInput(event: Event, commit = false): void {
    const value = sanitizeNumericInput(event, props.percentageLimits.serialsPct, commit)
    emit('update:serialsPct', value ?? '')
  }
</script>

<template>
  <div style="display: contents">
    <!-- Cartón personalizado -->
    <div class="col-12">
      <div class="d-flex align-items-center flex-wrap" style="gap: 1.25rem">
        <!-- Toggle -->
        <div class="custom-control custom-switch" style="padding-left: 2.25rem; flex-shrink: 0">
          <input
            id="allow_custom_card"
            name="allow_custom_card"
            type="checkbox"
            class="custom-control-input"
            :checked="(modelValue as any).allowCustomCard ?? false"
            @change="
              emit('update:modelValue', {
                ...modelValue,
                allowCustomCard: ($event.target as HTMLInputElement).checked,
                ...(!($event.target as HTMLInputElement).checked
                  ? { customCardMode: null, customCardPricePct: null, customCardEveryX: null, moveFreeSpaceMode: null, moveFreeSpacePricePct: null }
                  : {})
              } as any)
            "
          />
          <label class="custom-control-label" for="allow_custom_card" style="font-weight: 500">
            Permitir cartón personalizado
          </label>
        </div>

        <template v-if="(modelValue as any).allowCustomCard">
          <!-- Modalidad del cartón personalizado -->
          <div>
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Modalidad <span class="text-danger">*</span>
            </label>
            <select
              name="custom_card_mode"
              :class="['form-control form-control-sm', { 'is-invalid': (errors as any).customCardMode }]"
              style="width: 220px"
              :value="(modelValue as any).customCardMode ?? ''"
              @change="
                emit('update:modelValue', {
                  ...modelValue,
                  customCardMode: ($event.target as HTMLSelectElement).value || null,
                  ...(($event.target as HTMLSelectElement).value !== 'paid' ? { customCardPricePct: null } : {}),
                  ...(($event.target as HTMLSelectElement).value !== 'every_x' ? { customCardEveryX: null } : {})
                } as any)
              "
            >
              <option value="" disabled>Seleccione una opción</option>
              <option value="paid">Permitir con costo adicional</option>
              <option value="by_range">Precio inteligente (automático)</option>
              <option value="every_x">Permitir cada X cartones</option>
              <option value="free">Uso libre</option>
            </select>
            <small v-if="(errors as any).customCardMode" class="text-danger" style="font-size: 0.72rem">
              {{ (errors as any).customCardMode }}
            </small>
          </div>

          <!-- Precio cartón personalizado (solo modalidad "con costo adicional") -->
          <div v-if="(modelValue as any).customCardMode === 'paid'">
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Precio cartón personalizado <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-sm" style="width: 130px">
              <input
                name="custom_card_price_pct"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                :class="['form-control', { 'is-invalid': (errors as any).customCardPricePct }]"
                placeholder="Ej: 2"
                :value="(modelValue as any).customCardPricePct ?? ''"
                @input="onPctInput('customCardPricePct', $event)"
                @change="onPctInput('customCardPricePct', $event, true)"
              />
              <div class="input-group-append">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <small v-if="(errors as any).customCardPricePct" class="text-danger" style="font-size: 0.72rem">
              {{ (errors as any).customCardPricePct }}
            </small>
          </div>

          <!-- Precio inteligente por rango (solo modalidad "by_range") -->
          <div v-if="(modelValue as any).customCardMode === 'by_range'" style="max-width: 320px">
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Recargo calculado automáticamente
            </label>
            <small class="d-block text-muted" style="font-size: 0.72rem; line-height: 1.35">
              El sistema aplica un recargo entre 1% y 2% sobre el precio del cartón,
              según su valor (nunca supera el 5%). No requiere configurar un % manual.
            </small>
          </div>

          <!-- Cada X cartones (solo modalidad "cada X cartones") -->
          <div v-if="(modelValue as any).customCardMode === 'every_x'">
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Cada cuántos cartones <span class="text-danger">*</span>
            </label>
            <input
              name="custom_card_every_x"
              type="text"
              inputmode="numeric"
              autocomplete="off"
              :class="['form-control form-control-sm', { 'is-invalid': (errors as any).customCardEveryX }]"
              placeholder="Ej: 5"
              style="width: 110px"
              :value="(modelValue as any).customCardEveryX ?? ''"
              @input="onIntInput('customCardEveryX', $event)"
              @change="onIntInput('customCardEveryX', $event, true)"
            />
            <small v-if="!(errors as any).customCardEveryX" class="d-block text-muted" style="font-size: 0.72rem">
              Entre {{ BINGO_ROOM_INT_LIMITS.customCardEveryX.min }} y {{ BINGO_ROOM_INT_LIMITS.customCardEveryX.max }}.
            </small>
            <small v-if="(errors as any).customCardEveryX" class="text-danger" style="font-size: 0.72rem">
              {{ (errors as any).customCardEveryX }}
            </small>
          </div>

          <!-- Mover casilla libre (solo BINGO_75, única variante con casilla libre) -->
          <div v-if="(modelValue as any).gameVariant === 'BINGO_75'">
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Mover casilla LIBRE
            </label>
            <select
              name="move_free_space_mode"
              :class="['form-control form-control-sm', { 'is-invalid': (errors as any).moveFreeSpaceMode }]"
              style="width: 220px"
              :value="(modelValue as any).moveFreeSpaceMode ?? ''"
              @change="
                emit('update:modelValue', {
                  ...modelValue,
                  moveFreeSpaceMode: ($event.target as HTMLSelectElement).value || null,
                  ...(($event.target as HTMLSelectElement).value !== 'paid' ? { moveFreeSpacePricePct: null } : {})
                } as any)
              "
            >
              <option value="">No permitir</option>
              <option value="paid">Permitir con costo adicional</option>
              <option value="by_range">Precio inteligente (automático)</option>
              <option value="free">Uso libre</option>
            </select>
            <small v-if="(errors as any).moveFreeSpaceMode" class="text-danger d-block" style="font-size: 0.72rem">
              {{ (errors as any).moveFreeSpaceMode }}
            </small>
          </div>

          <!-- Precio mover casilla libre (solo modalidad "con costo adicional") -->
          <div v-if="(modelValue as any).moveFreeSpaceMode === 'paid'">
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Precio mover casilla libre <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-sm" style="width: 130px">
              <input
                name="move_free_space_price_pct"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                :class="['form-control', { 'is-invalid': (errors as any).moveFreeSpacePricePct }]"
                placeholder="Ej: 2"
                :value="(modelValue as any).moveFreeSpacePricePct ?? ''"
                @input="onPctInput('moveFreeSpacePricePct', $event)"
                @change="onPctInput('moveFreeSpacePricePct', $event, true)"
              />
              <div class="input-group-append">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <small v-if="(errors as any).moveFreeSpacePricePct" class="text-danger" style="font-size: 0.72rem">
              {{ (errors as any).moveFreeSpacePricePct }}
            </small>
          </div>

          <!-- Precio inteligente por rango (solo modalidad "by_range") -->
          <div v-if="(modelValue as any).moveFreeSpaceMode === 'by_range'" style="max-width: 320px">
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Recargo calculado automáticamente
            </label>
            <small class="d-block text-muted" style="font-size: 0.72rem; line-height: 1.35">
              El sistema aplica un recargo entre 1% y 2% sobre el precio del cartón,
              según su valor (nunca supera el 5%). No requiere configurar un % manual.
            </small>
          </div>
        </template>
      </div>
    </div>

    <!-- Cartón especial -->
    <div class="col-12">
      <hr style="margin: 0.25rem 0 0.75rem" />
      <div class="d-flex align-items-center flex-wrap" style="gap: 1.25rem">
        <!-- Toggle -->
        <div class="custom-control custom-switch" style="padding-left: 2.25rem; flex-shrink: 0">
          <input
            id="allow_special_card"
            name="allow_special_card"
            type="checkbox"
            class="custom-control-input"
            :checked="(modelValue as any).allowSpecialCard ?? false"
            @change="
              emit('update:modelValue', {
                ...modelValue,
                allowSpecialCard: ($event.target as HTMLInputElement).checked,
                ...(!($event.target as HTMLInputElement).checked ? { maxSpecialCards: null } : {})
              } as any)
            "
          />
          <label class="custom-control-label" for="allow_special_card" style="font-weight: 500">
            Permitir cartón especial
          </label>
        </div>

        <template v-if="(modelValue as any).allowSpecialCard">
          <!-- Máx. cartones especiales -->
          <div>
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Máx. cartones especiales <span class="text-danger">*</span>
            </label>
            <input
              name="max_special_cards"
              type="text"
              inputmode="numeric"
              autocomplete="off"
              :class="['form-control form-control-sm', { 'is-invalid': (errors as any).maxSpecialCards }]"
              placeholder="Ej: 3"
              style="width: 110px"
              :value="(modelValue as any).maxSpecialCards ?? ''"
              @input="onIntInput('maxSpecialCards', $event)"
              @change="onIntInput('maxSpecialCards', $event, true)"
            />
            <small v-if="(errors as any).maxSpecialCards" class="text-danger" style="font-size: 0.72rem">
              {{ (errors as any).maxSpecialCards }}
            </small>
          </div>
        </template>
      </div>
    </div>

    <!-- Seriales -->
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
                type="text"
                inputmode="decimal"
                autocomplete="off"
                :class="['form-control', { 'is-invalid': errors.serialsPct }]"
                placeholder="Ej: 5"
                :value="serialsPct"
                @input="onSerialsPctInput($event)"
                @change="onSerialsPctInput($event, true)"
              />
              <div class="input-group-append">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <small v-if="errors.serialsPct" class="d-block text-danger" style="font-size: 0.72rem">
              {{ errors.serialsPct }}
            </small>
            <small v-else class="d-block text-muted" style="font-size: 0.72rem">
              Entre {{ percentageLimits.serialsPct.min }} y {{ percentageLimits.serialsPct.max }}.
            </small>
          </div>

          <!-- #Máx. Seriales -->
          <div>
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              #Máx. Seriales <span class="text-danger">*</span>
            </label>
            <input
              name="num_serials"
              type="text"
              inputmode="numeric"
              autocomplete="off"
              :class="['form-control form-control-sm', { 'is-invalid': errors.numSerials }]"
              placeholder="Ej: 10"
              style="width: 110px"
              :value="modelValue.numSerials ?? ''"
              @input="onIntInput('numSerials', $event)"
              @change="onIntInput('numSerials', $event, true)"
            />
            <small v-if="errors.numSerials" class="d-block text-danger" style="font-size: 0.72rem">
              {{ errors.numSerials }}
            </small>
            <small v-else class="d-block text-muted" style="font-size: 0.72rem">
              Entre {{ BINGO_ROOM_INT_LIMITS.numSerials.min }} y {{ BINGO_ROOM_INT_LIMITS.numSerials.max }}.
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
  </div>
</template>
