<script setup lang="ts">
import { IMaskDirective } from 'vue-imask'
import { SERIAL_MASK, PHONE_LAST4_MASK } from '@js/composables/public/verifyCardSchema'
import type { VerificarContent } from '@js/types/content/landing-section'

defineProps<{
  content: VerificarContent | null
  cardSerial: string
  cardPhone: string
  verifyErrors: { serial?: string; phone?: string }
}>()

const emit = defineEmits<{
  'serial-accept': [event: CustomEvent]
  'phone-accept': [event: CustomEvent]
  submit: []
}>()

const vImask = IMaskDirective

const DEFAULT_STEPS = [
  'Ingresa el serial impreso en tu cartón',
  'Ingresa los últimos 4 dígitos de tu teléfono',
  'Ve el estado, la sala y el resultado al instante',
]
</script>

<template>
  <div class="verify-grid">
    <div>
      <span class="eyebrow">{{ content?.eyebrow ?? 'Sin necesidad de cuenta' }}</span>
      <h2 class="verify-title">{{ content?.title ?? 'Verifica tu cartón como un ticket físico' }}</h2>
      <p class="verify-sub">
        {{ content?.paragraph ?? '¿Compraste un cartón por WhatsApp o en punto físico? Confírmalo en segundos con el número de serial y los últimos 4 dígitos de tu teléfono.' }}
      </p>
      <ul class="verify-steps">
        <li v-for="(step, i) in (content?.steps ?? DEFAULT_STEPS)" :key="i" class="reveal-child" :style="{ '--stagger-index': i }">
          <span class="num">{{ i + 1 }}</span> {{ step }}
        </li>
      </ul>
    </div>
    <form class="verify-form" novalidate @submit.prevent="emit('submit')">
      <label for="landing-serial">Serial del cartón</label>
      <input
        id="landing-serial"
        v-imask="SERIAL_MASK"
        :value="cardSerial"
        type="text"
        placeholder="BINGO-A1B2C3D4E5F67"
        autocomplete="off"
        :aria-invalid="!!verifyErrors.serial"
        :class="{ 'verify-form__input--error': verifyErrors.serial }"
        @accept="emit('serial-accept', $event as CustomEvent)"
      />
      <p v-if="verifyErrors.serial" class="verify-form__error">{{ verifyErrors.serial }}</p>

      <label for="landing-phone">Últimos 4 dígitos del teléfono</label>
      <input
        id="landing-phone"
        v-imask="PHONE_LAST4_MASK"
        :value="cardPhone"
        type="text"
        inputmode="numeric"
        placeholder="••••"
        autocomplete="off"
        :aria-invalid="!!verifyErrors.phone"
        :class="{ 'verify-form__input--error': verifyErrors.phone }"
        @accept="emit('phone-accept', $event as CustomEvent)"
      />
      <p v-if="verifyErrors.phone" class="verify-form__error">{{ verifyErrors.phone }}</p>

      <button type="submit" class="btn btn--primary btn--block">Verificar cartón</button>
    </form>
  </div>
</template>
