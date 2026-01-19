<script setup lang="ts">
import Modal from "@/js/Components/Common/Modal/Modal.vue";
import ExchangeRateForm from "./ExchangeRateForm.vue";
import type { Currency } from "@/js/types/currency";

const props = defineProps<{
  modelValue: boolean;
  currencies: Currency[];
  selectedCurrency: Array<string>;
  roleUser: string;
  tokenId?: string;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
}>();
</script>

<template>
  <Modal
    v-model="props.modelValue"
    title="Nueva tasa de cambio"
    subtitle="Valores en base a 1 USD"
    size="md"
    @close="emit('update:modelValue', false)"
  >
    <ExchangeRateForm
      :currencies="props.currencies"
      :selected-currency="props.selectedCurrency"
      :role-user="props.roleUser"
      :token-id="props.tokenId"
    />
  </Modal>
</template>
