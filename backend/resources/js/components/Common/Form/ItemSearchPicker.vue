<script setup lang="ts" name="ItemSearchPicker" generic="T extends { id: string | number; name: string }">
  /**
   * ItemSearchPicker — Buscador + Panel de ítems seleccionados reutilizable.
   *
   * Props:
   *  - modelValue   : array de ítems seleccionados (T[])
   *  - search       : función async que recibe la query y retorna T[]
   *  - label        : etiqueta del buscador  (default "Agregar ítems")
   *  - placeholder  : placeholder del input  (default "Buscar...")
   *  - panelTitle   : título del panel       (default "Ítems seleccionados")
   *  - error        : mensaje de error externo
   *  - debounce     : ms de debounce         (default 350)
   *  - minChars     : mínimo de caracteres   (default 2)
   *
   * Slots:
   *  - result(item: T)  — fila del dropdown (default: muestra item.name)
   *  - chip(item: T)    — contenido del chip (default: muestra item.name)
   *  - panel-header-right — zona derecha del header del panel (ej: total $)
   *
   * Emits:
   *  - update:modelValue(items: T[])
   */

  import { ref, computed, watch } from 'vue'

  const props = withDefaults(defineProps<{
    modelValue:  T[]
    search:      (query: string) => Promise<T[]>
    label?:      string
    placeholder?: string
    panelTitle?: string
    error?:      string
    debounce?:   number
    minChars?:   number
  }>(), {
    label:       'Agregar ítems',
    placeholder: 'Buscar...',
    panelTitle:  'Ítems seleccionados',
    debounce:    350,
    minChars:    2,
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: T[]): void
  }>()

  /* ------------------------------------------------------------------ */
  /* Búsqueda con debounce                                               */
  /* ------------------------------------------------------------------ */

  const searchQuery   = ref('')
  const searchResults = ref<T[]>([])
  const searching     = ref(false)
  let   debounceTimer: ReturnType<typeof setTimeout> | null = null

  const selectedIds = computed(() => new Set(props.modelValue.map(i => String(i.id))))

  watch(searchQuery, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer)
    if (!val.trim() || val.trim().length < props.minChars) {
      searchResults.value = []
      return
    }
    debounceTimer = setTimeout(() => runSearch(val.trim()), props.debounce)
  })

  async function runSearch(q: string) {
    searching.value = true
    try {
      const results = await props.search(q)
      searchResults.value = results.filter(r => !selectedIds.value.has(String(r.id)))
    } catch {
      searchResults.value = []
    } finally {
      searching.value = false
    }
  }

  /* ------------------------------------------------------------------ */
  /* Agregar / quitar                                                    */
  /* ------------------------------------------------------------------ */

  function addItem(item: T) {
    emit('update:modelValue', [...props.modelValue, item])
    searchQuery.value   = ''
    searchResults.value = []
  }

  function removeItem(id: string | number) {
    emit('update:modelValue', props.modelValue.filter(i => String(i.id) !== String(id)))
  }

  /* exponer para que el padre pueda limpiar */
  defineExpose({ clear: () => { searchQuery.value = ''; searchResults.value = [] } })
</script>

<template>
  <!-- ================================================================
       Buscador
       ================================================================ -->
  <div class="isp-search-wrapper">
    <p class="isp-search-label">
      <slot name="label-icon"><i class="fas fa-layer-group"></i></slot>
      {{ label }}
    </p>

    <div class="input-group input-group-sm">
      <span class="input-group-text">
        <i :class="searching ? 'fas fa-spinner fa-spin' : 'fas fa-search'" style="width:14px"></i>
      </span>
      <input
        type="text"
        class="form-control"
        :placeholder="placeholder"
        v-model="searchQuery"
        autocomplete="off"
      />
    </div>

    <!-- Dropdown de resultados -->
    <Transition name="isp-dropdown">
      <div v-if="searchResults.length > 0" class="isp-results">
        <button
          v-for="result in searchResults"
          :key="String(result.id)"
          type="button"
          class="isp-result-item"
          @click="addItem(result)"
        >
          <slot name="result" :item="result">
            <span class="r-name">{{ result.name }}</span>
            <span class="r-add"><i class="fas fa-plus-circle"></i></span>
          </slot>
        </button>
      </div>
    </Transition>

    <small v-if="error" class="text-danger d-block mt-1">
      <i class="fas fa-exclamation-circle"></i> {{ error }}
    </small>
  </div>

  <!-- ================================================================
       Panel de ítems seleccionados
       ================================================================ -->
  <Transition name="isp-slide-fade">
    <div v-if="modelValue.length > 0" class="isp-panel">
      <div class="isp-panel-header">
        <span class="isp-panel-title">
          <slot name="panel-icon"><i class="fas fa-boxes"></i></slot>
          {{ panelTitle }}
        </span>
        <div class="isp-panel-header-right">
          <span class="isp-count">
            {{ modelValue.length }} ítem{{ modelValue.length !== 1 ? 's' : '' }}
          </span>
          <slot name="panel-header-right" />
        </div>
      </div>

      <TransitionGroup name="isp-chip" tag="div" class="isp-chips">
        <span
          v-for="item in modelValue"
          :key="String(item.id)"
          class="isp-chip"
        >
          <slot name="chip" :item="item" :remove="() => removeItem(item.id)">
            <i class="fas fa-tag chip-icon"></i>
            <span class="chip-name">{{ item.name }}</span>
            <button type="button" class="chip-remove" @click="removeItem(item.id)" title="Quitar">
              <i class="fas fa-times"></i>
            </button>
          </slot>
        </span>
      </TransitionGroup>
    </div>
  </Transition>
</template>

<style scoped>
/* ------------------------------------------------------------------ */
/* Buscador                                                            */
/* ------------------------------------------------------------------ */
.isp-search-wrapper {
  position: relative;
}

.isp-search-label {
  font-size: 0.775rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.3rem;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.isp-results {
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

.isp-result-item {
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
.isp-result-item:last-child { border-bottom: none; }
.isp-result-item:hover      { background: #eef4ff; }

/* slots de result — clases públicas para el consumidor */
:deep(.r-code)  { font-family: monospace; font-size: 0.72rem; background: #f1f3f5; color: #6c757d; padding: 1px 5px; border-radius: 3px; flex-shrink: 0; }
:deep(.r-name)  { flex: 1; font-weight: 500; color: #212529; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
:deep(.r-price) { color: #198754; font-size: 0.77rem; flex-shrink: 0; }
:deep(.r-add)   { color: #0d6efd; flex-shrink: 0; font-size: 0.85rem; }

/* ------------------------------------------------------------------ */
/* Panel                                                               */
/* ------------------------------------------------------------------ */
.isp-panel {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 0.3rem;
  padding: 0.45rem 0.7rem 0.55rem;
}

.isp-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.4rem;
}

.isp-panel-title {
  font-size: 0.775rem;
  font-weight: 600;
  color: #495057;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.isp-panel-header-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.isp-count {
  font-size: 0.7rem;
  font-weight: 700;
  background: #0d6efd;
  color: #fff;
  border-radius: 1rem;
  padding: 1px 8px;
  line-height: 1.7;
}

/* ------------------------------------------------------------------ */
/* Chips                                                               */
/* ------------------------------------------------------------------ */
.isp-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.isp-chip {
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

.chip-icon  { font-size: 0.58rem; color: #7aa3e0; flex-shrink: 0; }

.chip-name {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 500;
}

:deep(.chip-price) {
  font-size: 0.68rem;
  color: #198754;
  font-weight: 600;
  flex-shrink: 0;
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

/* ------------------------------------------------------------------ */
/* Transiciones                                                        */
/* ------------------------------------------------------------------ */
.isp-dropdown-enter-active,
.isp-dropdown-leave-active {
  transition: opacity 0.13s ease, transform 0.13s ease;
}
.isp-dropdown-enter-from,
.isp-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.isp-slide-fade-enter-active,
.isp-slide-fade-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.isp-slide-fade-enter-from,
.isp-slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.isp-chip-enter-active,
.isp-chip-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.isp-chip-enter-from,
.isp-chip-leave-to {
  opacity: 0;
  transform: scale(0.8);
}
</style>
