<script setup lang="ts">
import type { HeroContent } from '@js/types/content/landing-section'

const content = defineModel<HeroContent>({ required: true })
</script>

<template>
  <div class="lsf-field">
    <label>Texto antes del número (jugadores online)</label>
    <input v-model="content.eyebrowPrefix" type="text" class="form-control" maxlength="60" />
  </div>
  <div class="lsf-field">
    <label>Texto después del número</label>
    <input v-model="content.eyebrowSuffix" type="text" class="form-control" maxlength="60" />
  </div>
  <div class="lsf-field">
    <label>Título (admite &lt;br/&gt; y &lt;em&gt;)</label>
    <input v-model="content.title" type="text" class="form-control" maxlength="150" />
  </div>
  <div class="lsf-field">
    <label>Subtítulo</label>
    <textarea v-model="content.subtitle" class="form-control" rows="3" maxlength="300"></textarea>
  </div>
  <div class="lsf-field">
    <label>Botón principal</label>
    <input v-model="content.ctaPrimaryLabel" type="text" class="form-control" maxlength="40" />
  </div>
  <div class="lsf-field">
    <label>Botón secundario</label>
    <input v-model="content.ctaSecondaryLabel" type="text" class="form-control" maxlength="40" />
  </div>
  <div class="lsf-field">
    <label>Frases de confianza (3)</label>
    <input v-for="(_, i) in content.trustItems" :key="i" v-model="content.trustItems[i]" type="text" class="form-control mb-2" maxlength="60" />
  </div>
</template>
