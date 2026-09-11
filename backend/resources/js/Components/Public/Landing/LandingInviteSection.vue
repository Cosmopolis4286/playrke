<script setup lang="ts">
import type { InvitaContent } from '@js/types/content/landing-section'

defineProps<{
  content: InvitaContent | null
}>()

defineEmits<{ register: [] }>()

const DEFAULT_STEPS = [
  'Crea tu cuenta y obtén tu enlace de invitación personal',
  'Comparte tu enlace con amigos',
  'Cuando se registren y verifiquen su cuenta, ganas XP automáticamente',
]
</script>

<template>
  <div class="verify-grid">
    <div>
      <span class="eyebrow">{{ content?.eyebrow ?? 'Programa de referidos' }}</span>
      <h2 class="verify-title">{{ content?.title ?? 'Invita y gana' }}</h2>
      <p class="verify-sub">
        {{ content?.paragraph ?? 'Comparte tu enlace único cuando crees tu cuenta. Cuando un amigo se registre y verifique su cuenta, ganas XP.' }}
      </p>
      <ul class="verify-steps">
        <li v-for="(step, i) in (content?.steps ?? DEFAULT_STEPS)" :key="i" class="reveal-child" :style="{ '--stagger-index': i }">
          <span class="num">{{ i + 1 }}</span> {{ step }}
        </li>
      </ul>
    </div>
    <div class="invite-card">
      <i class="fas fa-user-plus invite-card__icon" />
      <p class="invite-card__lead">{{ content?.cardLead ?? 'Tu enlace de invitación te espera dentro de tu perfil' }}</p>
      <button type="button" class="btn btn--primary btn--block" @click="$emit('register')">
        {{ content?.ctaLabel ?? 'Crear cuenta gratis' }}
      </button>
    </div>
  </div>
</template>
