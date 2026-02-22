<script setup lang="ts">
  import { ref, reactive, computed, watch } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BingoCardGenerateForm from '@/js/Components/Games/Bingo/Card/BingoCardGenerateForm.vue'
  // Composables
    import { useBingoRoom } from '@/js/composables/games/bingo/room/useBingoRoom'


  import axios from 'axios'

  /**
   * Props recibidas desde Laravel (Inertia o blade -> json)
   */
  const props = defineProps<{
    rooms: any[]
    sellerCommn: number
  }>()

  // ---------------------
  // Estado principal
  // ---------------------

  const selectedRoom = ref<any>(null)
  const numCtns = ref(1)
  const typeSale = ref('currency')
  const selectedCountry = ref('')
  const playerInGame = ref(false)

  const loading = ref(false)

  // ---------------------
  // Jugador
  // ---------------------

  const player = reactive({
    name: '',
    lastName: '',
    phone: ''
  })

  // ---------------------
  // Computed
  // ---------------------

  const currencySymbol = computed(() => {
    return selectedRoom.value?.currency_symbol ?? '$'
  })

  const roomCtnPrice = computed(() => {
    return Number(selectedRoom.value?.ctn_price ?? 0)
  })

  const prizePool = computed(() => {
    return Number(selectedRoom.value?.prize_pool ?? 0)
  })

  const totalAmount = computed(() => {
    return roomCtnPrice.value * numCtns.value
  })

  const sellerCommission = computed(() => {
    return (totalAmount.value * props.sellerCommn) / 100
  })

  const commerceAmount = computed(() => {
    return totalAmount.value - sellerCommission.value
  })

  // ---------------------
  // Watchers
  // ---------------------

  watch(selectedRoom, () => {
    numCtns.value = 1
  })

  // ---------------------
  // Métodos
  // ---------------------

  const generateCarton = async () => {
    if (!selectedRoom.value) return

    loading.value = true

    try {
      await axios.post('/seller/generateCarton', {
        room: selectedRoom.value.id,
        num_ctns: numCtns.value,
        type_sale: typeSale.value,
        country: selectedCountry.value,
        player
      })

      alert('Cartones generados correctamente')
    } catch (e) {
      console.error(e)
      alert('Error al generar cartones')
    } finally {
      loading.value = false
    }
  }
</script>

<template>
  <div class="card">
    <CardHeader title="Generar Cartones" />
    <div class="card-body">
      <BingoCardGenerateForm />
    </div>
  </div>
</template>
