<script setup lang="ts" name="PrizeForm">
  import { computed, ref, watch } from 'vue'

  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'
  import backendClient from '@/js/plugins/axios'

  import type { Prize, PrizeSearchResult } from '@/js/types/prizes/prize'

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
   * Kit switch
   * ========================================================================== */

  const kitToggle = computed({
    get: () => form.value.kit === 'Y',
    set: (val: boolean) => {
      form.value = {
        ...form.value,
        kit: val ? 'Y' : 'N',
        kit_items: val ? (form.value.kit_items ?? []) : [],
        num_items: val ? (form.value.kit_items?.length ?? 0) : 1,
      }
      if (!val) {
        searchQuery.value   = ''
        searchResults.value = []
      }
    }
  })

  const isKit    = computed(() => form.value.kit === 'Y')
  const kitItems = computed(() => form.value.kit_items ?? [])

  /* ==========================================================================
   * Búsqueda de prizes individuales para el kit
   * ========================================================================== */

  const searchQuery   = ref('')
  const searchResults = ref<PrizeSearchResult[]>([])
  const searching     = ref(false)
  let   debounceTimer: ReturnType<typeof setTimeout> | null = null

  watch(searchQuery, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer)
    if (!val.trim() || val.trim().length < 2) {
      searchResults.value = []
      return
    }
    debounceTimer = setTimeout(() => doSearch(val.trim()), 350)
  })

  async function doSearch(q: string) {
    searching.value = true
    try {
      const { data } = await backendClient.get<{ data: PrizeSearchResult[] }>('/prizes/search', {
        params: { q, exclude: form.value.id }
      })
      searchResults.value = (data.data ?? []).filter(
        r => !kitItems.value.includes(r.id)
      )
    } catch {
      searchResults.value = []
    } finally {
      searching.value = false
    }
  }

  function addKitItem(item: PrizeSearchResult) {
    const current = [...kitItems.value, item.id]
    form.value = { ...form.value, kit_items: current, num_items: current.length }
    searchQuery.value   = ''
    searchResults.value = []
  }

  function removeKitItem(id: string | number) {
    const current = kitItems.value.filter(i => i !== id)
    form.value = { ...form.value, kit_items: current, num_items: current.length }
  }

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

    if (form.value.kit === 'Y' && kitItems.value.length === 0) {
      e.kit_items = 'Agrega al menos un ítem al kit.'
    }

    return e
  })

  const isValid = computed(() => Object.keys(errors.value).length === 0)
  const isEdit  = computed(() => !!form.value.id)

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

      <!-- Kit switch + Núm. ítems readonly en la misma línea -->
      <div class="col-12 d-flex align-items-end gap-3 mb-2" style="padding-top: 0.25rem">
        <FormSwitch
          label="¿Es Kit?"
          name="kit_toggle"
          col=""
          :modelValue="kitToggle"
          @update:modelValue="kitToggle = $event"
        />

        <Transition name="slide-fade">
          <FormInput
            v-if="isKit"
            label="Núm. ítems"
            name="num_items"
            type="number"
            readonly
            col=""
            style="width: 110px"
            :modelValue="kitItems.length"
            @update:modelValue="() => {}"
          />
        </Transition>
      </div>

      <!-- Buscador + lista inline de items del kit -->
      <Transition name="slide-fade">
        <div v-if="isKit" class="col-12">
          <!-- Input de búsqueda -->
          <div class="kit-search-wrapper mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">
                <i
                  :class="searching ? 'fas fa-spinner fa-spin' : 'fas fa-search'"
                  style="width: 14px"
                ></i>
              </span>
              <input
                type="text"
                class="form-control"
                placeholder="Buscar premio por nombre o código..."
                v-model="searchQuery"
                autocomplete="off"
              />
            </div>

            <!-- Resultados del search -->
            <div v-if="searchResults.length > 0" class="kit-search-results">
              <button
                v-for="result in searchResults"
                :key="String(result.id)"
                type="button"
                class="kit-search-result-item"
                @click="addKitItem(result)"
              >
                <span class="result-code">{{ result.code }}</span>
                <span class="result-name">{{ result.name }}</span>
                <span class="result-price">${{ result.price }}</span>
                <i class="fas fa-plus result-add"></i>
              </button>
            </div>

            <small v-if="errors.kit_items" class="text-danger d-block mt-1">
              {{ errors.kit_items }}
            </small>
          </div>

          <!-- Lista inline de items añadidos -->
          <div v-if="kitItems.length > 0" class="kit-items-list">
            <TransitionGroup name="kit-item" tag="div" class="kit-items-inline">
              <span
                v-for="id in kitItems"
                :key="String(id)"
                class="kit-item-badge"
              >
                <span class="kit-item-id">{{ id }}</span>
                <button
                  type="button"
                  class="kit-item-remove"
                  @click="removeKitItem(id)"
                  title="Quitar"
                >
                  <i class="fas fa-times"></i>
                </button>
              </span>
            </TransitionGroup>
          </div>
        </div>
      </Transition>
    </div>
  </form>
</template>

<style scoped>
  /* ---- Search ---- */
  .kit-search-wrapper {
    position: relative;
    max-width: 480px;
  }

  .kit-search-results {
    position: absolute;
    top: calc(100% + 2px);
    left: 0;
    right: 0;
    z-index: 100;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    max-height: 220px;
    overflow-y: auto;
  }

  .kit-search-result-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.45rem 0.75rem;
    background: none;
    border: none;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    text-align: left;
    font-size: 0.825rem;
    transition: background 0.15s;
  }
  .kit-search-result-item:last-child { border-bottom: none; }
  .kit-search-result-item:hover      { background: #f0f7ff; }

  .result-code  { font-family: monospace; font-size: 0.75rem; color: #6c757d; background: #f1f3f5; padding: 1px 5px; border-radius: 3px; flex-shrink: 0; }
  .result-name  { flex: 1; font-weight: 500; color: #212529; }
  .result-price { color: #198754; font-size: 0.78rem; flex-shrink: 0; }
  .result-add   { color: #0d6efd; font-size: 0.7rem; flex-shrink: 0; }

  /* ---- Items inline ---- */
  .kit-items-list   { margin-top: 0.35rem; }
  .kit-items-inline { display: flex; flex-wrap: wrap; gap: 0.4rem; }

  .kit-item-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #e7f0fd;
    border: 1px solid #b6d0f7;
    border-radius: 1rem;
    padding: 0.2rem 0.5rem 0.2rem 0.65rem;
    font-size: 0.78rem;
    color: #1a56b0;
    white-space: nowrap;
  }

  .kit-item-id {
    font-family: monospace;
    font-size: 0.72rem;
    color: #4a7fc1;
  }

  .kit-item-remove {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: #6c757d;
    line-height: 1;
    font-size: 0.68rem;
    display: flex;
    align-items: center;
    transition: color 0.15s;
  }
  .kit-item-remove:hover { color: #dc3545; }

  /* ---- Animaciones ---- */
  .slide-fade-enter-active,
  .slide-fade-leave-active {
    transition: opacity 0.22s ease, transform 0.22s ease;
  }
  .slide-fade-enter-from,
  .slide-fade-leave-to {
    opacity: 0;
    transform: translateX(-8px);
  }

  .kit-item-enter-active,
  .kit-item-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
  }
  .kit-item-enter-from,
  .kit-item-leave-to {
    opacity: 0;
    transform: scale(0.8);
  }
</style>
