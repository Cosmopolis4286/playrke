<script setup lang="ts">
import { ref } from "vue";

import CardHeader from "@js/Components/Common/Card/CardHeader.vue";
import ExchangeRateButtons from "@js/Components/ExchangeRate/ExchangeRateButtons.vue";
import ExchangeRateTable from "@js/Components/ExchangeRate/ExchangeRateTable.vue";
import { useExchangeRate } from "@js/composables/exchangeRate/useExchangeRate";
import type { ExchangeRatePageProps } from "@js/types/exchange-rate";
import Modal from "@/js/Components/Common/Modal/Modal.vue";
import ExchangeRateForm from "@/js/Components/ExchangeRate/ExchangeRateForm.vue";

/**
 * Props tipadas correctamente para Inertia
 */
const props = defineProps<ExchangeRatePageProps>();

/**
 * Composable
 */
const {
  loading,
  showModalForm,
  submit,
  create,
  edit,
  changeStatus,
  remove,
  updateExchangeRates,
} = useExchangeRate(props.tokenId);

/**
 * Estado local
 */
const selectedCurrency = ref<Array<string>>([""]);

/**
 * Navega al histórico
 */
function goHistorical(): void {
  if (!selectedCurrency.value) return;

  const url = `/exchange-rate/historical/${selectedCurrency.value}?tokenId=${props.tokenId}`;
  window.location.href = url;
}
</script>

<template>
  <div class="col-12">
    <div class="card">
      <CardHeader title="Tasas de Cambio" subtitle="Valores en base a Un Dólar (1 USD)" />

      <div class="card-body">
        <ExchangeRateButtons
          :currencies="currencies"
          :role-user="roleUser"
          :selected-currency="selectedCurrency"
          @create="create"
          @historical="goHistorical"
          @update="updateExchangeRates"
        />

        <ExchangeRateTable
          :exchange-rates="exchangeRates"
          :role-user="roleUser"
          @edit="edit"
          @change-status="changeStatus"
          @delete="remove"
        />
      </div>
    </div>
  </div>
  <Modal
    v-model="showModalForm"
    title="Nueva tasa"
    show-confirm
    :loading="loading"
    :on-submit="submit"
  >
    <ExchangeRateForm
      :currencies="currencies"
      :selected-currency="selectedCurrency"
      :role-user="roleUser"
      :token-id="tokenId"
    />
  </Modal>
</template>
