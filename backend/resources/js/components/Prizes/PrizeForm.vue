<script setup lang="ts" name="PrizeForm">
  import { computed, ref, watch } from 'vue'

  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'
  import PrizeKitPicker from './PrizeKitPicker.vue'

  import type { Prize, PrizeSearchResult } from '@/js/types/prizes/prize'

  /* ref al picker para poder llamar .clear() */
  const pickerRef = ref<{ clear: () => void } | null>(null)

  /* ==========================================================================
   * Props / Emits
   * ========================================================================== */

  const props = defineProps<{
    modelValue: Partial<Prize>
    kitItemObjects: PrizeSearchResult[]
    canCreateKit?: boolean
  }>()

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<Prize>): void
    (e: 'update:kitItemObjects', value: PrizeSearchResult[]): void
  }>()

  /* ==========================================================================
   * v-model computado
   * ========================================================================== */

  const form = computed<Partial<Prize>>({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v)
  })

  /* ==========================================================================
   * Kit items (objetos completos con precio, sincronizados con el padre)
   * ========================================================================== */

  const localKitItems = computed<PrizeSearchResult[]>({
    get: () => props.kitItemObjects,
    set: v => emit('update:kitItemObjects', v)
  })

  /* ==========================================================================
   * Opciones estáticas
   * ========================================================================== */

  const rewardTypeOptions = computed(() => [
    { value: 'PRODUCT', label: 'Producto' },
    { value: 'SERVICE', label: 'Servicio' },
    { value: 'MIXED', label: 'Mixta', disabled: !props.canCreateKit }
  ])

  // Si canCreateKit pasa a false estando MIXED seleccionado, limpiar la selección
  watch(
    () => props.canCreateKit,
    canCreate => {
      if (!canCreate && form.value.reward_type === 'MIXED') {
        form.value = { ...form.value, reward_type: undefined }
      }
    }
  )

  /* ==========================================================================
   * Helpers
   * ========================================================================== */

  const isEdit = computed(() => !!form.value.id)
  const isMixed = computed(() => form.value.reward_type === 'MIXED')
  const isKit = computed(() => form.value.kit === 'Y' || isMixed.value)
  const kitIds = computed(() => localKitItems.value.map(i => String(i.id)))

  // Cuando se selecciona MIXED, activar kit automáticamente
  watch(isMixed, val => {
    if (val && form.value.kit !== 'Y') {
      form.value = {
        ...form.value,
        kit: 'Y',
        kit_items: kitIds.value,
        num_items: localKitItems.value.length,
        price: kitPrice.value.toFixed(2)
      }
    }
  })

  /* ==========================================================================
   * Kit price auto-sum
   * ========================================================================== */

  const kitPrice = computed(() =>
    localKitItems.value.reduce((sum, item) => sum + Number(item.price ?? 0), 0)
  )

  // Sincroniza kit_items (IDs con repetidos) y num_items cuando cambia el picker
  watch(
    localKitItems,
    items => {
      if (isKit.value) {
        form.value = {
          ...form.value,
          kit_items: items.map(i => String(i.id)),
          num_items: items.length,
          price: kitPrice.value.toFixed(2)
        }
      }
    },
    { deep: true }
  )

  watch(kitPrice, val => {
    if (isKit.value) {
      form.value = { ...form.value, price: val.toFixed(2) }
    }
  })

  /* ==========================================================================
   * Kit switch
   * ========================================================================== */

  const kitToggle = computed({
    get: () => form.value.kit === 'Y',
    set: (val: boolean) => {
      const newItems: PrizeSearchResult[] = val ? localKitItems.value : []
      localKitItems.value = newItems
      form.value = {
        ...form.value,
        kit: val ? 'Y' : 'N',
        kit_items: val ? kitIds.value : [],
        num_items: val ? newItems.length : 1,
        price: val ? kitPrice.value.toFixed(2) : ''
      }
      if (!val) pickerRef.value?.clear()
    }
  })

  /* ==========================================================================
   * Máscara precio
   * ========================================================================== */

  const priceMask = {
    mask: Number,
    scale: 2,
    min: 0,
    max: 9999999999,
    radix: '.',
    mapToRadix: [','],
    padFractionalZeros: true,
    normalizeZeros: true,
    thousandsSeparator: ','
  }

  /* ==========================================================================
   * Validación reactiva
   * ========================================================================== */

  const errors = computed<Record<string, string>>(() => {
    const e: Record<string, string> = {}

    if (!form.value.reward_type) e.reward_type = 'El tipo es requerido.'
    if (!form.value.name?.trim()) e.name = 'El nombre es requerido.'

    if (!isKit.value) {
      const price = parseFloat(String(form.value.price ?? ''))
      if (form.value.price === '' || form.value.price === undefined || form.value.price === null) {
        e.price = 'El precio es requerido.'
      } else if (isNaN(price) || price < 0) {
        e.price = 'Precio inválido.'
      }
    }

    if (form.value.kit === 'Y' && localKitItems.value.length < 2) {
      e.kit_items = 'Agrega al menos 2 ítems al kit.'
    }

    return e
  })

  const isValid = computed(() => Object.keys(errors.value).length === 0)

  /* ==========================================================================
   * Submit / Expose
   * ========================================================================== */

  const submit = (): boolean => {
    if (!isValid.value) return false
    emit('submit')
    return true
  }

  defineExpose({ submit, isValid, reset: () => {} })
</script>

<template>
  <form id="prize-form" novalidate @submit.prevent="submit">
    <div class="row">
      <FormInput
        label="Nombre del premio"
        name="name"
        required
        maxlength="60"
        placeholder="Ej: Smartphone Premium"
        col="col-sm-8"
        :error="errors.name"
        v-model="form.name"
      />

      <FormInput
        label="Precio"
        name="price"
        required
        inputGroup
        col="col-sm-4"
        :mask="priceMask"
        :error="errors.price"
        :readonly="isKit"
        :class="{ 'opacity-75': isKit }"
        v-model="form.price"
      >
        <template #prepend>
          <span class="input-group-text">$</span>
        </template>
      </FormInput>

      <FormInput
        label="Descripción"
        name="description"
        maxlength="255"
        placeholder="Descripción opcional del premio..."
        col="col-sm-12"
        v-model="form.description"
      />

      <!-- Código: solo visible al editar (readonly, generado por backend) -->
      <FormInput
        v-if="isEdit"
        label="Código"
        name="code"
        maxlength="12"
        col="col-sm-6"
        readonly
        class="opacity-75"
        v-model="form.code"
      />

      <FormSelect
        label="Tipo de recompensa"
        name="reward_type"
        required
        placeholder="Seleccionar..."
        :options="rewardTypeOptions"
        optionValue="value"
        optionLabel="label"
        :col="isEdit ? 'col-sm-6' : 'col-sm-12'"
        :error="errors.reward_type"
        v-model="form.reward_type"
      />
      <div v-if="!canCreateKit && !isEdit" class="col-12 mt-n2 mb-1">
        <small class="text-muted">
          <i class="fas fa-lock me-1"></i>
          La opción <strong>Mixta</strong> requiere al menos 2 premios individuales.
        </small>
      </div>

      <!-- ------------------------------------------------------------------ -->
      <!-- Sección Kit                                                         -->
      <!-- ------------------------------------------------------------------ -->
      <div class="col-12" v-if="!isEdit">
        <div class="kit-section">
          <!-- Switch + hint -->
          <div class="kit-switch-row">
            <FormSwitch
              label="¿Es Kit?"
              name="kit_toggle"
              col=""
              class="mb-0"
              :modelValue="kitToggle"
              :disabled="!canCreateKit || isMixed"
              @update:modelValue="kitToggle = $event"
            />
            <span v-if="isMixed" class="kit-unavailable-hint kit-mixed-hint">
              <i class="fas fa-info-circle"></i>
              Recompensa Mixta requiere kit obligatoriamente.
            </span>
            <span v-else-if="!canCreateKit" class="kit-unavailable-hint">
              <i class="fas fa-info-circle"></i>
              Necesitas al menos 2 premios individuales para crear un kit.
            </span>
          </div>

          <!-- Cuerpo del kit (buscador + ítems) -->
          <Transition name="kit-expand">
            <div v-if="isKit" class="kit-body">
              <PrizeKitPicker
                v-model="localKitItems"
                :excludeId="form.id"
                :rewardType="form.reward_type"
                :error="errors.kit_items"
              />
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </form>
</template>

<style scoped>
  /* ============================================================
   Kit section container
   ============================================================ */
  .kit-section {
    border: 1px solid #e9ecef;
    border-radius: 0.4rem;
    padding: 0.65rem 1rem 0.6rem;
    margin-top: 0.25rem;
    background: #fafbfc;
  }

  .kit-switch-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .kit-unavailable-hint {
    font-size: 0.775rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 0.3rem;
  }

  .kit-mixed-hint {
    color: #0d6efd;
  }

  /* ============================================================
   Kit body
   ============================================================ */
  .kit-body {
    margin-top: 0.65rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
  }

  /* slot panel-header-right: total del kit */
  .kit-items-total {
    font-size: 0.72rem;
    color: #198754;
    font-weight: 600;
  }

  /* ============================================================
   Transición del cuerpo del kit (expand)
   ============================================================ */
  .kit-expand-enter-active,
  .kit-expand-leave-active {
    transition:
      opacity 0.22s ease,
      max-height 0.25s ease;
    overflow: hidden;
    max-height: 600px;
  }
  .kit-expand-enter-from,
  .kit-expand-leave-to {
    opacity: 0;
    max-height: 0;
  }
</style>
