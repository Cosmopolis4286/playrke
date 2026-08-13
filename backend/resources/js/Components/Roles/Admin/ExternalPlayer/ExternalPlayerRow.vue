<template>
  <div class="ep-row" :class="`is-${player.status.toLowerCase()}`">
    <button
      v-tooltip="expanded ? 'Ocultar detalle' : 'Ver detalle'"
      class="ep-row-toggle"
      @click="expanded = !expanded"
    >
      <i class="fas fa-chevron-right" :class="{ 'is-open': expanded }"></i>
    </button>

    <div class="ep-row-main">
      <span class="badge ep-status-badge" :class="statusBadgeClass">
        <i class="fas" :class="statusIcon"></i> {{ statusLabel }}
      </span>
      <div class="ep-row-info">
        <span class="ep-name">{{ player.first_name }} {{ player.last_name }}</span>
        <span class="ep-meta">
          <i class="fas fa-phone-alt mr-1"></i>{{ player.phone }}
          <template v-if="player.identification">
            <span class="mx-1">·</span><i class="fas fa-id-card mr-1"></i>{{ player.identification }}
          </template>
        </span>
      </div>
    </div>

    <div class="ep-row-extra">
      <span v-if="player.game" class="ep-tag ep-tag--game">
        <i class="fas" :class="gameIcon"></i>{{ formattedGameName }}
      </span>
      <span v-if="player.country" class="ep-tag"><i class="fas fa-globe-americas mr-1"></i>{{ player.country.name }}</span>
      <span
        v-if="player.expiration_date"
        v-tooltip="'Expira: ' + formatFullDate(player.expiration_date)"
        class="ep-tag"
        :class="{ 'ep-tag--warning': isExpiringSoon }"
      >
        <i class="fas fa-clock mr-1"></i>{{ formattedExpiration }}
      </span>
    </div>

    <div class="ep-row-actions">
      <VDropdown placement="top" :distance="8">
        <button class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-link"></i>
        </button>
        <template #popper>
          <div class="ep-link-popover">
            <code class="ep-link-popover-value">{{ player.link }}</code>
            <button class="btn btn-sm btn-primary" @click="copyLink">
              <i class="fas" :class="copied ? 'fa-check' : 'fa-copy'"></i>
              {{ copied ? 'Copiado' : 'Copiar' }}
            </button>
          </div>
        </template>
      </VDropdown>
      <button
        v-if="player.status === 'INACTIVE'"
        v-tooltip="'Reactivar jugador expirado y renovar su link'"
        class="btn btn-sm btn-outline-primary"
        :disabled="busy"
        @click="$emit('reactivate', player)"
      >
        <i class="fas fa-rotate-right"></i> Reactivar
      </button>
      <button
        v-if="player.status !== 'BLOCKED'"
        v-tooltip="'Invalida el link actual y genera uno nuevo, sin bloquear al jugador'"
        class="btn btn-sm btn-outline-secondary"
        :disabled="busy"
        @click="$emit('regenerate-link', player)"
      >
        <i class="fas fa-rotate"></i> Regenerar link
      </button>
      <button
        class="btn btn-sm"
        :class="player.status === 'BLOCKED' ? 'btn-outline-success' : 'btn-outline-danger'"
        :disabled="busy"
        @click="$emit('toggle-block', player)"
      >
        <i class="fas" :class="player.status === 'BLOCKED' ? 'fa-unlock' : 'fa-ban'"></i>
        {{ player.status === 'BLOCKED' ? 'Desbloquear' : 'Bloquear' }}
      </button>
    </div>

    <Transition name="ep-detail">
      <div v-if="expanded" class="ep-row-detail">
        <div class="ep-detail-item">
          <span class="ep-detail-label">Vendedor</span>
          <span class="ep-detail-value">
            {{ player.seller_user ? `${player.seller_user.first_name} ${player.seller_user.last_name} (${player.seller_user.email})` : '—' }}
          </span>
        </div>
        <div class="ep-detail-item">
          <span class="ep-detail-label">Link de acceso</span>
          <code class="ep-detail-value ep-detail-link">{{ player.link }}</code>
        </div>
        <div class="ep-detail-item">
          <span class="ep-detail-label">Registrado</span>
          <span class="ep-detail-value">{{ player.created_at ? formatFullDate(player.created_at) : '—' }}</span>
        </div>
        <div class="ep-detail-item">
          <span class="ep-detail-label">Expira</span>
          <span class="ep-detail-value">{{ player.expiration_date ? formatFullDate(player.expiration_date) : '—' }}</span>
        </div>
        <div class="ep-detail-item">
          <span class="ep-detail-label">Código de compra</span>
          <code class="ep-detail-value ep-detail-link">{{ player.purchase_code?.purchase_code ?? '—' }}</code>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import type { ExternalPlayer } from '@/js/types/roles/admin/external-player'

const toast = useToast()

const props = defineProps<{
  player: ExternalPlayer
  busy: boolean
}>()

defineEmits<{
  (e: 'toggle-block', player: ExternalPlayer): void
  (e: 'regenerate-link', player: ExternalPlayer): void
  (e: 'reactivate', player: ExternalPlayer): void
}>()

const expanded = ref(false)
const copied = ref(false)

const STATUS_LABELS: Record<ExternalPlayer['status'], string> = {
  ACTIVE: 'Activo',
  INACTIVE: 'Inactivo',
  BLOCKED: 'Bloqueado',
}

const STATUS_BADGE_CLASSES: Record<ExternalPlayer['status'], string> = {
  ACTIVE: 'badge-success',
  INACTIVE: 'badge-secondary',
  BLOCKED: 'badge-danger',
}

const STATUS_ICONS: Record<ExternalPlayer['status'], string> = {
  ACTIVE: 'fa-circle-check',
  INACTIVE: 'fa-circle-minus',
  BLOCKED: 'fa-ban',
}

// Icono por juego — no exclusivo de Bingo (dice); cualquier juego con venta
// externa asistida por vendedor puede aparecer aquí.
const GAME_ICONS: Record<string, string> = {
  BINGO: 'fa-dice',
  TRIVIA: 'fa-brain',
  POLLA: 'fa-futbol',
}

const statusLabel = computed(() => STATUS_LABELS[props.player.status])
const statusBadgeClass = computed(() => STATUS_BADGE_CLASSES[props.player.status])
const statusIcon = computed(() => STATUS_ICONS[props.player.status])

const gameIcon = computed(() => {
  const name = props.player.game?.name?.toUpperCase() ?? ''
  return GAME_ICONS[name] ?? 'fa-gamepad'
})

const formattedGameName = computed(() => {
  const name = props.player.game?.name ?? ''
  return name.charAt(0) + name.slice(1).toLowerCase()
})

const isExpiringSoon = computed(() => {
  if (!props.player.expiration_date) return false
  const daysLeft = (new Date(props.player.expiration_date).getTime() - Date.now()) / 86_400_000
  return daysLeft >= 0 && daysLeft <= 2
})

const formattedExpiration = computed(() => {
  if (!props.player.expiration_date) return ''
  return new Date(props.player.expiration_date).toLocaleDateString('es-VE', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
})

function formatFullDate(date: string): string {
  return new Date(date).toLocaleString('es-VE', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(props.player.link)
    copied.value = true
    toast.success('Link copiado al portapapeles')
    setTimeout(() => (copied.value = false), 1500)
  } catch {
    // Clipboard API puede fallar por permisos/contexto no seguro (ej. HTTP
    // sin TLS) — el toast de error deja claro que debe copiarlo manualmente
    // en vez de fallar en silencio.
    toast.error('No se pudo copiar el link. Selecciónalo manualmente.')
  }
}
</script>

<style scoped>
.ep-row {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.9rem;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface);
  flex-wrap: wrap;
}
.ep-row.is-blocked { border-color: rgba(239, 68, 68, 0.35); background: rgba(239, 68, 68, 0.04); }
.ep-row.is-inactive { opacity: 0.65; }

.ep-row-toggle {
  border: none;
  background: transparent;
  color: var(--ds-text-muted);
  cursor: pointer;
  padding: 0.3rem;
  flex-shrink: 0;
}
.ep-row-toggle i { font-size: 0.7rem; transition: transform 0.15s; }
.ep-row-toggle i.is-open { transform: rotate(90deg); }

.ep-row-main { display: flex; align-items: center; gap: 0.6rem; flex: 1 1 260px; min-width: 0; }
.ep-status-badge { font-size: 0.7rem; padding: 0.3rem 0.55rem; flex-shrink: 0; white-space: nowrap; }
.ep-status-badge i { font-size: 0.65rem; margin-right: 0.2rem; }
.ep-row-info { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.ep-name { font-size: 0.87rem; font-weight: 600; color: var(--ds-text); }
.ep-meta { font-size: 0.76rem; color: var(--ds-text-muted); }

.ep-row-extra { display: flex; flex-wrap: wrap; gap: 0.4rem; flex: 1 1 220px; }
.ep-tag {
  font-size: 0.72rem;
  color: var(--ds-text-2);
  background: var(--ds-surface-2);
  border-radius: 999px;
  padding: 0.2rem 0.55rem;
  white-space: nowrap;
}
.ep-tag--game { font-weight: 600; }
.ep-tag--game i { margin-right: 0.25rem; }
.ep-tag--warning { background: rgba(251, 191, 36, 0.18); color: #f59e0b; }

.ep-row-actions { display: flex; gap: 0.4rem; flex-shrink: 0; }

.ep-link-popover {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
}
.ep-link-popover-value {
  font-size: 0.72rem;
  background: var(--ds-surface-2);
  padding: 0.3rem 0.5rem;
  border-radius: 6px;
  white-space: nowrap;
  color: var(--ds-text-2);
}

.badge-success   { background-color: rgba(52,211,153,.18); color: #34d399; }
.badge-secondary { background-color: var(--ds-surface-2);  color: var(--ds-text-2); }
.badge-danger    { background-color: rgba(239,68,68,.18);  color: #ef4444; }

/* ── Detalle expandido ── */
.ep-row-detail {
  width: 100%;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.6rem;
  margin-top: 0.5rem;
  padding-top: 0.65rem;
  border-top: 1px dashed var(--ds-border);
}
.ep-detail-item { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.ep-detail-label { font-size: 0.68rem; color: var(--ds-text-muted); text-transform: uppercase; font-weight: 600; }
.ep-detail-value { font-size: 0.78rem; color: var(--ds-text-2); overflow-wrap: break-word; }
.ep-detail-link { font-size: 0.7rem; background: var(--ds-surface-2); padding: 0.15rem 0.4rem; border-radius: 4px; }

.ep-detail-enter-active, .ep-detail-leave-active { transition: opacity 0.15s, max-height 0.2s; overflow: hidden; }
.ep-detail-enter-from, .ep-detail-leave-to { opacity: 0; max-height: 0; }
.ep-detail-enter-to, .ep-detail-leave-from { opacity: 1; max-height: 200px; }

@media (max-width: 768px) {
  .ep-row { flex-direction: column; align-items: stretch; }
  .ep-row-actions { justify-content: flex-end; }
}
</style>
