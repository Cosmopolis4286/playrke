<script setup lang="ts">
/**
 * Se monta una sola vez fuera del árbol Inertia (mismo patrón que
 * PlatformWinTicker: div dedicado en el layout Blade, montado desde
 * global.ts) para mantener sincronizado en tiempo real el contador de
 * "movimientos pendientes" que hoy vive repartido en Blade puro:
 * - el pill/badge del navbar (#nb-pending-pill-wrap, #nb-pending-label, #nb-pending-badge)
 * - el badge numérico junto a "Movimientos" en el sidebar (#sidebar-pending-badge)
 * - el badge agregado en el padre "Billetera" del sidebar (#sidebar-parent-badge-wallet),
 *   visible aunque el submenú esté colapsado (ver sidebar-dynamic.blade.php)
 *
 * Antes de este componente ambos se calculaban una sola vez en el servidor
 * al renderizar el layout y quedaban desactualizados hasta el siguiente
 * full page load — este componente los mantiene vivos escuchando el mismo
 * evento WalletMovementProcessed/Requested que ya usa el dashboard admin.
 */
import { watch } from 'vue'
import { useWalletMovementsRealtime } from '@js/composables/wallet/admin/useWalletMovementsRealtime'

const props = defineProps<{
  initialPendingCount: number
}>()

const { pendingCount } = useWalletMovementsRealtime(props.initialPendingCount)

function updateNavbar(count: number): void {
  const pillWrap   = document.getElementById('nb-pending-pill-wrap')
  const pillWrapSm = document.getElementById('nb-pending-pill-wrap-sm')
  const label      = document.getElementById('nb-pending-label')
  const badge      = document.getElementById('nb-pending-badge')

  if (pillWrap)   pillWrap.style.display   = count > 0 ? '' : 'none'
  if (pillWrapSm) pillWrapSm.style.display = count > 0 ? '' : 'none'
  if (label)      label.textContent        = `${count} pendiente${count === 1 ? '' : 's'}`
  if (badge)       badge.textContent        = count > 99 ? '99+' : String(count)
}

function updateSidebar(count: number): void {
  const badge = document.getElementById('sidebar-pending-badge')
  if (badge) {
    badge.textContent = count > 99 ? '99+' : String(count)
    badge.style.display = count > 0 ? '' : 'none'
  }

  // Badge agregado del padre "Billetera" — hoy 'movements' es el único
  // subitem de wallet con badge_counter, así que el total del padre
  // coincide 1:1 con este mismo conteo (ver SidebarMenuService::generateMenu()).
  const parentBadge = document.getElementById('sidebar-parent-badge-wallet')
  if (parentBadge) {
    parentBadge.textContent = count > 99 ? '99+' : String(count)
    parentBadge.style.display = count > 0 ? '' : 'none'
  }
}

watch(pendingCount, count => {
  updateNavbar(count)
  updateSidebar(count)
}, { immediate: true })
</script>

<template>
  <!-- Sin UI propia: solo sincroniza el DOM Blade del navbar/sidebar ya existente. -->
  <span style="display: none" aria-hidden="true"></span>
</template>
