<script setup lang="ts">
/**
 * Marketplace P2P — listado de activos publicados por otros jugadores.
 * Ver MARKETPLACE_ARCHITECTURE_AUDIT.md. Comprar dispara settlement
 * automático (create->fund->lockAsset->release) en la misma request —
 * ver MarketplaceController::buy().
 */
import { onMounted, ref } from 'vue'
import { useMarketplace } from '@js/composables/marketplace/useMarketplace'
import type { Listing, Paginated } from '@js/types/marketplace/listing'

const props = defineProps<{ listings: Paginated<Listing> }>()

const { listings, loading, buying, error, refresh, buy } = useMarketplace(props.listings)
const feedback = ref<{ type: 'success' | 'error'; message: string } | null>(null)

onMounted(() => {
  refresh()
})

async function handleBuy(listing: Listing) {
  const result = await buy(listing)
  feedback.value = { type: result.success ? 'success' : 'error', message: result.message }
}
</script>

<template>
  <div class="marketplace-page">
    <div class="marketplace-header">
      <div>
        <h1 class="marketplace-title"><i class="fas fa-store"></i> Marketplace</h1>
        <p class="marketplace-sub">Compra y vende activos digitales de otros jugadores</p>
      </div>
      <button class="marketplace-refresh" :class="{ 'marketplace-refresh--spin': loading }" :disabled="loading" @click="refresh">
        <i class="fas fa-sync-alt"></i> Actualizar
      </button>
    </div>

    <div v-if="feedback" class="marketplace-feedback" :class="`marketplace-feedback--${feedback.type}`">
      {{ feedback.message }}
    </div>

    <div v-if="loading && !listings?.data.length" class="marketplace-state">
      <span class="marketplace-spinner"></span><span>Cargando listings...</span>
    </div>

    <div v-else-if="error" class="marketplace-state marketplace-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <div v-else-if="listings?.data.length" class="marketplace-grid">
      <div v-for="listing in listings.data" :key="listing.id" class="marketplace-card">
        <div class="marketplace-card__rarity" :class="`rarity--${listing.asset.rarity.toLowerCase()}`">{{ listing.asset.rarity }}</div>
        <h3 class="marketplace-card__name">{{ listing.asset.name }}</h3>
        <p class="marketplace-card__category">{{ listing.asset.category }}</p>
        <p class="marketplace-card__seller">Vendido por {{ listing.seller_name }}</p>
        <p class="marketplace-card__quantity" v-if="listing.quantity > 1">Cantidad: {{ listing.quantity }}</p>
        <div class="marketplace-card__price">{{ listing.currency.symbol }}{{ listing.price.toFixed(2) }}</div>
        <button
          class="marketplace-card__buy"
          :disabled="buying === listing.id"
          @click="handleBuy(listing)"
        >
          <i class="fas fa-shopping-cart"></i> {{ buying === listing.id ? 'Procesando...' : 'Comprar' }}
        </button>
      </div>
    </div>

    <div v-else class="marketplace-state marketplace-state--empty">
      <i class="fas fa-inbox"></i>
      <p>No hay activos publicados ahora mismo</p>
      <small>Vuelve a revisar en unos minutos</small>
    </div>
  </div>
</template>

<style scoped>
.marketplace-page { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }

.marketplace-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.marketplace-title { font-size: 1.5rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.marketplace-sub { color: #6b7280; margin: 0.25rem 0 0; }

.marketplace-refresh {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e5e7eb;
  background: #fff; cursor: pointer; font-size: 0.875rem;
}
.marketplace-refresh--spin i { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.marketplace-feedback {
  padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem;
}
.marketplace-feedback--success { background: #dcfce7; color: #166534; }
.marketplace-feedback--error { background: #fee2e2; color: #991b1b; }

.marketplace-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.5rem; padding: 3rem 1rem; color: #6b7280;
}
.marketplace-state--error { color: #dc2626; }
.marketplace-spinner {
  width: 24px; height: 24px; border: 3px solid #e5e7eb; border-top-color: #667eea;
  border-radius: 50%; animation: spin 0.8s linear infinite;
}

.marketplace-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;
}

.marketplace-card {
  border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem;
  display: flex; flex-direction: column; gap: 0.35rem; background: #fff;
}

.marketplace-card__rarity {
  align-self: flex-start; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
  padding: 0.15rem 0.5rem; border-radius: 999px; background: #f3f4f6; color: #4b5563;
}
.rarity--rare { background: #dbeafe; color: #1e40af; }
.rarity--common { background: #f3f4f6; color: #4b5563; }

.marketplace-card__name { font-size: 1rem; font-weight: 600; margin: 0; }
.marketplace-card__category { font-size: 0.8rem; color: #9ca3af; margin: 0; text-transform: capitalize; }
.marketplace-card__seller { font-size: 0.8rem; color: #6b7280; margin: 0; }
.marketplace-card__quantity { font-size: 0.8rem; color: #6b7280; margin: 0; }

.marketplace-card__price { font-size: 1.25rem; font-weight: 700; color: #059669; margin-top: 0.25rem; }

.marketplace-card__buy {
  margin-top: 0.5rem; padding: 0.5rem; border-radius: 0.5rem; border: none;
  background: #667eea; color: #fff; font-weight: 600; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 0.4rem;
}
.marketplace-card__buy:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
