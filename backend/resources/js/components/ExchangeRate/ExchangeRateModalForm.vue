<script setup lang="ts">
  import Modal from '../Common/Modal/Modal.vue'
  import ExchangeRateForm from './ExchangeRateForm.vue';

  const props = defineProps<{
    currencies: Currency[]
    selectedCurrency: Array<string>
    tokenId?: string
  }>()
</script>

<template>
  <Modal

  />
</template>
