<script setup lang="ts">
interface Currency { id: string; symbol: string; name: string; iso_code: string }

defineProps<{
  currencies: Currency[]
  feeByCurrency: Record<string, number>
  getCurrencyById: (id: string) => Currency | undefined
  error?: string
  disabled?: boolean
}>()

defineEmits<{
  toggle: [id: string]
  'update:fee': [id: string, value: number]
}>()
</script>

<template>
  <div class="space-y-4">
    <!-- Selector de monedas -->
    <div>
      <label class="block text-sm font-medium fbc-text-secondary mb-2">
        Monedas de entrada
      </label>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="c in currencies" :key="c.id"
          type="button"
          :disabled="disabled"
          @click="$emit('toggle', c.id)"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all disabled:opacity-40 disabled:cursor-not-allowed"
          :class="feeByCurrency[c.id] !== undefined
            ? 'bg-indigo-600 border-indigo-600 text-white shadow'
            : 'fbc-chip hover:border-indigo-400'"
        >
          <span>{{ c.symbol }}</span>
          <span class="opacity-80">{{ c.iso_code }}</span>
          <span v-if="feeByCurrency[c.id] !== undefined" class="ml-0.5">✓</span>
        </button>
      </div>
      <p class="mt-1.5 text-xs fbc-text-muted">Haz clic para activar/desactivar. Sin selección = sala gratuita.</p>
    </div>

    <!-- Precios por moneda -->
    <div v-if="Object.keys(feeByCurrency).length > 0"
      class="rounded-xl border border-indigo-100 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20 p-4">
      <p class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 mb-3 uppercase tracking-wide">
        💰 Precio de entrada por moneda
      </p>
      <div class="flex flex-wrap gap-3">
        <div v-for="(_, cid) in feeByCurrency" :key="cid" class="flex items-center gap-0">
          <span class="inline-flex items-center px-2.5 py-1.5 rounded-l-lg bg-indigo-100 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 text-xs font-bold border border-indigo-200 dark:border-indigo-700">
            {{ getCurrencyById(String(cid))?.symbol ?? cid }}
          </span>
          <input
            type="number"
            min="0"
            step="0.01"
            :disabled="disabled"
            :value="feeByCurrency[cid]"
            @input="$emit('update:fee', String(cid), Number(($event.target as HTMLInputElement).value))"
            class="fbc-price-input w-24 rounded-r-lg border border-l-0 border-indigo-200 dark:border-indigo-700 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400 disabled:opacity-50"
            placeholder="0.00"
          />
        </div>
      </div>
      <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </div>
  </div>
</template>

<style scoped>
.fbc-text-secondary { color: var(--ds-text-2); }
.fbc-text-muted { color: var(--ds-text-muted); }
.fbc-chip { background: var(--ds-surface); border-color: var(--ds-border); color: var(--ds-text-2); }
.fbc-price-input { background: var(--ds-surface); color: var(--ds-text); }
</style>
