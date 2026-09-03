<template>
    <PageHeader
      icon="fa-info-circle"
      title="Jugadores Externos"
      subtitle="Compradores sin cuenta en la plataforma, captados por un vendedor."
      :breadcrumb-items="breadcrumbItems"
    />

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
              :hierarchy="props.hierarchy"
              @search="handleSearch"
              @search-debounced="handleSearchDebounced"
              @page-change="goToPage"
              @toggle-block="handleToggleBlock"
              @regenerate-link="handleRegenerateLink"
              @reactivate="handleReactivate"
              @convert-to-user="handleConvertToUser"
              @export="handleExport"
            />
          </div>
        </div>
      </div>
    </section>
</template>

<script setup lang="ts">
import ExternalPlayerPanel from '@/js/Components/Roles/Admin/ExternalPlayer/ExternalPlayerPanel.vue'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import { useExternalPlayers } from '@/js/composables/roles/admin/external-player/useExternalPlayers'
import { useToast } from 'vue-toastification'
import { showPasswordModal } from '@/js/helpers/user/password'
import type { ExternalPlayer, ExternalPlayerFilters, ExternalPlayerStatistics, GameOption, HierarchyOptions, PaginatedResult } from '@/js/types/roles/admin/external-player'

const toast = useToast()

const props = defineProps<{
  players: PaginatedResult<ExternalPlayer>
  statistics: ExternalPlayerStatistics
  games: GameOption[]
  hierarchy: HierarchyOptions
}>()

const breadcrumbItems = [
  { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
  { label: 'Usuarios', route: '/admins', icon: 'fas fa-users' },
  { label: 'Jugadores Externos' },
]

const { players, pagination, statistics, loading, filters, fetchPlayers, searchDebounced, goToPage, toggleBlock, regenerateLink, reactivate, convertToUser } = useExternalPlayers({
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

async function handleConvertToUser(playerId: string) {
  try {
    const result = await convertToUser(playerId)
    toast.success(result.message)
    await showPasswordModal(result.temporary_password)
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'No se pudo convertir el jugador')
  }
}

function handleExport() {
  const params = new URLSearchParams(
    Object.entries(filters.value).filter(([, v]) => v !== undefined) as [string, string][]
  )
  window.location.href = `/admin/external-players/export?${params.toString()}`
}
</script>
