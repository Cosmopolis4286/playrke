<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { usePosPurchaseCodes } from '@/js/composables/games/bingo/card/pos/usePosPurchaseCodes'
import type { PurchaseCode } from '@/js/composables/games/bingo/card/pos/usePosPurchaseCodes'
import { showToastConfirmAction } from '@/js/utils/toastNotify'

/**
 * "Compra con código": enlace público de venta del vendedor y códigos de
 * compra para sus compradores RECURRENTES (solo a quien ya le compró). Con
 * el código, el comprador compra por su cuenta desde el enlace y la venta
 * entra a la misma revisión de pagos. Vence por usos o por tiempo.
 */
const pc = reactive(usePosPurchaseCodes())

const open = ref(false)
const selectedBuyerKey = ref('')
const uses = ref(5)
const days = ref(30)
const showAll = ref(false)

onMounted(() => pc.load())

watch(() => pc.options, opts => {
  if (!opts) return
  uses.value = opts.default_uses
  days.value = opts.default_valid_days
}, { immediate: true })

const buyerKey = (b: { country_id: string; phone: string }) => `${b.country_id}|${b.phone}`
const selectedBuyer = computed(() => pc.buyers.find(b => buyerKey(b) === selectedBuyerKey.value) ?? null)

const visibleCodes = computed(() => (showAll.value ? pc.codes : pc.codes.slice(0, 5)))

async function generate(): Promise<void> {
  if (!selectedBuyer.value) return
  const code = await pc.issue(selectedBuyer.value, uses.value, days.value)
  if (code) selectedBuyerKey.value = ''
}

async function confirmRegenerate(): Promise<void> {
  const ok = await showToastConfirmAction({
    title: 'Regenerar enlace',
    text: 'El enlace actual dejará de funcionar. Tendrás que enviar el nuevo a tus compradores.',
    icon: 'warning',
    confirmButtonText: 'Sí, regenerar',
  })
  if (ok) await pc.regenerateLink()
}

async function confirmRevoke(code: PurchaseCode): Promise<void> {
  const ok = await showToastConfirmAction({
    title: 'Revocar código',
    text: `${pc.buyerName(code)} ya no podrá comprar con el código ${code.code}.`,
    icon: 'warning',
    confirmButtonText: 'Sí, revocar',
  })
  if (ok) await pc.revoke(code)
}

function date(iso: string | null): string {
  return iso ? new Date(iso).toLocaleDateString('es', { day: 'numeric', month: 'short' }) : '—'
}

const TONE: Record<PurchaseCode['status'], string> = {
  Vigente: 'success',
  Agotado: 'muted',
  Vencido: 'muted',
  Revocado: 'danger',
}
</script>

<template>
  <section class="pos-card ppc">
    <button type="button" class="ppc__head" :aria-expanded="open" @click="open = !open">
      <span>
        <i class="fas fa-key me-1"></i><strong>Compra con código</strong>
        <span v-if="pc.activeCount" class="pos-pill pos-pill--success ms-1">{{ pc.activeCount }} vigente(s)</span>
      </span>
      <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
    </button>
    <p class="pos-muted ppc__hint">
      Tus compradores frecuentes compran solos desde tu enlace, con un código que tú les das.
    </p>

    <div v-if="open">
      <div v-if="pc.loading" class="pos-skeleton" style="height: 120px"></div>

      <template v-else-if="pc.link">
        <!-- ── Enlace ── -->
        <span class="pos-label">Tu enlace de venta</span>
        <div class="ppc__link">
          <code class="text-truncate">{{ pc.link.url }}</code>
          <button type="button" class="pos-icon-btn" title="Copiar enlace" @click="pc.copy(pc.link.url, 'Enlace')">
            <i class="fas fa-copy"></i>
          </button>
          <button type="button" class="pos-icon-btn" title="Regenerar (invalida el actual)" @click="confirmRegenerate">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>

        <!-- ── Último código generado ── -->
        <div v-if="pc.lastIssued" class="ppc__issued">
          <span class="pos-muted">Código para {{ pc.buyerName(pc.lastIssued) }}</span>
          <strong class="ppc__code">{{ pc.lastIssued.code }}</strong>
          <div class="d-flex gap-2 flex-wrap justify-content-center">
            <a class="pos-btn pos-btn--whatsapp pos-btn--sm" :href="pc.whatsappUrl(pc.lastIssued)" target="_blank" rel="noopener">
              <i class="fab fa-whatsapp"></i>Enviar por WhatsApp
            </a>
            <button type="button" class="pos-btn pos-btn--ghost pos-btn--sm" @click="pc.copy(pc.shareText(pc.lastIssued), 'Mensaje')">
              <i class="fas fa-copy"></i>Copiar mensaje
            </button>
          </div>
        </div>

        <!-- ── Dar código ── -->
        <span class="pos-label mt-3">Dar código a un comprador</span>
        <select v-model="selectedBuyerKey" class="form-control form-control-sm" :disabled="!pc.buyers.length">
          <option value="">{{ pc.buyers.length ? 'Elige un comprador frecuente…' : 'Aún no tienes compradores frecuentes' }}</option>
          <option v-for="b in pc.buyers" :key="buyerKey(b)" :value="buyerKey(b)">
            {{ b.first_name }} {{ b.last_name }} · {{ b.phone }} · {{ b.purchases }} compra(s)
          </option>
        </select>

        <template v-if="selectedBuyer && pc.options">
          <span class="pos-label mt-2">Vence a las</span>
          <div class="pos-chips">
            <button v-for="n in pc.options.uses" :key="`u${n}`" type="button" class="pos-chip" :class="{ 'pos-chip--on': uses === n }" @click="uses = n">
              {{ n }} compra{{ n === 1 ? '' : 's' }}
            </button>
          </div>
          <span class="pos-label mt-2">o a los</span>
          <div class="pos-chips">
            <button v-for="d in pc.options.valid_days" :key="`d${d}`" type="button" class="pos-chip" :class="{ 'pos-chip--on': days === d }" @click="days = d">
              {{ d }} días
            </button>
          </div>
          <button type="button" class="pos-btn pos-btn--primary pos-btn--block mt-2" :disabled="pc.issuing" @click="generate">
            <span v-if="pc.issuing" class="spinner-border spinner-border-sm"></span>
            <i v-else class="fas fa-key"></i>Generar código
          </button>
          <p class="pos-muted mt-1 mb-0">Si ya tenía un código vigente, se reemplaza.</p>
        </template>

        <!-- ── Códigos otorgados ── -->
        <template v-if="pc.codes.length">
          <span class="pos-label mt-3">Códigos otorgados</span>
          <ul class="ppc__list">
            <li v-for="c in visibleCodes" :key="c.id">
              <div class="ppc__row">
                <code>{{ c.code }}</code>
                <span class="pos-pill" :class="`pos-pill--${TONE[c.status]}`">{{ c.status }}</span>
              </div>
              <div class="ppc__row pos-muted">
                <span class="text-truncate">{{ pc.buyerName(c) }}</span>
                <span>{{ c.remaining_uses }}/{{ c.max_uses }} · hasta {{ date(c.expires_at) }}</span>
              </div>
              <div v-if="c.usable" class="ppc__row ppc__actions">
                <a class="pos-btn pos-btn--ghost pos-btn--sm" :href="pc.whatsappUrl(c)" target="_blank" rel="noopener">
                  <i class="fab fa-whatsapp"></i>Enviar
                </a>
                <button type="button" class="pos-btn pos-btn--ghost pos-btn--sm" @click="confirmRevoke(c)">
                  <i class="fas fa-ban"></i>Revocar
                </button>
              </div>
            </li>
          </ul>
          <button v-if="pc.codes.length > 5" type="button" class="pos-btn pos-btn--ghost pos-btn--sm pos-btn--block" @click="showAll = !showAll">
            {{ showAll ? 'Ver menos' : `Ver los ${pc.codes.length}` }}
          </button>
        </template>
      </template>
    </div>
  </section>
</template>

<style scoped>
.ppc__head {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  padding: 0;
  border: none;
  background: transparent;
  color: var(--ds-text);
  font-size: 15px;
  cursor: pointer;
  text-align: left;
}
.ppc__hint { margin: 4px 0 10px; font-size: 12px; }
.ppc__link {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface-2);
}
.ppc__link code { flex: 1; min-width: 0; font-size: 12px; }
.ppc__issued {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  margin-top: 12px;
  padding: 12px;
  border-radius: 12px;
  background: var(--pos-accent-soft);
  text-align: center;
}
.ppc__code { font-size: 26px; letter-spacing: 4px; font-family: ui-monospace, monospace; }
.ppc__list { list-style: none; padding: 0; margin: 0 0 8px; display: flex; flex-direction: column; gap: 8px; }
.ppc__list li { padding: 8px 10px; border-radius: 10px; border: 1px solid var(--ds-border); }
.ppc__row { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 12px; }
.ppc__row + .ppc__row { margin-top: 4px; }
.ppc__actions { justify-content: flex-end; }
</style>
