<script setup lang="ts" name="PrizeForm">
  import { computed, ref, watch } from 'vue'

  import FormInput  from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'
  import backendClient from '@/js/plugins/axios'

  import type { Prize, PrizeSearchResult } from '@/js/types/prizes/prize'

  /* ==========================================================================
   * Props / Emits
   * ========================================================================== */

  const props = defineProps<{
    modelValue:    Partial<Prize>
    canCreateKit?: boolean
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
        kit:       val ? 'Y' : 'N',
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
    mask: Number, scale: 2, min: 0, max: 9999999999,
    radix: '.', mapToRadix: [','], padFractionalZeros: true,
    normalizeZeros: true, thousandsSeparator: ','
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

    if (!form.value.reward_type) e.reward_type = 'El tipo es requerido.'
    if (!form.value.name?.trim()) e.name = 'El nombre es requerido.'

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

      <!-- ------------------------------------------------------------------ -->
      <!-- Sección Kit                                                         -->
      <!-- ------------------------------------------------------------------ -->
      <div class="col-12">
        <div class="kit-section">

          <!-- Switch + hint -->
          <div class="kit-switch-row">
            <FormSwitch
              label="¿Es Kit?"
              name="kit_toggle"
              col=""
              :modelValue="kitToggle"
              :disabled="!canCreateKit && !isEdit"
              @update:modelValue="kitToggle = $event"
            />
            <span v-if="!canCreateKit && !isEdit" class="kit-unavailable-hint">
              <i class="fas fa-info-circle"></i>
              Necesitas al menos 2 premios individuales para crear un kit.
            </span>
          </div>

          <!-- Cuerpo del kit (buscador + ítems) -->
          <Transition name="kit-expand">
            <div v-if="isKit" class="kit-body">

              <!-- Buscador -->
              <div class="kit-search-wrapper">
                <p class="kit-search-label">
                  <i class="fas fa-layer-group"></i> Agregar ítems al kit
                </p>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">
                    <i :class="searching ? 'fas fa-spinner fa-spin' : 'fas fa-search'" style="width:14px"></i>
                  </span>
                  <input
                    type="text"
                    class="form-control"
                    placeholder="Buscar por nombre o código..."
                    v-model="searchQuery"
                    autocomplete="off"
                  />
                </div>

                <!-- Dropdown de resultados -->
                <Transition name="dropdown">
                  <div v-if="searchResults.length > 0" class="kit-search-results">
                    <button
                      v-for="result in searchResults"
                      :key="String(result.id)"
                      type="button"
                      class="kit-result-item"
                      @click="addKitItem(result)"
                    >
                      <span class="r-code">{{ result.code }}</span>
                      <span class="r-name">{{ result.name }}</span>
                      <span class="r-price">${{ result.price }}</span>
                      <span class="r-add"><i class="fas fa-plus-circle"></i></span>
                    </button>
                  </div>
                </Transition>

                <small v-if="errors.kit_items" class="text-danger d-block mt-1">
                  <i class="fas fa-exclamation-circle"></i> {{ errors.kit_items }}
                </small>
              </div>

              <!-- Panel de ítems seleccionados (solo si hay alguno) -->
              <Transition name="slide-fade">
                <div v-if="kitItems.length > 0" class="kit-items-panel">
                  <div class="kit-items-header">
                    <span class="kit-items-title">
                      <i class="fas fa-boxes"></i> Ítems del kit
                    </span>
                    <span class="kit-items-count">
                      {{ kitItems.length }} ítem{{ kitItems.length !== 1 ? 's' : '' }}
                    </span>
                  </div>
                  <TransitionGroup name="kit-chip" tag="div" class="kit-chips">
                    <span
                      v-for="id in kitItems"
                      :key="String(id)"
                      class="kit-chip"
                    >
                      <i class="fas fa-tag chip-icon"></i>
                      <span class="chip-id">{{ id }}</span>
                      <button
                        type="button"
                        class="chip-remove"
                        @click="removeKitItem(id)"
                        title="Quitar del kit"
                      >
                        <i class="fas fa-times"></i>
                      </button>
                    </span>
                  </TransitionGroup>
                </div>
              </Transition>

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
  flex-wrap: wrap;
}

.kit-unavailable-hint {
  font-size: 0.775rem;
  color: #6c757d;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

/* ============================================================
   Kit body
   ============================================================ */
.kit-body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* ---- Search ---- */
.kit-search-wrapper {
  position: relative;
}

.kit-search-label {
  font-size: 0.775rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.3rem;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.kit-search-results {
  position: absolute;
  top: calc(100% + 2px);
  left: 0;
  right: 0;
  z-index: 200;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 0.3rem;
  box-shadow: 0 6px 16px rgba(0,0,0,0.1);
  max-height: 200px;
  overflow-y: auto;
}

.kit-result-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.38rem 0.7rem;
  background: none;
  border: none;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  text-align: left;
  font-size: 0.815rem;
  transition: background 0.12s;
}
.kit-result-item:last-child { border-bottom: none; }
.kit-result-item:hover      { background: #eef4ff; }

.r-code  { font-family: monospace; font-size: 0.72rem; background: #f1f3f5; color: #6c757d; padding: 1px 5px; border-radius: 3px; flex-shrink: 0; }
.r-name  { flex: 1; font-weight: 500; color: #212529; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.r-price { color: #198754; font-size: 0.77rem; flex-shrink: 0; }
.r-add   { color: #0d6efd; flex-shrink: 0; font-size: 0.85rem; }

/* ---- Items panel ---- */
.kit-items-panel {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 0.3rem;
  padding: 0.45rem 0.7rem 0.55rem;
}

.kit-items-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.4rem;
}

.kit-items-title {
  font-size: 0.775rem;
  font-weight: 600;
  color: #495057;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.kit-items-count {
  font-size: 0.7rem;
  font-weight: 700;
  background: #0d6efd;
  color: #fff;
  border-radius: 1rem;
  padding: 1px 8px;
  line-height: 1.7;
}

/* ---- Chips ---- */
.kit-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.kit-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  background: #e8f0fe;
  border: 1px solid #c5d8f9;
  border-radius: 1rem;
  padding: 0.18rem 0.45rem 0.18rem 0.55rem;
  font-size: 0.775rem;
  color: #1a56b0;
  white-space: nowrap;
  max-width: 220px;
}

.chip-icon { font-size: 0.58rem; color: #7aa3e0; flex-shrink: 0; }

.chip-id {
  font-family: monospace;
  font-size: 0.7rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chip-remove {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: #9db8e0;
  font-size: 0.6rem;
  line-height: 1;
  display: flex;
  align-items: center;
  flex-shrink: 0;
  transition: color 0.15s;
}
.chip-remove:hover { color: #dc3545; }

/* ============================================================
   Transiciones
   ============================================================ */
.kit-expand-enter-active,
.kit-expand-leave-active {
  transition: opacity 0.22s ease, max-height 0.25s ease;
  overflow: hidden;
  max-height: 600px;
}
.kit-expand-enter-from,
.kit-expand-leave-to {
  opacity: 0;
  max-height: 0;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.13s ease, transform 0.13s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.kit-chip-enter-active,
.kit-chip-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.kit-chip-enter-from,
.kit-chip-leave-to {
  opacity: 0;
  transform: scale(0.8);
}
</style>
