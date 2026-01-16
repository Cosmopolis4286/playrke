<script setup lang="ts">
  import { Head } from '@inertiajs/vue3'
  import { aboutContent } from '@/js/modules/utils/info/aboutContent'

  defineProps<{
    title: string
  }>()
</script>

<template>
  <Head :title="title" />

  <div class="info-wrapper">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-9">
        <div class="card mb-3">
          <div class="card-body">
            <h2 class="mb-3">{{ aboutContent.title }}</h2>

            <p v-for="(paragraph, index) in aboutContent.paragraphs" :key="index" class="mb-3">
              {{ paragraph }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
  .info-wrapper {
    max-width: 100%;
    overflow-x: hidden;
  }
</style>
