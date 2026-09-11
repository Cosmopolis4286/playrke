<script setup lang="ts">
import type { VerificarContent } from '@js/types/content/landing-section'

const content = defineModel<VerificarContent>({ required: true })
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
    <label>Pasos (3)</label>
    <input v-for="(_, i) in content.steps" :key="i" v-model="content.steps[i]" type="text" class="form-control mb-2" maxlength="150" />
  </div>
</template>
