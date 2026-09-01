<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import GuestLayout from '@js/Layouts/GuestLayout.vue'
import type { CarouselSlide } from '@js/Layouts/GuestLayout.vue'
import backendClient from '@/js/plugins/axios'
import { useTurnstile } from '@js/composables/common/useTurnstile'

interface CardPrize {
  type_label:   string
  status_label: string
  amount_usd:   number
}

interface CardResult {
  serial_number: string
  card_numbers:  Record<string, (number | null)[]>
  status_label:  string
  room: { name: string | null; date: string | null; time: string | null }
  prize: CardPrize | null
}

const slides: CarouselSlide[] = [
  {
    icon: 'fa-solid fa-ticket',
    title: 'Verifica tu cartón',
    desc: 'Confirma tus números, el estado de tu cartón y si ganaste un premio, sin necesidad de iniciar sesión.',
  },
]

const serialNumber = ref('')
const phoneLast4   = ref('')
const loading       = ref(false)
const errorMessage  = ref<string | null>(null)
const result        = ref<CardResult | null>(null)

const { siteKey, token: turnstileToken, render: renderTurnstile, reset: resetTurnstile } = useTurnstile()
const turnstileContainer = ref<HTMLElement | null>(null)

onMounted(async () => {
  // Precarga desde la landing pública (Public/Landing.vue construye
  // /verify-card?serial=X&phone=Y) — sin esto los campos quedaban siempre
  // vacíos aunque el usuario ya hubiera ingresado los valores ahí.
  const params = new URLSearchParams(window.location.search)
  const serialParam = params.get('serial')
  const phoneParam = params.get('phone')
  if (serialParam) serialNumber.value = serialParam
  if (phoneParam) phoneLast4.value = phoneParam

  await nextTick()
  if (turnstileContainer.value) renderTurnstile(turnstileContainer.value)
})

async function submit() {
  if (siteKey && !turnstileToken.value) {
    errorMessage.value = 'Completa la verificación de seguridad.'
    return
  }

  errorMessage.value = null
  result.value = null
  loading.value = true

  try {
    const { data } = await backendClient.post('/api/bingo/verify-card', {
      serial_number: serialNumber.value.trim(),
      phone_last4:   phoneLast4.value.trim(),
      'cf-turnstile-response': turnstileToken.value,
    })
    result.value = data.card
  } catch (e: any) {
    errorMessage.value = e?.response?.data?.message ?? 'No se pudo verificar el cartón. Intenta de nuevo.'
  } finally {
    loading.value = false
    resetTurnstile()
  }
}
</script>

<template>
  <GuestLayout title="Verificar cartón" :slides="slides">
    <template #tagline>Transparencia total</template>

    <template #form-content>
      <form novalidate @submit.prevent="submit" class="auth-form">
        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-ticket" aria-hidden="true"></i>
          <input
            v-model.trim="serialNumber"
            type="text"
            class="auth-form__input"
            placeholder="Número de serial del cartón"
            aria-label="Número de serial del cartón"
            required
            :disabled="loading"
          />
        </fieldset>

        <fieldset class="auth-form__field">
          <i class="auth-form__icon fa-solid fa-phone" aria-hidden="true"></i>
          <input
            v-model.trim="phoneLast4"
            type="text"
            inputmode="numeric"
            maxlength="4"
            class="auth-form__input"
            placeholder="Últimos 4 dígitos de tu teléfono"
            aria-label="Últimos 4 dígitos de tu teléfono"
            required
            :disabled="loading"
          />
        </fieldset>

        <div v-if="siteKey" ref="turnstileContainer" class="auth-form__turnstile"></div>

        <p v-if="errorMessage" class="auth-form__error">{{ errorMessage }}</p>

        <button type="submit" class="auth-form__submit" :disabled="loading || (!!siteKey && !turnstileToken)">
          {{ loading ? 'Verificando…' : 'Verificar cartón' }}
        </button>
      </form>

      <div v-if="result" class="verify-card__result">
        <h3>Cartón #{{ result.serial_number }}</h3>
        <p>Estado: <strong>{{ result.status_label }}</strong></p>
        <p v-if="result.room.date">Sala: {{ result.room.date }} <template v-if="result.room.time">a las {{ result.room.time }}</template></p>

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
          🏆 Ganaste: {{ result.prize.type_label }} — ${{ result.prize.amount_usd.toFixed(2) }}
          ({{ result.prize.status_label }})
        </div>
      </div>
    </template>
  </GuestLayout>
</template>

<style scoped>
.verify-card__result {
  margin-top: 1.5rem;
  text-align: left;
}
.verify-card__matrix {
  width: 100%;
  border-collapse: collapse;
  margin-top: 0.75rem;
}
.verify-card__matrix th,
.verify-card__matrix td {
  border: 1px solid rgba(255, 255, 255, 0.15);
  padding: 0.4rem;
  text-align: center;
}
.verify-card__prize {
  margin-top: 1rem;
  font-weight: 600;
}
</style>
