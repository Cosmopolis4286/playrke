<template>
  <div :class="containerClass">
    <div class="card">
      <!-- Incluye el header del card con variables claras -->
      <CardHeader :subtitle="subtitle" :btnCardHeader="{
        route: $route('bingo.configuration'),
        icon: 'fa-solid fa-gear'
      }" />
      <div class="card-body">
        <CoinTabs :coins="coins" :currency="selectedCoinId" :routeWithCoin="{
          route: 'bingo.byCoin',
          parameters: { room: 42 }
        }" />
        <BtnGroup :buttons="[
          { color: 'btn-primary', icon: 'fa fa-copy', dataType: 'copy', title: 'Copiar' },
          { color: 'btn-danger', icon: 'fa fa-trash', dataType: 'delete', title: 'Eliminar' }
        ]" name="room" :dataId="room.id" @action="handleButtonAction" />
        <div class="tab-content" id="myTabContent">
          <div class="tab-pane fade show active">
            <TableRooms :rooms="rooms" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import CardHeader from '@/components/utils/CardHeader.vue'
import CoinTabs from '@/components/utils/CoinTabs.vue'
import BtnGroup from '@/components/utils/BtnGroup.vue'
import TableRooms from '@/components/summary/TableRooms.vue'
import { computed } from 'vue'

// Props recibidos
const props = defineProps({
  layout: { type: String, required: true },
  rooms: { type: Array, required: true },
  subtitle: { type: String, default: '' },
  customer: { type: Object, default: null },
})

// Computed para la clase del contenedor según si existe customer
const containerClass = computed(() => (props.customer ? 'col-12 p-2' : 'col-12'))
</script>

<style scoped>
/* Aquí puedes agregar estilos si necesitas */
</style>
