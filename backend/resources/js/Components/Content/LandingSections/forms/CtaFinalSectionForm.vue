<script setup lang="ts">
import type { CtaFinalContent } from '@js/types/content/landing-section'

const content = defineModel<CtaFinalContent>({ required: true })
</script>

<template>
  <div class="lsf-field">
    <label>Eyebrow</label>
    <input v-model="content.eyebrow" type="text" class="form-control" maxlength="60" />
  </div>
  <div class="lsf-field">
    <label>Título</label>
    <input v-model="content.title" type="text" class="form-control" maxlength="150" />
  </div>
  <div class="lsf-field">
    <label>Párrafo</label>
    <textarea v-model="content.paragraph" class="form-control" rows="3" maxlength="400"></textarea>
  </div>
  <div class="lsf-field">
    <label>Botón</label>
    <input v-model="content.ctaLabel" type="text" class="form-control" maxlength="40" />
  </div>
</template>
