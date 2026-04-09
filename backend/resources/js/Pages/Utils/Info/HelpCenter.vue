<script setup lang="ts">
  import { Head } from '@inertiajs/vue3'
  import { computed } from 'vue'
  import {
    faqGeneral,
    faqVenezuela,
    faqBrasil,
    faqWallet,
    infographics,
    VENEZUELA_COUNTRY_ID,
  } from '@/js/modules/utils/info/helpCenterContent'

  const props = defineProps<{
    title: string
    countryId: number
  }>()

  const faqCountry = computed(() =>
    props.countryId === VENEZUELA_COUNTRY_ID ? faqVenezuela : faqBrasil
  )
</script>

<template>
  <Head :title="title" />

  <div class="hc-wrapper">
    <div class="card hc-main-card">
    <!-- Header -->
    <div class="hc-header mb-4">
      <div class="hc-header-icon">
        <i class="fas fa-circle-question"></i>
      </div>
      <div>
        <h4 class="hc-header-title">Centro de Ayuda</h4>
        <p class="hc-header-sub">Encuentra respuestas a las preguntas más comunes sobre la plataforma.</p>
      </div>
    </div>

    <div class="row g-3">

      <!-- Columna 1: Preguntas generales -->
      <div class="col-12 col-lg-4">
        <div class="card hc-card">
          <div class="card-header hc-card-header">
            <i class="fas fa-message me-2"></i>Preguntas Frecuentes
          </div>
          <div class="card-body p-0 hc-card-body">
            <ul class="hc-faq-list">
              <li v-for="(item, i) in faqGeneral" :key="i" class="hc-faq-item">
                <details>
                  <summary class="hc-faq-question">{{ item.question }}</summary>
                  <p class="hc-faq-answer">{{ item.answer }}</p>
                </details>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Columna 2: País + Wallet -->
      <div class="col-12 col-lg-4">
        <div class="card hc-card">
          <div class="card-header hc-card-header">
            <i class="fas fa-wallet me-2"></i>Pagos y Retiros
          </div>
          <div class="card-body p-0 hc-card-body">
            <ul class="hc-faq-list">
              <li v-for="(item, i) in faqCountry" :key="i" class="hc-faq-item">
                <details>
                  <summary class="hc-faq-question">{{ item.question }}</summary>
                  <p class="hc-faq-answer">{{ item.answer }}</p>
                </details>
              </li>
              <li v-for="(item, i) in faqWallet" :key="`w-${i}`" class="hc-faq-item">
                <details>
                  <summary class="hc-faq-question">{{ item.question }}</summary>
                  <p class="hc-faq-answer">{{ item.answer }}</p>
                </details>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Columna 3: Infografías + soporte -->
      <div class="col-12 col-lg-4 d-flex flex-column gap-3">

        <div class="card hc-card">
          <div class="card-header hc-card-header">
            <i class="fas fa-map me-2"></i>Guías Paso a Paso
          </div>
          <div class="card-body p-0 hc-card-body">
            <ul class="hc-faq-list">
              <li v-for="(item, i) in infographics" :key="i" class="hc-faq-item">
                <details>
                  <summary class="hc-faq-question">{{ item.question }}</summary>
                  <ol class="hc-steps">
                    <li v-for="(step, s) in item.steps" :key="s">{{ step }}</li>
                  </ol>
                </details>
              </li>
            </ul>
          </div>
        </div>

        <!-- Card soporte -->
        <div class="card hc-card hc-support-card">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="hc-support-icon">
              <i class="fa-brands fa-telegram"></i>
            </div>
            <div>
              <p class="hc-support-label mb-1">¿No encontraste tu respuesta?</p>
              <a
                href="https://t.me/+rV3KWBuuKOtjYzkx"
                target="_blank"
                rel="noopener noreferrer"
                class="hc-support-link"
              >
                Únete a la Comunidad PlayRKE <i class="fas fa-arrow-right ms-1" style="font-size:0.7rem"></i>
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>
    </div><!-- /.hc-main-card -->
  </div>
</template>

<style scoped>
  .hc-wrapper {
    max-width: 100%;
    overflow-x: hidden;
  }

  .hc-main-card {
    border-radius: 12px;
    border: 1px solid rgba(128, 128, 128, 0.15);
    box-shadow: 0 1px 6px rgba(0,0,0,0.07);
    padding: 1.25rem 1.5rem;
  }

  /* Header */
  .hc-header {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .hc-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(13, 110, 253, 0.12);
    color: #0d6efd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
  }

  .hc-header-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
  }

  .hc-header-sub {
    font-size: 0.82rem;
    color: #6c757d;
    margin: 0;
  }

  /* Cards */
  .hc-card {
    border-radius: 10px;
    border: 1px solid rgba(128, 128, 128, 0.15);
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    overflow: hidden;
    /* Altura fija para que el expand sea interno */
    display: flex;
    flex-direction: column;
  }

  .hc-card-body {
    overflow-y: auto;
    max-height: 420px;
    /* scrollbar discreta */
    scrollbar-width: thin;
    scrollbar-color: rgba(128,128,128,0.25) transparent;
  }

  .hc-card-body::-webkit-scrollbar {
    width: 4px;
  }

  .hc-card-body::-webkit-scrollbar-thumb {
    background: rgba(128,128,128,0.25);
    border-radius: 4px;
  }

  .hc-card-body::-webkit-scrollbar-track {
    background: transparent;
  }

  .hc-card-header {
    font-size: 0.82rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    padding: 0.65rem 1rem;
    border-bottom: 1px solid rgba(128, 128, 128, 0.12);
    color: #6c757d;
  }

  /* FAQ list */
  .hc-faq-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .hc-faq-item {
    border-bottom: 1px solid rgba(128, 128, 128, 0.08);
  }

  .hc-faq-item:last-child {
    border-bottom: none;
  }

  .hc-faq-item details {
    padding: 0;
  }

  .hc-faq-question {
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    list-style: none;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.65rem 1rem;
    line-height: 1.4;
    color: inherit;
    user-select: none;
    transition: background 0.15s;
  }

  .hc-faq-question::-webkit-details-marker { display: none; }

  .hc-faq-question::before {
    content: '›';
    font-size: 1.1rem;
    line-height: 1.2;
    transition: transform 0.2s;
    flex-shrink: 0;
    color: #0d6efd;
    font-weight: 700;
  }

  .hc-faq-item details[open] .hc-faq-question {
    background: rgba(13, 110, 253, 0.04);
  }

  .hc-faq-item details[open] .hc-faq-question::before {
    transform: rotate(90deg);
  }

  .hc-faq-answer {
    font-size: 0.82rem;
    margin: 0;
    padding: 0 1rem 0.75rem 2.1rem;
    color: #6c757d;
    line-height: 1.6;
  }

  .hc-steps {
    font-size: 0.82rem;
    margin: 0;
    padding: 0 1rem 0.75rem 2.8rem;
    color: #6c757d;
    line-height: 1.7;
  }

  /* Support card */
  .hc-support-card {
    border: 1px solid rgba(13, 110, 253, 0.2);
    background: rgba(13, 110, 253, 0.03);
  }

  .hc-support-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
  }

  .hc-support-label {
    font-size: 0.8rem;
    color: #6c757d;
  }

  .hc-support-link {
    font-size: 0.85rem;
    font-weight: 500;
    color: #0d6efd;
    text-decoration: none;
    transition: opacity 0.18s;
  }

  .hc-support-link:hover {
    opacity: 0.75;
    text-decoration: none;
  }
</style>
