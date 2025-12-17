<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { termsContent } from '@/modules/shared/utils/info/termsContent'

defineProps<{
  title: string
}>()
</script>

<template>
  <Head :title="title" />

  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="card mb-3">
        <div class="card-body">
          <h2 class="mb-3">{{ termsContent.title }}</h2>

          <!-- Itera sobre las secciones -->
          <div v-for="(section, index) in termsContent.sections" :key="index">
            <h4>{{ section.heading }}</h4>
            <p v-for="(paragraph, index) in section.paragraphs" :key="index" class="mb-3">
              {{ paragraph }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
