<script setup lang="ts" name="PrizeForm">
  import { computed, watch } from 'vue'

  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  import type { Prize } from '@/js/types/prizes/prize'

  /* ==========================================================================
   * Props / Emits
   * ========================================================================== */

  const props = defineProps<{
    modelValue: Partial<Prize>
  }>()

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<Prize>): void
  }>()

  /* ==========================================================================
   * v-model computado
   * ========================================================================== */

  const form = computed<Partial<Prize>>({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v)
  })

  /* ==========================================================================
   * Opciones estáticas
   * ========================================================================== */

  const rewardTypeOptions = [
    { value: 'PRODUCT', label: 'Producto' },
    { value: 'SERVICE', label: 'Servicio' }
  ]

  /* ==========================================================================
   * Lógica kit: si se desactiva, resetear num_items a 1
   * ========================================================================== */

  const kitToggle = computed({
    get: () => form.value.kit === 'Y',
    set: (val: boolean) => {
      form.value = { ...form.value, kit: val ? 'Y' : 'N', num_items: val ? (form.value.num_items ?? 1) : 1 }
    }
  })

  watch(
    () => form.value.kit,
    val => {
      if (val !== 'Y') {
        form.value = { ...form.value, num_items: 1 }
      }
    }
  )

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

    if (!form.value.code) {
      e.code = 'El código es requerido.'
    } else if (!/^[a-zA-Z0-9]{1,12}$/.test(form.value.code)) {
      e.code = 'Máx 12 caracteres alfanuméricos.'
    }

    if (!form.value.reward_type) {
      e.reward_type = 'El tipo es requerido.'
    }

    if (!form.value.name?.trim()) {
      e.name = 'El nombre es requerido.'
    }

    const price = parseFloat(String(form.value.price ?? ''))
    if (form.value.price === '' || form.value.price === undefined || form.value.price === null) {
      e.price = 'El precio es requerido.'
    } else if (isNaN(price) || price < 0) {
      e.price = 'Precio inválido.'
    }

    if (form.value.kit === 'Y') {
      const items = Number(form.value.num_items)
      if (!items || items < 1 || !Number.isInteger(items)) {
        e.num_items = 'Mínimo 1 ítem.'
      }
    }

    return e
  })

  const isValid = computed(() => Object.keys(errors.value).length === 0)
  const isKit = computed(() => form.value.kit === 'Y')
  const isEdit = computed(() => !!form.value.id)

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

      <FormInput
        label="Código"
        name="code"
        required
        maxlength="12"
        placeholder="Ej: PRIZE001"
        col="col-sm-6"
        :error="errors.code"
        :readonly="isEdit"
        :class="{ 'opacity-75': isEdit }"
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
        col="col-sm-6"
        :error="errors.reward_type"
        v-model="form.reward_type"
      />

      <!-- Kit toggle + num_items en la misma fila -->
      <div class="col-12">
        <div class="kit-row">
          <!-- Toggle switch -->
          <div class="kit-toggle-wrapper">
            <label class="form-label kit-label" for="kit-toggle">¿Es Kit?</label>
            <div class="form-check form-switch mb-0">
              <input
                id="kit-toggle"
                class="form-check-input kit-switch"
                type="checkbox"
                role="switch"
                :checked="kitToggle"
                @change="kitToggle = ($event.target as HTMLInputElement).checked"
              />
              <label class="form-check-label kit-switch-label" for="kit-toggle">
                <span :class="kitToggle ? 'text-primary fw-semibold' : 'text-muted'">
                  {{ kitToggle ? 'Sí' : 'No' }}
                </span>
              </label>
            </div>
          </div>

          <!-- Num items (aparece con animación) -->
          <Transition name="slide-fade">
            <div v-if="isKit" class="kit-items-wrapper">
              <FormInput
                label="Núm. ítems"
                name="num_items"
                type="number"
                required
                placeholder="1"
                :min="1"
                :max="127"
                col=""
                :error="errors.num_items"
                v-model="form.num_items"
              />
            </div>
          </Transition>

          <!-- Info hint -->
          <Transition name="slide-fade">
            <div v-if="isKit" class="kit-hint">
              <i class="fas fa-layer-group me-1"></i>
              Agrupa múltiples ítems como un solo premio.
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </form>
</template>

<style scoped>
  /* Kit row layout */
  .kit-row {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex-wrap: wrap;
    padding: 0.5rem 0 1rem;
  }

  .kit-toggle-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .kit-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--bs-secondary-color, #6c757d);
    margin-bottom: 0;
    white-space: nowrap;
  }

  .kit-switch {
    width: 2.5em;
    height: 1.35em;
    cursor: pointer;
  }

  .kit-switch-label {
    padding-left: 0.4rem;
    font-size: 0.875rem;
  }

  .kit-items-wrapper {
    width: 130px;
    flex-shrink: 0;
  }

  .kit-hint {
    font-size: 0.78rem;
    color: var(--bs-secondary-color, #6c757d);
    display: flex;
    align-items: center;
    padding-top: 1.6rem; /* alinea con el input */
  }

  /* Animación slide-fade */
  .slide-fade-enter-active,
  .slide-fade-leave-active {
    transition:
      opacity 0.22s ease,
      transform 0.22s ease;
  }
  .slide-fade-enter-from,
  .slide-fade-leave-to {
    opacity: 0;
    transform: translateX(-8px);
  }
</style>
