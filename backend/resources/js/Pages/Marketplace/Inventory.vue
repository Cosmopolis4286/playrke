<script setup lang="ts">
/**
 * Mi inventario — activos disponibles del jugador, con acción de publicar
 * en el Marketplace. Ver MARKETPLACE_ARCHITECTURE_AUDIT.md.
 */
import { onMounted, reactive, ref } from 'vue'
import { useInventory } from '@js/composables/marketplace/useInventory'
import type { PlayerAsset } from '@js/types/marketplace/listing'

interface CurrencyOption { id: string; iso_code: string; symbol: string }

const props = defineProps<{ assets: PlayerAsset[]; currencies: CurrencyOption[] }>()

const { assets, loading, error, refresh, createListing } = useInventory(props.assets)
const feedback = ref<{ type: 'success' | 'error'; message: string } | null>(null)
const publishingId = ref<string | null>(null)
const form = reactive({ price: '', currencyId: props.currencies[0]?.id ?? '' })

onMounted(() => {
  refresh()
})

function startPublish(asset: PlayerAsset) {
  publishingId.value = asset.id
  form.price = ''
  form.currencyId = props.currencies[0]?.id ?? ''
}

async function confirmPublish(asset: PlayerAsset) {
  if (!form.price || Number(form.price) <= 0) {
    feedback.value = { type: 'error', message: 'Indica un precio válido.' }
    return
  }
  if (!form.currencyId) {
    feedback.value = { type: 'error', message: 'Selecciona una moneda.' }
    return
  }
  const result = await createListing(asset.id, asset.quantity, Number(form.price), form.currencyId)
  feedback.value = { type: result.success ? 'success' : 'error', message: result.message }
  if (result.success) publishingId.value = null
}
</script>

<template>
  <div class="inventory-page">
    <div class="inventory-header">
      <div>
        <h1 class="inventory-title"><i class="fas fa-boxes-stacked"></i> Mi inventario</h1>
        <p class="inventory-sub">Activos digitales disponibles para usar o vender</p>
      </div>
      <button class="inventory-refresh" :class="{ 'inventory-refresh--spin': loading }" :disabled="loading" @click="refresh">
        <i class="fas fa-sync-alt"></i> Actualizar
      </button>
    </div>

    <div v-if="feedback" class="inventory-feedback" :class="`inventory-feedback--${feedback.type}`">
      {{ feedback.message }}
    </div>

    <div v-if="loading && !assets.length" class="inventory-state">
      <span class="inventory-spinner"></span><span>Cargando inventario...</span>
    </div>

    <div v-else-if="error" class="inventory-state inventory-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <div v-else-if="assets.length" class="inventory-grid">
      <div v-for="asset in assets" :key="asset.id" class="inventory-card">
        <h3 class="inventory-card__name">{{ asset.definition.name }}</h3>
        <p class="inventory-card__category">{{ asset.definition.category }} · {{ asset.definition.rarity }}</p>
        <p class="inventory-card__quantity">Cantidad: {{ asset.quantity }}</p>

        <template v-if="asset.definition.tradable && asset.definition.marketplace_enabled">
          <button v-if="publishingId !== asset.id" class="inventory-card__publish" @click="startPublish(asset)">
            <i class="fas fa-tag"></i> Publicar en marketplace
          </button>
          <div v-else class="inventory-card__form">
            <input v-model="form.price" type="number" min="0.01" step="0.01" placeholder="Precio" />
            <select v-model="form.currencyId">
              <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.iso_code }}</option>
            </select>
            <button class="inventory-card__confirm" @click="confirmPublish(asset)">Confirmar</button>
            <button class="inventory-card__cancel" @click="publishingId = null">Cancelar</button>
          </div>
        </template>
        <p v-else class="inventory-card__not-tradable">No comerciable</p>
      </div>
    </div>

    <div v-else class="inventory-state inventory-state--empty">
      <i class="fas fa-box-open"></i>
      <p>Tu inventario está vacío</p>
    </div>
  </div>
</template>

<style scoped>
.inventory-page { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }

.inventory-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;
}
.inventory-title { font-size: 1.5rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.inventory-sub { color: #6b7280; margin: 0.25rem 0 0; }

.inventory-refresh {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e5e7eb;
  background: #fff; cursor: pointer; font-size: 0.875rem;
}
.inventory-refresh--spin i { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.inventory-feedback { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem; }
.inventory-feedback--success { background: #dcfce7; color: #166534; }
.inventory-feedback--error { background: #fee2e2; color: #991b1b; }

.inventory-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.5rem; padding: 3rem 1rem; color: #6b7280;
}
.inventory-spinner {
  width: 24px; height: 24px; border: 3px solid #e5e7eb; border-top-color: #667eea;
  border-radius: 50%; animation: spin 0.8s linear infinite;
}

.inventory-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }

.inventory-card {
  border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem;
  display: flex; flex-direction: column; gap: 0.35rem; background: #fff;
}
.inventory-card__name { font-size: 1rem; font-weight: 600; margin: 0; }
.inventory-card__category { font-size: 0.8rem; color: #9ca3af; margin: 0; text-transform: capitalize; }
.inventory-card__quantity { font-size: 0.85rem; color: #374151; margin: 0; }
.inventory-card__not-tradable { font-size: 0.8rem; color: #9ca3af; margin-top: 0.5rem; }

.inventory-card__publish {
  margin-top: 0.5rem; padding: 0.5rem; border-radius: 0.5rem; border: none;
  background: #667eea; color: #fff; font-weight: 600; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 0.4rem;
}

.inventory-card__form { display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.5rem; }
.inventory-card__form input, .inventory-card__form select {
  padding: 0.4rem 0.6rem; border-radius: 0.4rem; border: 1px solid #d1d5db; font-size: 0.85rem;
}
.inventory-card__confirm, .inventory-card__cancel {
  padding: 0.4rem; border-radius: 0.4rem; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 600;
}
.inventory-card__confirm { background: #059669; color: #fff; }
.inventory-card__cancel { background: #f3f4f6; color: #4b5563; }
</style>
