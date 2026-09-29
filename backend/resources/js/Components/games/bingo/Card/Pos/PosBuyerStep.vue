<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import FormInput from '@/js/Components/Common/Form/FormInput.vue'
import { usePos } from '@/js/composables/games/bingo/card/pos/posContext'
import { useFrequentBuyers } from '@/js/composables/games/bingo/card/pos/usePosTodaySales'
import { showToast } from '@/js/utils/toastNotify'
import type { FrequentBuyer } from '@/js/types/games/bingo/card/bingo-card-generate'

/**
 * Paso 2: comprador. País en chips (se salta si hay uno solo), clientes
 * frecuentes para completar en un clic, teléfono con búsqueda automática
 * del comprador en la sala y nombre.
 */
const { rooms, sale } = usePos()
const frequent = useFrequentBuyers()

const locked = computed(() => !rooms.selectedRoom)
const showSearch = ref(false)

onMounted(() => frequent.load())

/** Clientes recientes como atajo cuando el teléfono aún está vacío. */
const recentChips = computed(() => frequent.query.value ? [] : frequent.buyers.value.slice(0, 4))

async function pick(fb: FrequentBuyer): Promise<void> {
  showSearch.value = false
  await sale.applyFrequentBuyer(fb)
}

function closeSearchSoon(): void {
  // Deja terminar el click sobre un resultado antes de cerrar el panel.
  window.setTimeout(() => { showSearch.value = false }, 150)
}

function initials(fb: FrequentBuyer): string {
  return `${fb.first_name.charAt(0)}${fb.last_name.charAt(0)}`.toUpperCase() || '?'
}

async function copy(text: string | null): Promise<void> {
  if (!text) return
  try {
    await navigator.clipboard.writeText(text)
    showToast('Link copiado', 'success')
  } catch {
    showToast('No se pudo copiar el link', 'error')
  }
}

const stepState = computed(() => sale.buyerReady ? 'done' : locked.value ? 'idle' : 'active')
</script>

<template>
  <section class="pos-card" :class="{ 'pos-card--locked': locked }" :aria-disabled="locked">
    <div class="pos-step">
      <span class="pos-step__num" :class="{ 'pos-step__num--done': stepState === 'done', 'pos-step__num--active': stepState === 'active' }">
        <i v-if="stepState === 'done'" class="fas fa-check"></i><template v-else>2</template>
      </span>
      <div>
        <h2 class="pos-step__title">Comprador</h2>
        <p class="pos-step__hint">Si ya te compró antes, búscalo por nombre o teléfono.</p>
      </div>
    </div>

    <template v-if="!locked">
      <!-- ── País ── -->
      <div v-if="sale.loadingCountries" class="pos-skeleton mb-3" style="height: 34px; max-width: 260px"></div>
      <div v-else-if="sale.countries.length > 1" class="mb-3">
        <span class="pos-label">País del comprador</span>
        <div class="pos-chips" role="radiogroup" aria-label="País">
          <button
            v-for="c in sale.countries"
            :key="c.id"
            type="button"
            role="radio"
            class="pos-chip"
            :class="{ 'pos-chip--on': sale.selectedCountry === c.id }"
            :aria-checked="sale.selectedCountry === c.id"
            @click="sale.selectedCountry = c.id"
          >
            <span class="pbs-iso">{{ c.iso_code_2 }}</span>{{ c.name }}
          </button>
        </div>
      </div>
      <div v-else-if="!sale.countries.length" class="pbs-alert pbs-alert--warning mb-3">
        <i class="fas fa-exclamation-triangle"></i>
        Esta sala no acepta ninguna de las monedas de tu comercio. Elige otra sala.
      </div>

      <template v-if="sale.selectedCountry">
        <!-- ── Clientes frecuentes ── -->
        <div class="pbs-search mb-3">
          <div class="pbs-search__box">
            <i class="fas fa-address-book"></i>
            <input
              type="search"
              :value="frequent.query.value"
              placeholder="Buscar cliente frecuente…"
              aria-label="Buscar cliente frecuente por nombre o teléfono"
              @focus="showSearch = true"
              @blur="closeSearchSoon"
              @input="frequent.search(($event.target as HTMLInputElement).value); showSearch = true"
            />
            <i v-if="frequent.loading.value" class="fas fa-spinner fa-spin"></i>
          </div>

          <div v-if="showSearch" class="pbs-results" role="listbox">
            <button
              v-for="fb in frequent.buyers.value"
              :key="`${fb.country_id}-${fb.phone}`"
              type="button"
              role="option"
              class="pbs-result"
              @mousedown.prevent="pick(fb)"
            >
              <span class="pbs-avatar">{{ initials(fb) }}</span>
              <span class="pbs-result__body">
                <strong>{{ fb.first_name }} {{ fb.last_name }}</strong>
                <span class="pos-muted">{{ fb.phone }}</span>
              </span>
              <span class="pos-pill pos-pill--muted">{{ fb.purchases }} {{ fb.purchases === 1 ? 'compra' : 'compras' }}</span>
            </button>
            <div v-if="!frequent.buyers.value.length && !frequent.loading.value" class="pbs-results__empty">
              {{ frequent.query.value ? 'Sin coincidencias. Escribe el teléfono abajo.' : 'Aún no tienes clientes registrados.' }}
            </div>
          </div>

          <div v-if="recentChips.length && !sale.buyer.phone" class="pos-chips mt-2">
            <span class="pos-muted align-self-center me-1">Recientes:</span>
            <button v-for="fb in recentChips" :key="`r-${fb.country_id}-${fb.phone}`" type="button" class="pos-chip" @click="pick(fb)">
              <span class="pbs-avatar pbs-avatar--sm">{{ initials(fb) }}</span>{{ fb.first_name }}
            </button>
          </div>
        </div>

        <!-- ── Teléfono + nombre ── -->
        <div class="pbs-fields">
          <div class="pbs-phone">
            <FormInput
              label="Teléfono"
              name="phone"
              type="tel"
              required
              col=""
              :mask="sale.phone.phoneMask ?? undefined"
              :maskEmitFormatted="true"
              :placeholder="sale.phone.phonePlaceholder"
              :hint="!sale.phone.phoneError ? sale.phone.phoneHint : undefined"
              :error="sale.phone.phoneError ?? undefined"
              :inputValidState="sale.buyer.phone && sale.phone.isPhoneValid ? 'valid' : null"
              :modelValue="sale.buyer.phone"
              @update:modelValue="(v: string) => (sale.buyer.phone = String(v ?? ''))"
            />
            <span class="pbs-lookup" aria-live="polite">
              <span v-if="sale.buyerLookup === 'checking'" class="pos-pill pos-pill--muted"><i class="fas fa-spinner fa-spin"></i> Buscando…</span>
              <span v-else-if="sale.buyerLookup === 'found'" class="pos-pill pos-pill--success"><i class="fas fa-user-check"></i> Ya compró en esta sala</span>
              <span v-else-if="sale.buyerLookup === 'new'" class="pos-pill pos-pill--info"><i class="fas fa-user-plus"></i> Comprador nuevo en esta sala</span>
            </span>
          </div>

          <template v-if="sale.buyerLookup === 'found' || sale.buyerLookup === 'new'">
            <FormInput
              label="Nombre"
              name="first_name"
              required
              col=""
              placeholder="Nombre"
              :readonly="sale.buyerLookup === 'found'"
              :modelValue="sale.buyer.firstName"
              @update:modelValue="(v: string) => (sale.buyer.firstName = String(v ?? ''))"
            />
            <FormInput
              label="Apellido"
              name="last_name"
              required
              col=""
              placeholder="Apellido"
              :readonly="sale.buyerLookup === 'found'"
              :modelValue="sale.buyer.lastName"
              @update:modelValue="(v: string) => (sale.buyer.lastName = String(v ?? ''))"
            />
          </template>
        </div>

        <!-- ── Comprador que ya tiene cartones en esta sala ── -->
        <div v-if="sale.existingBuyer" class="pbs-alert pbs-alert--success">
          <i class="fas fa-ticket-alt"></i>
          <div class="flex-grow-1">
            <strong>{{ sale.buyer.firstName }}</strong> ya tiene
            <strong>{{ sale.existingBuyer.cardCount }} {{ sale.existingBuyer.cardCount === 1 ? 'cartón' : 'cartones' }}</strong>
            en esta sala. Los nuevos se suman a su mismo link.
          </div>
          <button v-if="sale.existingBuyer.link" type="button" class="pos-btn pos-btn--ghost pos-btn--sm" @click="copy(sale.existingBuyer.link)">
            <i class="fas fa-link"></i>Copiar link
          </button>
        </div>
      </template>
    </template>

    <p v-else class="pos-muted mb-0"><i class="fas fa-lock me-1"></i>Elige primero una sala.</p>
  </section>
</template>

<style scoped>
.pbs-iso {
  font-size: 10px;
  font-weight: 800;
  padding: 1px 5px;
  border-radius: 4px;
  background: var(--ds-badge-bg);
  color: var(--ds-badge-color);
}
.pos-chip--on .pbs-iso { background: rgba(255,255,255,.25); color: #fff; }

.pbs-search { position: relative; }
.pbs-search__box {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 10px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
}
.pbs-search__box:focus-within { border-color: var(--pos-accent); box-shadow: 0 0 0 3px var(--pos-accent-soft); }
.pbs-search__box input { flex: 1; min-width: 0; border: none; outline: none; background: transparent; color: var(--ds-text); font-size: 14px; }

.pbs-results {
  position: absolute;
  z-index: 20;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  max-height: 290px;
  overflow-y: auto;
  padding: 4px;
  border-radius: 12px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface);
  box-shadow: var(--ds-card-hover-shadow);
}
.pbs-result {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--ds-text);
  text-align: left;
  cursor: pointer;
}
.pbs-result:hover, .pbs-result:focus-visible { background: var(--ds-surface-2); outline: none; }
.pbs-result__body { display: flex; flex-direction: column; flex: 1; min-width: 0; font-size: 13px; }
.pbs-results__empty { padding: 12px; font-size: 13px; color: var(--ds-text-2); text-align: center; }

.pbs-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  font-size: 12px;
  font-weight: 800;
  background: var(--pos-accent-soft);
  color: var(--pos-accent-strong);
}
.pbs-avatar--sm { width: 20px; height: 20px; font-size: 9px; }

.pbs-fields {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr) minmax(0, 1fr);
  gap: 0 12px;
  align-items: start;
}
.pbs-fields :deep(.form-group) { margin-bottom: 10px; }
.pbs-phone { position: relative; }
.pbs-lookup { display: block; margin-top: -4px; margin-bottom: 8px; min-height: 20px; }

.pbs-alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 13px;
}
.pbs-alert--success { background: var(--pos-success-soft); }
.pbs-alert--success > i { color: var(--pos-success); }
.pbs-alert--warning { background: var(--pos-warning-soft); }
.pbs-alert--warning > i { color: var(--pos-warning); }

@media (max-width: 768px) {
  .pbs-fields { grid-template-columns: 1fr 1fr; }
  .pbs-phone { grid-column: 1 / -1; }
}
</style>
