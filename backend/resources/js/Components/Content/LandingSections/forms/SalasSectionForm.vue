<script setup lang="ts">
import type { SalasContent } from '@js/types/content/landing-section'

const content = defineModel<SalasContent>({ required: true })
</script>

<template>
  <div class="lsf-field">
    <label>Título</label>
    <input v-model="content.title" type="text" class="form-control" maxlength="120" />
  </div>
  <div class="lsf-field">
    <label>Subtítulo</label>
    <input v-model="content.subtitle" type="text" class="form-control" maxlength="200" />
  </div>
  <div class="lsf-field">
    <label>Texto del link "Ver todas las salas"</label>
    <input v-model="content.ctaLabel" type="text" class="form-control" maxlength="40" />
  </div>
</template>
