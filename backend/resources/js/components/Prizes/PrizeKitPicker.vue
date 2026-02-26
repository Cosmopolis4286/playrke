<script setup lang="ts" name="PrizeKitPicker">
  import { computed, ref } from 'vue'
  import ItemSearchPicker from '@/js/Components/Common/Form/ItemSearchPicker.vue'
  import backendClient from '@/js/plugins/axios'

  import type { PrizeSearchResult } from '@/js/types/prizes/prize'

  /* ========================================================================== */
  /* Props / Emits */
  /* ========================================================================== */

  const props = defineProps<{
    modelValue:  PrizeSearchResult[]
    excludeId?:  string | number
    error?:      string
    rewardType?: string   // MIXED o undefined → sin filtro (devuelve todos los no-kit)
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: PrizeSearchResult[]): void
  }>()

  /* ========================================================================== */
  /* Internal */
  /* ========================================================================== */

  const pickerRef = ref<{ clear: () => void } | null>(null)

  const items = computed({
    get: () => props.modelValue,
    set: (v: PrizeSearchResult[]) => emit('update:modelValue', v)
  })

  /* ========================================================================== */
  /* Search */
  /* ========================================================================== */

  async function searchPrizes(q: string): Promise<PrizeSearchResult[]> {
    const rewardType = props.rewardType && props.rewardType !== 'MIXED'
      ? props.rewardType
      : undefined

    const { data } = await backendClient.get<{ data: PrizeSearchResult[] }>('/prizes/search', {
      params: { q, exclude: props.excludeId, reward_type: rewardType }
    })
    return data.data ?? []
  }

  /* ========================================================================== */
  /* Helpers */
  /* ========================================================================== */

  const REWARD_LABELS: Record<string, string> = {
    PRODUCT: 'Producto',
    SERVICE: 'Servicio',
    MIXED:   'Mixta',
  }

  function rewardTypeLabel(type: string): string {
    return REWARD_LABELS[type] ?? type
  }

  /* ========================================================================== */
  /* Total */
  /* ========================================================================== */

  const total = computed(() => items.value.reduce((sum, i) => sum + Number(i.price ?? 0), 0))

  /* ========================================================================== */
  /* Expose */
  /* ========================================================================== */

  defineExpose({ clear: () => pickerRef.value?.clear() })
</script>

<template>
  <ItemSearchPicker
    ref="pickerRef"
    :modelValue="items"
    :search="searchPrizes"
    label="Agregar ítems al kit"
    placeholder="Buscar por nombre o código..."
    panelTitle="Ítems del kit"
    chipLayout="list"
    :error="error"
    @update:modelValue="items = $event"
  >
    <!-- ── Resultado búsqueda ── -->
    <template #result="{ item }">
      <span class="r-code">{{ (item as any).code }}</span>
      <span class="r-result-body">
        <span class="r-name">{{ item.name }}</span>
        <span v-if="(item as any).description" class="r-desc">{{ (item as any).description }}</span>
      </span>
      <span class="r-type" :class="`r-type--${((item as any).reward_type ?? '').toLowerCase()}`">
        {{ rewardTypeLabel((item as any).reward_type) }}
      </span>
      <span class="r-price">${{ Number((item as any).price).toFixed(2) }}</span>
      <span class="r-add"><i class="fas fa-plus-circle"></i></span>
    </template>

    <!-- ── Fila de factura por ítem ── -->
    <template #chip="{ item, qty, remove, increment, decrement }">
      <div class="kit-row">
        <!-- Nombre + tipo -->
        <div class="kit-row__name">
          <i class="fas fa-tag kit-row__icon"></i>
          <span class="kit-row__label">{{ item.name }}</span>
        </div>

        <!-- Precio unitario -->
        <div class="kit-row__unit">
          ${{ Number((item as any).price).toFixed(2) }}
        </div>

        <!-- Controles cantidad -->
        <div class="kit-row__qty">
          <button type="button" class="qty-btn" @click="decrement" title="Quitar uno">
            <i class="fas fa-minus"></i>
          </button>
          <span class="qty-badge">{{ qty }}</span>
          <button type="button" class="qty-btn" @click="increment" title="Agregar uno más">
            <i class="fas fa-plus"></i>
          </button>
        </div>

        <!-- Subtotal -->
        <div class="kit-row__subtotal">
          ${{ (Number((item as any).price) * qty).toFixed(2) }}
        </div>

        <!-- Quitar todo -->
        <button type="button" class="kit-row__remove" @click="remove" title="Quitar del kit">
          <i class="fas fa-xmark"></i>
        </button>
      </div>
    </template>

    <!-- ── Icono panel ── -->
    <template #panel-icon>
      <i class="fas fa-receipt"></i>
    </template>

    <!-- ── Header right: total ── -->
    <template #panel-header-right>
      <span class="kit-total">
        Total: <strong>${{ total.toFixed(2) }}</strong>
      </span>
    </template>
  </ItemSearchPicker>
</template>

<style scoped>
  /* ------------------------------------------------------------------ */
  /* Fila tipo factura                                                    */
  /* ------------------------------------------------------------------ */
  .kit-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.3rem 0.5rem;
    border-radius: 0.3rem;
    background: #f8faff;
    border: 1px solid #dce8fb;
    font-size: 0.785rem;
    color: #1a56b0;
  }

  .kit-row__name {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    flex: 1 1 0;
    min-width: 0;
  }

  .kit-row__icon {
    font-size: 0.6rem;
    color: #7aa3e0;
    flex-shrink: 0;
  }

  .kit-row__label {
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* precio unitario */
  .kit-row__unit {
    font-size: 0.72rem;
    color: #6c757d;
    white-space: nowrap;
    flex-shrink: 0;
    min-width: 56px;
    text-align: right;
  }

  /* controles qty */
  .kit-row__qty {
    display: flex;
    align-items: center;
    gap: 0.2rem;
    flex-shrink: 0;
  }

  .qty-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: none;
    outline: none;
    background: #dce8fb;
    color: #1a56b0;
    cursor: pointer;
    font-size: 0.55rem;
    padding: 0;
    transition: background 0.13s, color 0.13s;
  }

  .qty-btn:hover {
    background: #0d6efd;
    color: #fff;
  }

  .qty-badge {
    font-size: 0.72rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
    color: #0d6efd;
  }

  /* subtotal */
  .kit-row__subtotal {
    font-size: 0.78rem;
    font-weight: 700;
    color: #198754;
    white-space: nowrap;
    flex-shrink: 0;
    min-width: 64px;
    text-align: right;
  }

  /* quitar */
  .kit-row__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: none;
    outline: none;
    background: none;
    color: #b0c8f0;
    cursor: pointer;
    font-size: 0.65rem;
    padding: 0;
    flex-shrink: 0;
    transition: background 0.13s, color 0.13s;
  }

  .kit-row__remove:hover {
    background: #fecaca;
    color: #dc2626;
  }

  /* ── Resultados de búsqueda ── */
  :deep(.r-result-body) {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    gap: 0.05rem;
  }

  :deep(.r-desc) {
    font-size: 0.68rem;
    color: #9ca3af;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  :deep(.r-type) {
    font-size: 0.65rem;
    font-weight: 600;
    border-radius: 0.4rem;
    padding: 1px 6px;
    white-space: nowrap;
    flex-shrink: 0;
    line-height: 1.6;
  }

  :deep(.r-type--product) {
    background: #dbeafe;
    color: #1d4ed8;
  }

  :deep(.r-type--service) {
    background: #dcfce7;
    color: #166534;
  }

  :deep(.r-type--mixed) {
    background: #fef3c7;
    color: #92400e;
  }

  /* ── total header ── */
  .kit-total {
    font-size: 0.72rem;
    color: #198754;
    font-weight: 600;
  }
</style>
