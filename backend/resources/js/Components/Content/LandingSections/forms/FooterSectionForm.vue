<script setup lang="ts">
import type { FooterContent } from '@js/types/content/landing-section'

const content = defineModel<FooterContent>({ required: true })
</script>

<template>
  <div class="lsf-field">
    <label>Tagline</label>
    <input v-model="content.tagline" type="text" class="form-control" maxlength="200" />
  </div>
  <div class="lsf-field">
    <label>Facebook</label>
    <input v-model="content.socials.facebook" type="url" class="form-control" maxlength="255" />
  </div>
  <div class="lsf-field">
    <label>Instagram</label>
    <input v-model="content.socials.instagram" type="url" class="form-control" maxlength="255" />
  </div>
  <div class="lsf-field">
    <label>Telegram</label>
    <input v-model="content.socials.telegram" type="url" class="form-control" maxlength="255" />
  </div>
  <div class="lsf-field">
    <label>Sufijo del copyright (después de "PlayRKE © {año}.")</label>
    <input v-model="content.copyrightSuffix" type="text" class="form-control" maxlength="100" />
  </div>
  <div class="lsf-field">
    <label>Línea de confianza</label>
    <input v-model="content.trustLine" type="text" class="form-control" maxlength="150" />
  </div>
</template>
