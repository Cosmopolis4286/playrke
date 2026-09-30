<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { usePos } from '@/js/composables/games/bingo/card/pos/posContext'
import { whatsappNumber } from '@/js/composables/games/bingo/card/pos/usePosSale'
import { showToast } from '@/js/utils/toastNotify'

/**
 * Cierre de una venta: el vendedor comparte el link con el comprador
 * (copiar o WhatsApp con mensaje listo) y arranca la siguiente venta sin
 * volver a elegir sala ni país.
 */
const { rooms, sale } = usePos()
const result = computed(() => sale.lastSale!)
const copied = ref(false)
const root = ref<HTMLElement | null>(null)

onMounted(() => root.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))

const pending = computed(() => result.value.paymentStatus === 'PENDING')

const message = computed(() => {
  const r = result.value
  const lines = [
    `¡Hola ${r.buyerName.split(' ')[0]}! 🎉 Tus ${r.cardCount} ${r.cardCount === 1 ? 'cartón' : 'cartones'} de Bingo (sala ${r.roomCode}) ya están registrados.`,
  ]
  if (r.playerLink) lines.push(`Entra aquí para verlos y seguir el sorteo en vivo: ${r.playerLink}`)
  if (pending.value) lines.push('Tu pago está en verificación: los cartones aparecerán apenas se confirme. ¡Mucha suerte! 🍀')
  else lines.push('¡Mucha suerte! 🍀')
  return lines.join('\n\n')
})

const whatsappUrl = computed(() => {
  const number = whatsappNumber(result.value.buyerPhone, result.value.countryIso2)
  const text = encodeURIComponent(message.value)
  return number ? `https://wa.me/${number}?text=${text}` : `https://wa.me/?text=${text}`
})

async function copy(text: string, label: string): Promise<void> {
  try {
    await navigator.clipboard.writeText(text)
    copied.value = true
    window.setTimeout(() => { copied.value = false }, 2000)
    showToast(`${label} copiado`, 'success')
  } catch {
    showToast('No se pudo copiar', 'error')
  }
}

function otherRoom(): void {
  sale.startNewSale()
  rooms.selectedRoomId = null
}
</script>

<template>
  <section ref="root" class="pos-card pvs" aria-live="polite">
    <div class="pvs__icon" :class="{ 'pvs__icon--pending': pending }">
      <i :class="pending ? 'fas fa-hourglass-half' : 'fas fa-check'"></i>
    </div>

    <h2 class="pvs__title">¡Venta registrada!</h2>
    <p class="pvs__sub">
      <strong>{{ result.cardCount }} {{ result.cardCount === 1 ? 'cartón' : 'cartones' }}</strong>
      para <strong>{{ result.buyerName }}</strong> · sala {{ result.roomCode }} ·
      <span class="pos-money">{{ result.currencySymbol }} {{ result.total.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
    </p>

    <div v-if="pending" class="pvs__note">
      <i class="fas fa-info-circle"></i>
      <span>
        El pago quedó <strong>en revisión</strong>. El comprador verá sus cartones cuando tu comercio apruebe el pago
        (si fue en efectivo, cuando confirme que recibió el dinero). Te avisaremos cuando se apruebe o se rechace.
        Avísale al comprador para que no se preocupe si aún no aparecen.
      </span>
    </div>

    <div v-if="result.playerLink" class="pvs__link">
      <span class="pos-label">Link del comprador</span>
      <div class="pvs__link-box">
        <input :value="result.playerLink" readonly aria-label="Link del comprador" @focus="($event.target as HTMLInputElement).select()" />
        <button type="button" class="pos-btn pos-btn--ghost pos-btn--sm" @click="copy(result.playerLink!, 'Link')">
          <i :class="copied ? 'fas fa-check' : 'fas fa-copy'"></i>{{ copied ? 'Copiado' : 'Copiar' }}
        </button>
      </div>
    </div>

    <div class="pvs__share">
      <a :href="whatsappUrl" target="_blank" rel="noopener" class="pos-btn pos-btn--whatsapp pos-btn--lg">
        <i class="fab fa-whatsapp"></i>Enviar por WhatsApp
      </a>
      <button type="button" class="pos-btn pos-btn--ghost pos-btn--lg" @click="copy(message, 'Mensaje')">
        <i class="far fa-comment-dots"></i>Copiar mensaje
      </button>
    </div>

    <div class="pvs__next">
      <button type="button" class="pos-btn pos-btn--primary pos-btn--lg" @click="sale.startNewSale()">
        <i class="fas fa-plus"></i>Nueva venta en esta sala
      </button>
      <button type="button" class="pos-btn pos-btn--ghost" @click="otherRoom">
        <i class="fas fa-exchange-alt"></i>Otra sala
      </button>
    </div>
  </section>
</template>

<style scoped>
.pvs { text-align: center; padding: 28px 22px; scroll-margin-top: 90px; }

.pvs__icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 12px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 30px;
  color: #fff;
  background: var(--pos-success);
  box-shadow: 0 0 0 8px var(--pos-success-soft);
  animation: pvs-pop .45s cubic-bezier(.2, 1.4, .4, 1) both;
}
.pvs__icon--pending { background: var(--pos-warning); box-shadow: 0 0 0 8px var(--pos-warning-soft); }
@keyframes pvs-pop { from { transform: scale(.4); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.pvs__title { font-size: 22px; font-weight: 800; margin: 0 0 4px; }
.pvs__sub { color: var(--ds-text-2); margin: 0 0 16px; font-size: 14px; }

.pvs__note {
  display: flex;
  gap: 10px;
  text-align: left;
  font-size: 13px;
  padding: 10px 12px;
  border-radius: 10px;
  background: var(--pos-warning-soft);
  margin: 0 auto 16px;
  max-width: 560px;
}
.pvs__note i { color: var(--pos-warning); margin-top: 2px; }

.pvs__link { max-width: 560px; margin: 0 auto 16px; text-align: left; }
.pvs__link-box {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 6px 6px 6px 12px;
  border-radius: 10px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
}
.pvs__link-box input { flex: 1; min-width: 0; border: none; outline: none; background: transparent; color: var(--ds-text); font-size: 13px; }

.pvs__share, .pvs__next { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
.pvs__next { margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--ds-border); }

@media (prefers-reduced-motion: reduce) { .pvs__icon { animation: none; } }
@media (max-width: 576px) {
  .pvs__share .pos-btn, .pvs__next .pos-btn { width: 100%; }
}
</style>
