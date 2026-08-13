<template>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold">Jugadores Externos</h1>
            <p class="text-muted mb-0 mt-1">
              <i class="fas fa-info-circle text-primary mr-1"></i>
              Compradores sin cuenta en la plataforma, captados por un vendedor.
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
              :pagination="pagination"
              :loading="loading"
              :games="props.games"
              @search="handleSearch"
              @search-debounced="handleSearchDebounced"
              @page-change="goToPage"
              @toggle-block="handleToggleBlock"
              @regenerate-link="handleRegenerateLink"
              @reactivate="handleReactivate"
              @export="handleExport"
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
import { useToast } from 'vue-toastification'
import type { ExternalPlayer, ExternalPlayerFilters, ExternalPlayerStatistics, GameOption, PaginatedResult } from '@/js/types/roles/admin/external-player'

const toast = useToast()

const props = defineProps<{
  players: PaginatedResult<ExternalPlayer>
  statistics: ExternalPlayerStatistics
  games: GameOption[]
}>()

const breadcrumbItems = [
  { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
  { label: 'Usuarios', route: '/admins', icon: 'fas fa-users' },
  { label: 'Jugadores Externos' },
]

const { players, pagination, statistics, loading, filters, fetchPlayers, searchDebounced, goToPage, toggleBlock, regenerateLink, reactivate } = useExternalPlayers({
  players: props.players,
  statistics: props.statistics,
})

async function handleSearch(newFilters: ExternalPlayerFilters) {
  await fetchPlayers(newFilters)
}

function handleSearchDebounced(newFilters: ExternalPlayerFilters) {
  searchDebounced(newFilters)
}

async function handleToggleBlock(playerId: string) {
  try {
    await toggleBlock(playerId)
    toast.success('Jugador actualizado correctamente')
  } catch {
    toast.error('No se pudo actualizar el jugador')
  }
}

async function handleRegenerateLink(playerId: string) {
  try {
    await regenerateLink(playerId)
    toast.success('Link regenerado correctamente')
  } catch {
    toast.error('No se pudo regenerar el link')
  }
}

async function handleReactivate(playerId: string) {
  try {
    await reactivate(playerId)
    toast.success('Jugador reactivado correctamente')
  } catch {
    toast.error('No se pudo reactivar el jugador')
  }
}

function handleExport() {
  const params = new URLSearchParams(
    Object.entries(filters.value).filter(([, v]) => v !== undefined) as [string, string][]
  )
  window.location.href = `/admin/external-players/export?${params.toString()}`
}
</script>
