<script setup lang="ts">
import type { CtaFinalContent } from '@js/types/content/landing-section'

defineProps<{
  content: CtaFinalContent | null
}>()

defineEmits<{ register: [] }>()
</script>

<template>
  <span class="eyebrow eyebrow--center">{{ content?.eyebrow ?? 'Empieza hoy' }}</span>
  <h2>{{ content?.title ?? 'Tu primera sala te espera' }}</h2>
  <p>{{ content?.paragraph ?? 'Crear cuenta toma menos de un minuto. El pozo no espera.' }}</p>
  <button type="button" class="btn btn--primary btn--lg" @click="$emit('register')">
    {{ content?.ctaLabel ?? 'Crear cuenta gratis' }}
  </button>
</template>
