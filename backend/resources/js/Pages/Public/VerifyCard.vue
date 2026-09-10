<script setup lang="ts">
import { computed, onMounted, nextTick, ref } from 'vue'
import { useToast } from 'vue-toastification'
import GuestLayout from '@js/Layouts/GuestLayout.vue'
import type { CarouselSlide } from '@js/Layouts/GuestLayout.vue'
import AuthFormField from '@js/Components/auth/AuthFormField.vue'
import { useTurnstile } from '@js/composables/common/useTurnstile'
import { useVerifyCard } from '@js/composables/public/useVerifyCard'
import { SERIAL_MASK, PHONE_LAST4_MASK } from '@js/composables/public/verifyCardSchema'

// BingoCard::CARD_STATUSES: 1=Activo, 2=Inactivo, 3=Ganador, 4=Cancelado.
// Mapear por el número (no parseando status_label en español) es más
// estable ante cambios de copy del backend.
const STATUS_BADGE_CLASS: Record<number, string> = {
  1: 'verify-card__status-badge--active',
  2: 'verify-card__status-badge--neutral',
  3: 'verify-card__status-badge--winner',
  4: 'verify-card__status-badge--neutral',
}

const slides: CarouselSlide[] = [
  {
    icon: 'fa-solid fa-ticket',
    title: 'Verifica tu cartón',
    desc: 'Confirma tus números, el estado de tu cartón y si ganaste un premio, sin necesidad de iniciar sesión.',
  },
]

const toast = useToast()
const { form, result, loading, isBusy, markTouched, fieldError, submit: submitVerify } = useVerifyCard()

const statusBadgeClass = computed(() =>
  result.value ? STATUS_BADGE_CLASS[result.value.status] ?? 'verify-card__status-badge--neutral' : ''
)

const { siteKey, token: turnstileToken, render: renderTurnstile, reset: resetTurnstile } = useTurnstile()
const turnstileContainer = ref<HTMLElement | null>(null)

onMounted(async () => {
  // Precarga desde la landing pública (Public/Landing.vue construye
  // /verify-card?serial=X&phone=Y) — sin esto los campos quedaban siempre
  // vacíos aunque el usuario ya hubiera ingresado los valores ahí.
  const params = new URLSearchParams(window.location.search)
  const serialParam = params.get('serial')
  const phoneParam = params.get('phone')
  if (serialParam) form.serial_number = serialParam
  if (phoneParam) form.phone_last4 = phoneParam

  await nextTick()
  if (turnstileContainer.value) renderTurnstile(turnstileContainer.value)
})

async function submit() {
  if (siteKey && !turnstileToken.value) {
    toast.warning('Completa la verificación de seguridad.')
    return
  }
  await submitVerify(turnstileToken.value || undefined)
  resetTurnstile()
}
</script>

<template>
  <GuestLayout title="Verificar cartón" :slides="slides" tagline-icon="fa-ticket">
    <template #tagline>Transparencia total</template>

    <template #form-content>
      <form novalidate @submit.prevent="submit" class="auth-form">
        <AuthFormField
          v-model="form.serial_number"
          icon="fa-ticket"
          placeholder="BINGO-A1B2C3D4E5F67"
          :mask="SERIAL_MASK"
          required
          :disabled="isBusy"
          :error="fieldError('serial_number')"
          @input="markTouched('serial_number')"
        />

        <AuthFormField
          v-model="form.phone_last4"
          type="tel"
          icon="fa-phone"
          placeholder="Últimos 4 dígitos de tu teléfono"
          :mask="PHONE_LAST4_MASK"
          required
          :disabled="isBusy"
          :error="fieldError('phone_last4')"
          @input="markTouched('phone_last4')"
        />

        <div v-if="siteKey" ref="turnstileContainer" class="auth-form__turnstile"></div>

        <button type="submit" class="auth-form__submit" :disabled="isBusy || (!!siteKey && !turnstileToken)">
          <span class="auth-form__submit-text">{{ loading ? 'Verificando…' : 'Verificar cartón' }}</span>
          <span v-if="loading" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </button>
      </form>

      <div v-if="result" class="verify-card__result">
        <div class="verify-card__result-head">
          <h3 class="verify-card__title">Cartón #{{ result.serial_number }}</h3>
          <span class="verify-card__status-badge" :class="statusBadgeClass">{{ result.status_label }}</span>
        </div>
        <p v-if="result.room.date" class="verify-card__room">
          Sala: {{ result.room.date }} <template v-if="result.room.time">a las {{ result.room.time }}</template>
        </p>

        <table class="verify-card__matrix">
          <thead>
            <tr>
              <th v-for="col in Object.keys(result.card_numbers)" :key="col">{{ col }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rowIdx in Math.max(...Object.values(result.card_numbers).map(c => c.length))" :key="rowIdx">
              <td v-for="col in Object.keys(result.card_numbers)" :key="col">
                {{ result.card_numbers[col]?.[rowIdx - 1] ?? '' }}
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="result.prize" class="verify-card__prize">
          <i class="fas fa-trophy verify-card__prize-icon" aria-hidden="true"></i>
          <div class="verify-card__prize-text">
            <span class="verify-card__prize-label">{{ result.prize.type_label }} · {{ result.prize.status_label }}</span>
            <span class="verify-card__prize-amount">${{ result.prize.amount_usd.toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </template>
  </GuestLayout>
</template>

<style scoped>
.verify-card__result {
  margin-top: 1.25rem;
  padding: 18px;
  border-radius: 16px;
  text-align: left;
  background: linear-gradient(160deg, rgba(123,79,255,0.06) 0%, rgba(10,15,36,0.4) 100%);
  border: 1px solid rgba(255,255,255,0.1);
  animation: verify-result-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes verify-result-in {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

.verify-card__result-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
}
.verify-card__title {
  margin: 0;
  font-family: var(--font-heading);
  font-size: 1.05em;
  font-weight: 700;
  color: var(--white);
}

.verify-card__status-badge {
  flex-shrink: 0;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 0.68em;
  font-weight: 700;
  letter-spacing: 0.3px;
  text-transform: uppercase;
}
.verify-card__status-badge--winner {
  background: rgba(42,245,152,0.14);
  color: var(--mint);
  border: 1px solid rgba(42,245,152,0.35);
}
.verify-card__status-badge--active {
  background: rgba(0,158,253,0.14);
  color: var(--blue-accent);
  border: 1px solid rgba(0,158,253,0.35);
}
.verify-card__status-badge--neutral {
  background: rgba(255,255,255,0.06);
  color: var(--gray-2);
  border: 1px solid rgba(255,255,255,0.14);
}

.verify-card__room {
  margin: 6px 0 0;
  font-size: 0.78em;
  color: var(--gray-2);
}

.verify-card__matrix {
  width: 100%;
  border-collapse: separate;
  border-spacing: 3px;
  margin-top: 0.85rem;
}
.verify-card__matrix th {
  padding: 6px 0;
  font-family: var(--font-heading);
  font-size: 0.72em;
  font-weight: 700;
  color: var(--mint);
  background: linear-gradient(160deg, rgba(42,245,152,0.10), rgba(0,158,253,0.06));
  border-radius: 6px;
}
.verify-card__matrix td {
  padding: 8px 0;
  text-align: center;
  font-size: 0.82em;
  font-weight: 600;
  color: var(--gray-1);
  background: rgba(255,255,255,0.03);
  border-radius: 6px;
}

.verify-card__prize {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 1rem;
  padding: 14px 16px;
  border-radius: 14px;
  background: linear-gradient(135deg, var(--mint) 0%, var(--blue-accent) 100%);
  box-shadow: 0 0 30px rgba(42,245,152,0.25);
}
.verify-card__prize-icon {
  font-size: 1.6em;
  color: #06121a;
  flex-shrink: 0;
}
.verify-card__prize-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.verify-card__prize-label {
  font-size: 0.7em;
  font-weight: 700;
  letter-spacing: 0.2px;
  color: rgba(6,18,26,0.75);
  text-transform: uppercase;
}
.verify-card__prize-amount {
  font-family: var(--font-heading);
  font-size: 1.3em;
  font-weight: 800;
  color: #06121a;
}

@media (prefers-reduced-motion: reduce) {
  .verify-card__result { animation: none; }
}
</style>
