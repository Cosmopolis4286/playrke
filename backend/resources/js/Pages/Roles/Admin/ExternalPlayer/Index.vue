<template>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold">Jugadores Externos</h1>
            <p class="text-muted mb-0 mt-1">
              <i class="fas fa-info-circle text-primary mr-1"></i>
              Compradores de cartones de Bingo sin cuenta en la plataforma, captados por un vendedor.
            </p>
          </div>
          <div class="col-sm-6">
            <Breadcrumb :items="breadcrumbItems" />
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <ExternalPlayerPanel
              :players="players"
              :statistics="statistics"
              :loading="loading"
              @search="handleSearch"
              @toggle-block="handleToggleBlock"
            />
          </div>
        </div>
      </div>
    </section>
</template>

<script setup lang="ts">
import ExternalPlayerPanel from '@/js/Components/Roles/Admin/ExternalPlayer/ExternalPlayerPanel.vue'
import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'
import { useExternalPlayers } from '@/js/composables/roles/admin/external-player/useExternalPlayers'
import Swal from 'sweetalert2'
import type { ExternalPlayer, ExternalPlayerStatistics } from '@/js/types/roles/admin/external-player'

const props = defineProps<{
  players: ExternalPlayer[]
  statistics: ExternalPlayerStatistics
}>()

const breadcrumbItems = [
  { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
  { label: 'Usuarios', route: '/admins', icon: 'fas fa-users' },
  { label: 'Jugadores Externos' },
]

const { players, statistics, loading, fetchPlayers, toggleBlock } = useExternalPlayers({
  players: props.players,
  statistics: props.statistics,
})

async function handleSearch(filters: { search: string; status: string }) {
  await fetchPlayers({
    search: filters.search || undefined,
    status: filters.status || undefined,
  })
}

async function handleToggleBlock(playerId: string) {
  try {
    await toggleBlock(playerId)
    Swal.fire({ icon: 'success', title: 'Éxito', text: 'Jugador actualizado correctamente', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
  } catch {
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el jugador' })
  }
}
</script>
