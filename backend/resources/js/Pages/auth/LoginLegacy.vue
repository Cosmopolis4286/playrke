<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

defineProps<{
  html: string
  register: boolean
}>()
</script>

<template>
  <Head title="Tu Sitio Para Ganar" />
  <div v-html="html" />
</template>
