<script setup lang="ts">
  import { computed } from 'vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import { updateCardPriceForCurrency, getCurrencyLabel } from '../Composables/useRoomCurrency'
  import { showToast } from '@/js/utils/toastNotify'
  import { sanitizeNumericInput } from '../Utils/numericInput'
  import { BINGO_ROOM_AMOUNT_LIMITS } from '@/js/composables/games/bingo/room/shared/bingoRoomFormSchema'
  import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
  import type { SelectOption } from '@/js/types/common/form/form'

  const props = defineProps<{
    modelValue: BingoRoomFormData
    errors: Record<string, any>
    allowedCurrenciesOptions: SelectOption[]
    useBaseUsd: boolean
    canUseBaseUsd?: boolean
    canAdjustCardPriceByCurrency: boolean
    currenciesWithActiveRate?: string[]
    /** IDs de monedas con cartones ya vendidos — no se pueden quitar de "Monedas Permitidas". */
    soldCurrencyIds?: string[]
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: BingoRoomFormData): void
    (e: 'update:useBaseUsd', value: boolean): void
  }>()

  /**
   * Intercepta el cambio de "Monedas Permitidas": si el usuario intenta quitar
   * una moneda que ya tiene cartones vendidos, se revierte y se avisa — el
   * servidor también lo rechazaría, pero avisar aquí evita perder el resto de
   * los cambios del formulario al guardar.
   */
  function onAllowedCurrenciesChange(value: (string | number)[] | null): void {
    const sold = props.soldCurrencyIds ?? []
    const next = (value ?? []).map(String)
    const removedSold = sold.filter(id => !next.includes(id))

    if (removedSold.length > 0) {
      const labels = removedSold.map(id => label(id)).join(', ')
      showToast(`No puedes quitar ${labels}: ya tiene cartones vendidos en esta sala.`, 'error')
      return
    }

    emit('update:modelValue', { ...props.modelValue, allowedCurrencies: value as any })
  }

  function onCurrencyPriceChange(currency: string | number, value: string | number | null) {
    const updated = updateCardPriceForCurrency(props.modelValue.cardPriceByCurrency, currency, value)
    emit('update:modelValue', { ...props.modelValue, cardPriceByCurrency: updated })
  }

  const A = BINGO_ROOM_AMOUNT_LIMITS

  function rangeHint(lim: { min: number; max: number }) {
    return `Entre ${lim.min.toLocaleString('es-VE')} y ${lim.max.toLocaleString('es-VE')}.`
  }

  function onAmountInput(field: 'cardPriceUsd' | 'usdExchangeRoundStep', event: Event, commit = false) {
    const value = sanitizeNumericInput(event, A[field], commit)
    emit('update:modelValue', { ...props.modelValue, [field]: value })
  }

  function label(id: string | number) {
    return getCurrencyLabel(id, props.allowedCurrenciesOptions)
  }

  /**
   * Si hay 2+ monedas permitidas, se puede elegir entre crear una sala única
   * (multi-moneda) o una sala independiente por cada moneda.
   */
  const canChooseRoomMode = computed(() => (props.modelValue.allowedCurrencies?.length ?? 0) > 1)

  /**
   * Monedas permitidas que no cuentan con una tasa de cambio activa.
   * Sin esta tasa, el precio base en USD no puede convertirse a esas monedas.
   */
  const currenciesWithoutActiveRate = computed(() => {
    const activeRates = props.currenciesWithActiveRate ?? []
    return (props.modelValue.allowedCurrencies ?? []).filter(
      cur => !activeRates.includes(String(cur))
    )
  })

  const currenciesWithoutActiveRateLabel = computed(() =>
    currenciesWithoutActiveRate.value.map(cur => label(cur)).join(', ')
  )
</script>

<template>
  <div style="display: contents">
  <!-- Monedas permitidas -->
  <FormSelect
    label="Monedas Permitidas"
    name="allowed_currencies"
    :error="errors.allowedCurrencies"
    container
    required
    multiple
    placeholder="Seleccionar Monedas"
    :options="allowedCurrenciesOptions"
    optionValue="value"
    optionLabel="label"
    col="col-sm-6"
    :modelValue="modelValue.allowedCurrencies"
    @update:modelValue="onAllowedCurrenciesChange"
  />

  <!-- Switch: Sala única / Salas por moneda -->
  <div class="col-12" v-show="canChooseRoomMode" style="margin-bottom: 1rem">
    <div class="d-flex align-items-center" style="gap: 0.5rem">
      <div class="custom-control custom-switch" style="padding-left: 2.1rem">
        <input
          type="checkbox"
          class="custom-control-input"
          id="roomsPerCurrencySwitch"
          :checked="modelValue.roomsPerCurrency ?? false"
          @change="emit('update:modelValue', { ...modelValue, roomsPerCurrency: ($event.target as HTMLInputElement).checked })"
        />
        <label class="custom-control-label" for="roomsPerCurrencySwitch" style="cursor: pointer">
          Salas por moneda
        </label>
      </div>
    </div>
    <small class="text-muted d-block" style="font-size: 0.78rem">
      Se creará una sala independiente por cada moneda seleccionada.
    </small>
  </div>

  <!-- Panel de precios de cartones — siempre visible en cuanto hay al menos
       1 moneda seleccionada (canAdjustCardPriceByCurrency), ya no depende de
       un botón ⚙️ que lo mantenía oculto pese a ser obligatorio completarlo. -->
  <div class="col-12" v-show="canAdjustCardPriceByCurrency" style="margin-bottom: 1rem">
    <div style="border: 1px solid var(--ds-border); border-radius: 0.3rem; padding: 0.6rem 0.85rem">
      <div class="d-flex align-items-center justify-content-between mb-1">
        <div class="d-flex align-items-center" style="gap: 0.4rem">
          <i class="fa-solid fa-coins" style="font-size: 0.75rem; color: var(--ds-text-2)"></i>
          <span style="font-size: 0.8rem; font-weight: 600; color: var(--ds-text)">Precio de cartones</span>
          <span style="font-size: 0.75rem; color: var(--ds-text-2)">— Base USD + ajuste por moneda</span>
        </div>
        <div class="d-flex align-items-center" style="gap: 0.75rem">
          <div class="d-flex align-items-center" style="gap: 0.35rem" :title="canUseBaseUsd === false ? 'No disponible: hay monedas seleccionadas sin tasa de cambio activa' : undefined">
            <span style="font-size: 0.74rem; color: var(--ds-text-2); white-space: nowrap">Usar Base USD</span>
            <div class="custom-control custom-switch" style="padding-left: 2.1rem; margin-bottom: 0">
              <input
                type="checkbox"
                class="custom-control-input"
                id="useBaseUsdSwitch"
                :checked="useBaseUsd"
                :disabled="canUseBaseUsd === false"
                @change="emit('update:useBaseUsd', ($event.target as HTMLInputElement).checked)"
              />
              <label class="custom-control-label" for="useBaseUsdSwitch" style="cursor: pointer" />
            </div>
          </div>
        </div>
      </div>

      <div style="border-top: 1px solid #e9ecef; margin: 0.4rem 0 0.55rem" />

      <div
        v-if="currenciesWithoutActiveRate.length > 0"
        class="d-flex align-items-start"
        style="gap: 0.35rem; font-size: 0.74rem; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; border-radius: 0.25rem; padding: 0.35rem 0.5rem; margin-bottom: 0.5rem"
      >
        <i class="fa-solid fa-triangle-exclamation" style="margin-top: 0.1rem"></i>
        <span v-if="useBaseUsd">
          No hay tasa de cambio activa para: <strong>{{ currenciesWithoutActiveRateLabel }}</strong>.
          El precio en USD no podrá convertirse correctamente para esa(s) moneda(s).
        </span>
        <span v-else>
          No hay tasa de cambio activa para: <strong>{{ currenciesWithoutActiveRateLabel }}</strong>.
          Aunque el precio se ingrese manualmente, se necesita la tasa para registrar el equivalente en USD.
        </span>
      </div>

      <div class="d-flex align-items-start flex-wrap" style="gap: 0.5rem">
        <template v-if="useBaseUsd">
          <div>
            <div class="input-group input-group-sm" style="width: 130px">
              <div class="input-group-prepend">
                <span class="input-group-text" title="Precio base en USD">USD</span>
              </div>
              <input
                name="card_price_usd"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                :class="['form-control', { 'is-invalid': errors.cardPriceUsd }]"
                placeholder="0.00"
                :value="modelValue.cardPriceUsd ?? ''"
                @input="onAmountInput('cardPriceUsd', $event)"
                @change="onAmountInput('cardPriceUsd', $event, true)"
              />
            </div>
            <small v-if="errors.cardPriceUsd" class="d-block text-danger" style="font-size: 0.72rem">{{ errors.cardPriceUsd }}</small>
            <small v-else class="d-block text-muted" style="font-size: 0.72rem">{{ rangeHint(A.cardPriceUsd) }}</small>
          </div>

          <div style="color: #ced4da; font-size: 0.85rem; margin-top: 0.42rem">·</div>

          <div>
            <div class="input-group input-group-sm" style="width: 155px">
              <div class="input-group-prepend">
                <span class="input-group-text" title="Incremento de redondeo para la tasa de cambio">
                  <i class="fa-solid fa-rotate" style="font-size: 0.7rem"></i>
                </span>
              </div>
              <input
                name="usd_exchange_round_step"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                :class="['form-control', { 'is-invalid': errors.usdExchangeRoundStep }]"
                placeholder="Redondeo opc."
                :value="modelValue.usdExchangeRoundStep ?? ''"
                @input="onAmountInput('usdExchangeRoundStep', $event)"
                @change="onAmountInput('usdExchangeRoundStep', $event, true)"
              />
            </div>
            <small v-if="errors.usdExchangeRoundStep" class="d-block text-danger" style="font-size: 0.72rem">{{ errors.usdExchangeRoundStep }}</small>
            <small v-else class="d-block text-muted" style="font-size: 0.72rem">Opcional · {{ rangeHint(A.usdExchangeRoundStep) }}</small>
          </div>
        </template>

        <template v-if="!useBaseUsd && canAdjustCardPriceByCurrency">
          <template v-for="cur in modelValue.allowedCurrencies ?? []" :key="String(cur)">
            <div>
              <div class="input-group input-group-sm" style="width: 130px">
                <div class="input-group-prepend">
                  <span
                    class="input-group-text"
                    :class="{ 'border-danger text-danger': errors.cardPriceByCurrency?.[String(cur)] }"
                  >{{ label(cur) }}</span>
                </div>
                <input
                  :name="`card_price_${String(cur)}`"
                  type="text"
                  inputmode="decimal"
                  autocomplete="off"
                  :class="['form-control', { 'is-invalid': errors.cardPriceByCurrency?.[String(cur)] }]"
                  placeholder="0.00"
                  :value="modelValue.cardPriceByCurrency?.[String(cur)] ?? ''"
                  @input="onCurrencyPriceChange(cur, sanitizeNumericInput($event, A.cardPriceByCurrency))"
                  @change="onCurrencyPriceChange(cur, sanitizeNumericInput($event, A.cardPriceByCurrency, true))"
                />
              </div>
              <small v-if="errors.cardPriceByCurrency?.[String(cur)]" class="d-block text-danger" style="font-size: 0.72rem; max-width: 130px">
                {{ errors.cardPriceByCurrency[String(cur)] }}
              </small>
            </div>
          </template>
        </template>
      </div>
    </div>
  </div>
  </div>
</template>
