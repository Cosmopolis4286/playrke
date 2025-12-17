<template>
  <ul class="nav nav-tabs mt-n2 mb-2" role="tablist">
    <li class="nav-item" v-for="item in currencies" :key="item.id">
      <a class="nav-link" :class="{ active: isActive(item.id) }" :id="tabId(item)" :href="tabLink(item)" role="tab"
        :aria-controls="tabId(item)" :aria-selected="isActive(item.id) ? 'true' : 'false'">
        {{ getCurrencyLabel(item) }}
      </a>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { computed } from 'vue'

/**
 * Interfaz para un item de moneda
 */
interface CurrencyItem {
  id: number | string;
  encrypted: string;
  iso_code?: string;
  currency?: string;
  name?: string;
}

/**
 * Interfaz para la configuración de ruta con moneda
 */
interface RouteWithCurrency {
  route: string;
  parameters: Record<string, any>;
}

/**
 * Props del componente CurrencyTabs
 */
interface Props {
  currencies: CurrencyItem[];
  currency: number | string;
  routeWithCurrency: RouteWithCurrency;
}

const props = defineProps<Props>();

/**
 * Verifica si la moneda está activa
 * @param id - ID de la moneda
 * @returns true si la moneda está activa
 */
const isActive = (id: number | string): boolean => String(id) === String(props.currency);

/**
 * Genera el ID del tab para una moneda
 * @param item - Item de moneda
 * @returns ID del tab
 */
const tabId = (item: CurrencyItem): string =>
  `${item.id}-${item.iso_code ?? item.currency ?? item.name}`;

/**
 * Obtiene la etiqueta de la moneda
 * @param item - Item de moneda
 * @returns Etiqueta de la moneda
 */
const getCurrencyLabel = (item: CurrencyItem): string =>
  item.iso_code ?? item.currency ?? item.name ?? '';

/**
 * Genera el enlace para el tab de la moneda
 * @param item - Item de moneda
 * @returns URL del enlace
 */
const tabLink = (item: CurrencyItem): string => {
  return window.route(props.routeWithCurrency.route, {
    ...props.routeWithCurrency.parameters,
    currency: item.encrypted
  });
};
</script>
