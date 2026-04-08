<script setup lang="ts">
  import { Head } from '@inertiajs/vue3'
  import { powerOfKnowledgeContent as content } from '@/js/modules/utils/info/powerOfKnowledgeContent'

  defineProps<{
    title: string
    imageUrl: string
    termsUrl?: string
  }>()
</script>

<template>
  <Head :title="title" />

  <div class="info-wrapper">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-9">
        <div class="card mb-3">
          <div class="card-body">

            <div class="d-flex justify-content-center mb-3">
              <img :src="imageUrl" class="pds-img" alt="El Poder del Saber" />
            </div>

            <p class="text-center">{{ content.intro }}</p>

            <hr />

            <h4>{{ content.reglamento.heading }}</h4>
            <p><b>El Poder del Saber</b>, {{ content.reglamento.description }}</p>

            <p v-for="(rule, index) in content.reglamento.rules" :key="index">
              <b>{{ index + 1 }}.</b> {{ rule }}
            </p>

            <hr />

            <h5>{{ content.comodines.heading }}</h5>
            <p v-for="(item, index) in content.comodines.items" :key="index">
              <i :class="item.icon"></i> {{ item.text }}
            </p>
            <p>{{ content.comodines.note }}</p>

            <hr />

            <h6><b>{{ content.howToParticipate.heading }}</b></h6>
            <p>
              {{ content.howToParticipate.text }}
              <template v-if="termsUrl">
                [<a :href="termsUrl">Ver&nbsp;<b>7.3</b></a>]
              </template>
            </p>

            <hr />

            <h6><b>{{ content.prizes.heading }}</b></h6>
            <p>{{ content.prizes.text }}</p>
            <p>{{ content.prizes.note }}</p>

            <hr />

            <div class="mt-4 text-center">
              <span class="end-disclaimer">{{ content.footer.disclaimer }}</span><br />
              <span class="end-updated">{{ content.footer.updatedAt }}</span>
            </div>

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

  .pds-img {
    max-width: 100%;
    height: auto;
  }
</style>
