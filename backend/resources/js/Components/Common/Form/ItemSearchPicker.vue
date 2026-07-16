<script
  setup
  lang="ts"
  name="ItemSearchPicker"
  generic="T extends { id: string | number; name: string }"
>
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
   *  - chip(item: T, qty: number, remove: fn, increment: fn, decrement: fn) — chip con cantidad
   *  - panel-header-right — zona derecha del header del panel (ej: total $)
   *
   * Emits:
   *  - update:modelValue(items: T[])
   */

  import { ref, computed, watch } from 'vue'

  const props = withDefaults(
    defineProps<{
      modelValue: T[]
      search: (query: string) => Promise<T[]>
      label?: string
      placeholder?: string
      panelTitle?: string
      error?: string
      debounce?: number
      minChars?: number
      chipLayout?: 'wrap' | 'list'
    }>(),
    {
      label: 'Agregar ítems',
      placeholder: 'Buscar...',
      panelTitle: 'Ítems seleccionados',
      debounce: 350,
      minChars: 2,
      chipLayout: 'wrap'
    }
  )

  const emit = defineEmits<{
    (e: 'update:modelValue', value: T[]): void
  }>()

  /* ------------------------------------------------------------------ */
  /* Búsqueda con debounce                                               */
  /* ------------------------------------------------------------------ */

  const searchQuery = ref('')
  const searchResults = ref<T[]>([])
  const searching = ref(false)
  const isFocused = ref(false)
  let debounceTimer: ReturnType<typeof setTimeout> | null = null
  let lastQuery = ''

  watch(searchQuery, val => {
    const trimmed = val.trim()
    if (debounceTimer) clearTimeout(debounceTimer)
    if (!trimmed || trimmed.length < props.minChars) {
      searchResults.value = []
      lastQuery = ''
      return
    }
    // Solo consulta si el query cambió
    if (trimmed === lastQuery) return
    debounceTimer = setTimeout(() => {
      lastQuery = trimmed
      runSearch(trimmed)
    }, props.debounce)
  })

  async function runSearch(q: string) {
    searching.value = true
    try {
      const results = await props.search(q)
      searchResults.value = results
    } catch {
      searchResults.value = []
    } finally {
      searching.value = false
    }
  }

  function onFocus() {
    isFocused.value = true
  }

  function onBlur() {
    // Delay para permitir clicks en el dropdown
    setTimeout(() => {
      isFocused.value = false
    }, 180)
  }

  /* ------------------------------------------------------------------ */
  /* Agrupación por id (qty)                                             */
  /* ------------------------------------------------------------------ */

  /**
   * Unique items con su cantidad. Orden de primera aparición.
   */
  const groupedItems = computed<Array<{ item: T; qty: number }>>(() => {
    const map = new Map<string, { item: T; qty: number }>()
    for (const i of props.modelValue) {
      const key = String(i.id)
      if (map.has(key)) {
        map.get(key)!.qty++
      } else {
        map.set(key, { item: i, qty: 1 })
      }
    }
    return Array.from(map.values())
  })

  /* ------------------------------------------------------------------ */
  /* Agregar / quitar / incrementar / decrementar                        */
  /* ------------------------------------------------------------------ */

  function addItem(item: { id: string | number; name: string }) {
    emit('update:modelValue', [...props.modelValue, item as T])
  }

  function removeAll(id: string | number) {
    emit(
      'update:modelValue',
      props.modelValue.filter(i => String(i.id) !== String(id))
    )
  }

  function increment(item: { id: string | number; name: string }) {
    emit('update:modelValue', [...props.modelValue, item as T])
  }

  function decrement(id: string | number) {
    // Elimina solo la última ocurrencia
    const arr = [...props.modelValue]
    const idx = arr.map(i => String(i.id)).lastIndexOf(String(id))
    if (idx !== -1) arr.splice(idx, 1)
    emit('update:modelValue', arr)
  }

  /* ------------------------------------------------------------------ */
  /* Totales para el contador                                            */
  /* ------------------------------------------------------------------ */

  const totalQty = computed(() => props.modelValue.length)

  /* exponer para que el padre pueda limpiar */
  defineExpose({
    clear: () => {
      searchQuery.value = ''
      searchResults.value = []
      lastQuery = ''
    }
  })
</script>

<template>
  <!-- ================================================================
       Contenedor principal con flexbox para reordenar
       ================================================================ -->
  <div class="isp-root" :class="{ 'isp-focused': isFocused }">

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
          <i :class="searching ? 'fas fa-spinner fa-spin' : 'fas fa-search'" style="width: 14px"></i>
        </span>
        <input
          type="text"
          class="form-control"
          :placeholder="placeholder"
          v-model="searchQuery"
          autocomplete="off"
          @focus="onFocus"
          @blur="onBlur"
        />
      </div>

      <!-- Dropdown de resultados (siempre bajo el input) -->
      <Transition name="isp-dropdown">
        <div v-if="isFocused && searchResults.length > 0" class="isp-results">
          <button
            v-for="result in searchResults"
            :key="String(result.id)"
            type="button"
            class="isp-result-item"
            @mousedown.prevent="addItem(result)"
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
              {{ totalQty }} ítem{{ totalQty !== 1 ? 's' : '' }}
            </span>
            <slot name="panel-header-right" />
          </div>
        </div>

        <TransitionGroup name="isp-chip" tag="div" class="isp-chips" :class="`isp-chips--${chipLayout}`">
          <span
            v-for="{ item, qty } in groupedItems"
            :key="String(item.id)"
            class="isp-chip"
            :class="{ 'isp-chip--row': chipLayout === 'list' }"
          >
            <slot
              name="chip"
              :item="item"
              :qty="qty"
              :remove="() => removeAll(item.id)"
              :increment="() => increment(item)"
              :decrement="() => decrement(item.id)"
            >
              <i class="fas fa-tag chip-icon"></i>
              <span class="chip-name">{{ item.name }}</span>
              <span v-if="qty > 1" class="chip-qty">×{{ qty }}</span>
              <span class="chip-remove" @click="removeAll(item.id)" role="button" title="Quitar">
                <i class="fas fa-xmark"></i>
              </span>
            </slot>
          </span>
        </TransitionGroup>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
  /* ------------------------------------------------------------------ */
  /* Root                                                                 */
  /* ------------------------------------------------------------------ */
  .isp-root {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  /* Cuando está enfocado, el dropdown queda encima del panel visualmente
     pero el DOM no se reordena — el z-index + position:absolute lo maneja */

  /* ------------------------------------------------------------------ */
  /* Buscador */
  /* ------------------------------------------------------------------ */
  .isp-search-wrapper {
    position: relative;
  }

  .isp-search-label {
    font-size: 0.775rem;
    font-weight: 600;
    color: var(--ds-text-2);
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
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: 0.3rem;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    max-height: 220px;
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
    border-bottom: 1px solid var(--ds-border);
    cursor: pointer;
    text-align: left;
    font-size: 0.815rem;
    transition: background 0.12s;
    color: var(--ds-text);
  }

  .isp-result-item:last-child {
    border-bottom: none;
  }

  .isp-result-item:hover {
    background: rgba(102,126,234,.12);
  }

  /* ---------------- SLOT RESULT ---------------- */

  :deep(.r-code) {
    font-family: monospace;
    font-size: 0.72rem;
    background: var(--ds-surface-2);
    color: var(--ds-text-2);
    padding: 1px 5px;
    border-radius: 3px;
    flex-shrink: 0;
  }

  :deep(.r-name) {
    flex: 1;
    font-weight: 500;
    color: var(--ds-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  :deep(.r-price) {
    color: #198754;
    font-size: 0.77rem;
    flex-shrink: 0;
  }

  :deep(.r-add) {
    color: #0d6efd;
    flex-shrink: 0;
    font-size: 0.85rem;
  }

  /* ------------------------------------------------------------------ */
  /* Panel */
  /* ------------------------------------------------------------------ */

  .isp-panel {
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-border);
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
    color: var(--ds-text-2);
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
  /* Chips */
  /* ------------------------------------------------------------------ */

  .isp-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
  }

  /* layout lista (filas tipo factura) */
  .isp-chips--list {
    flex-direction: column;
    flex-wrap: nowrap;
    gap: 0.25rem;
  }

  .isp-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: rgba(102,126,234,.14);
    border: 1px solid rgba(102,126,234,.32);
    border-radius: 1rem;
    padding: 0.18rem 0.45rem 0.18rem 0.55rem;
    font-size: 0.775rem;
    color: #94a8ee;
    white-space: nowrap;
    max-width: 260px;
  }
  [data-theme="light"] .isp-chip { color: #4c5fd6; }

  /* En modo lista: ocupa todo el ancho, sin pill */
  .isp-chip--row {
    display: flex;
    width: 100%;
    max-width: 100%;
    background: none;
    border: none;
    border-radius: 0;
    padding: 0;
  }

  .chip-qty {
    font-size: 0.65rem;
    font-weight: 700;
    background: #0d6efd;
    color: #fff;
    border-radius: 0.6rem;
    padding: 0 5px;
    line-height: 1.55;
    flex-shrink: 0;
  }

  /* ---------------- SLOT CHIP ---------------- */

  :deep(.chip-icon) {
    font-size: 0.58rem;
    color: #7aa3e0;
    flex-shrink: 0;
  }

  :deep(.chip-name) {
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

  :deep(.chip-qty-badge) {
    font-size: 0.65rem;
    font-weight: 700;
    background: #0d6efd;
    color: #fff;
    border-radius: 0.6rem;
    padding: 0 5px;
    line-height: 1.55;
    flex-shrink: 0;
  }

  :deep(.chip-qty-controls) {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    flex-shrink: 0;
  }

  :deep(.chip-qty-btn) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: none;
    border: none;
    outline: none;
    padding: 0;
    cursor: pointer;
    color: #7aa3e0;
    font-size: 0.6rem;
    line-height: 1;
    transition:
      background 0.15s,
      color 0.15s;
  }

  :deep(.chip-qty-btn:hover) {
    background: rgba(102,126,234,.28);
    color: #94a8ee;
  }
  [data-theme="light"] :deep(.chip-qty-btn:hover) { color: #1a56b0; }

  :deep(.chip-remove) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    cursor: pointer;
    color: #a8c4e8;
    font-size: 0.65rem;
    line-height: 1;
    margin-left: 1px;
    background: none;
    border: none;
    outline: none;
    padding: 0;
    transition:
      background 0.15s,
      color 0.15s;
    user-select: none;
  }

  :deep(.chip-remove:hover) {
    background: rgba(220,38,38,.2);
    color: #ef4444;
  }

  /* ------------------------------------------------------------------ */
  /* Transiciones */
  /* ------------------------------------------------------------------ */

  .isp-dropdown-enter-active,
  .isp-dropdown-leave-active {
    transition:
      opacity 0.13s ease,
      transform 0.13s ease;
  }

  .isp-dropdown-enter-from,
  .isp-dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
  }

  .isp-slide-fade-enter-active,
  .isp-slide-fade-leave-active {
    transition:
      opacity 0.18s ease,
      transform 0.18s ease;
  }

  .isp-slide-fade-enter-from,
  .isp-slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
  }

  .isp-chip-enter-active,
  .isp-chip-leave-active {
    transition:
      opacity 0.15s ease,
      transform 0.15s ease;
  }

  .isp-chip-enter-from,
  .isp-chip-leave-to {
    opacity: 0;
    transform: scale(0.8);
  }
</style>
