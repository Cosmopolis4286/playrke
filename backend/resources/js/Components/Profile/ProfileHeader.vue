<template>
  <header class="profile-header">
    <div class="ph-stars" aria-hidden="true">
      <span class="ph-star" style="top:12%;left:8%;animation-delay:0s"/>
      <span class="ph-star" style="top:35%;left:22%;animation-delay:0.8s"/>
      <span class="ph-star" style="top:60%;left:5%;animation-delay:1.6s"/>
      <span class="ph-star" style="top:20%;left:75%;animation-delay:0.4s"/>
      <span class="ph-star" style="top:70%;left:88%;animation-delay:1.2s"/>
      <span class="ph-star" style="top:80%;left:45%;animation-delay:2s"/>
    </div>

    <div class="header-inner">
      <ProfileIdentityCard
        :user="user"
        :level="stats.level ?? 1"
        :show-level="isPlayer"
        @open-avatar="$emit('open-avatar')"
      />

      <ProfileQuickStats
        v-if="isPlayer"
        :stats="stats"
        :missions-pending="missionsPending"
        :missions-total="missionsTotal"
      />

      <ProfileHeaderActions
        :user="user"
        :edit-mode="editMode"
        :format-currency="formatCurrency"
        @toggle-edit="$emit('toggle-edit')"
        @open-password="$emit('open-password')"
        @open-kyc="$emit('open-kyc')"
      />
    </div>
  </header>
</template>

<script setup lang="ts">
import type { ProfileUser } from '@js/composables/useProfile'
import ProfileIdentityCard from '@js/Components/Profile/ProfileIdentityCard.vue'
import ProfileQuickStats from '@js/Components/Profile/ProfileQuickStats.vue'
import ProfileHeaderActions from '@js/Components/Profile/ProfileHeaderActions.vue'

defineProps<{
    user: ProfileUser
    stats: Record<string, any>
    editMode: boolean
    formatCurrency: (amount: number | null, currency: unknown) => string
    missionsPending?: number
    missionsTotal?: number
    /** Gamificación (nivel/XP/partidas/logros/racha/misiones) es exclusiva de player — ver ProfileTabs.vue para el detalle de por qué. */
    isPlayer?: boolean
}>()

defineEmits<{
    'toggle-edit': []
    'open-password': []
    'open-avatar': []
    'open-kyc': []
}>()
</script>

<style scoped>
.profile-header {
    background: linear-gradient(135deg, #1a1060 0%, #2d1b69 30%, #667eea 70%, #764ba2 100%);
    padding: 1.4rem 1.6rem 1.2rem;
    border-radius: 16px 16px 0 0;
    position: relative;
    overflow: hidden;
}

.ph-stars { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.ph-star {
    position: absolute;
    width: 3px; height: 3px;
    border-radius: 50%;
    background: rgba(255,255,255,0.6);
    animation: phStarPulse 3s ease-in-out infinite;
}
@keyframes phStarPulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50%       { opacity: 1;   transform: scale(1.4); }
}

/* Grid de 3 zonas: identidad | stats | acciones — más predecible que flex
   puro con wrap para distribuir el espacio disponible sin que las 3 zonas
   compitan de forma impredecible en anchos intermedios (~900-1200px, el
   rango donde el layout anterior se apretaba). Para roles sin stats de
   gamificación (ver isPlayer en el script) ProfileQuickStats no se
   renderiza — :has() reacomoda a 2 columnas para no dejar un hueco vacío
   donde iban las stats. */
.header-inner {
    display: grid;
    grid-template-columns: minmax(240px, 1.3fr) minmax(200px, 1fr) minmax(200px, 0.9fr);
    align-items: center;
    gap: 1.5rem;
    position: relative;
    z-index: 1;
}
.header-inner:not(:has(.quick-stats)) {
    grid-template-columns: minmax(240px, 1.4fr) minmax(200px, 1fr);
}

/* Pantallas medianas: stats pasa a compartir fila con acciones, debajo de
   identidad — evita el "apretujón" de 3 columnas angostas simultáneas. */
@media (max-width: 1180px) {
    .header-inner {
        grid-template-columns: 1fr;
        gap: 1.1rem;
    }
}

@media (max-width: 480px) {
    .profile-header { padding: 1.1rem 1.1rem 1rem; }
}
</style>
