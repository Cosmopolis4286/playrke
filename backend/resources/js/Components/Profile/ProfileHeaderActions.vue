<template>
  <div class="header-actions">
    <div v-if="user.wallet" class="wallet-badge">
      <span class="wallet-badge__icon"><i class="fas fa-wallet" /></span>
      <div class="wallet-badge__info">
        <span class="wallet-badge__label">Saldo disponible</span>
        <span class="wallet-badge__amount">
          {{ formatCurrency(user.wallet.balance, user.wallet.currency) }}
        </span>
      </div>
    </div>

    <div class="actions-row">
      <button
        :class="['action-btn', editMode ? 'action-btn--save' : 'action-btn--edit']"
        @click="$emit('toggle-edit')"
      >
        <i :class="['fas', editMode ? 'fa-save' : 'fa-pen']" />
        {{ editMode ? 'Guardar' : 'Editar perfil' }}
      </button>

      <button class="action-btn action-btn--ghost" @click="$emit('open-password')">
        <i class="fas fa-lock" /> Contraseña
      </button>

      <button
        v-if="user.kyc_status !== 'VERIFIED' && user.kyc_status !== 'PENDING'"
        class="action-btn action-btn--kyc"
        @click="$emit('open-kyc')"
      >
        <i class="fas fa-id-card" /> Verificar identidad
      </button>
    </div>

    <div v-if="accountAlert" class="account-alert" :class="`account-alert--${accountAlert.type}`">
      <i :class="accountAlert.icon" />
      <span>{{ accountAlert.message }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ProfileUser } from '@js/composables/useProfile'

const props = defineProps<{
    user: ProfileUser
    editMode: boolean
    formatCurrency: (amount: number | null, currency: unknown) => string
}>()

defineEmits<{
    'toggle-edit': []
    'open-password': []
    'open-kyc': []
}>()

const accountAlert = computed(() => {
    if (!props.user.tos_accepted_at)
        return { type: 'warn', icon: 'fas fa-file-contract', message: 'Acepta los Términos de Servicio para continuar usando la plataforma.' }
    if (!props.user.is_verified)
        return { type: 'warn', icon: 'fas fa-envelope', message: 'Verifica tu correo electrónico para asegurar tu cuenta.' }
    if (props.user.kyc_status === 'REJECTED')
        return { type: 'danger', icon: 'fas fa-times-circle', message: 'Tu verificación KYC fue rechazada. Sube tus documentos de nuevo.' }
    return null
})
</script>

<style scoped>
.header-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }

.wallet-badge {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px; padding: 6px 12px;
}
.wallet-badge__icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: rgba(52,211,153,0.2);
    color: #34d399; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.wallet-badge__info { display: flex; flex-direction: column; }
.wallet-badge__label  { font-size: 0.65rem; color: rgba(255,255,255,0.55); line-height: 1; }
.wallet-badge__amount { font-size: 0.85rem; font-weight: 700; color: #fff; line-height: 1.3; }

.actions-row { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }

.action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 8px; font-size: 0.76rem;
    font-weight: 600; cursor: pointer; border: none;
    transition: all 0.15s ease; white-space: nowrap;
}
.action-btn--edit  { background: rgba(255,255,255,0.95); color: #667eea; }
.action-btn--edit:hover  { background: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.action-btn--save  { background: #28a745; color: #fff; box-shadow: 0 2px 8px rgba(40,167,69,0.4); }
.action-btn--save:hover  { background: #218838; transform: translateY(-1px); }
.action-btn--ghost { background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.25); }
.action-btn--ghost:hover { background: rgba(255,255,255,0.22); }
.action-btn--kyc   { background: rgba(255,193,7,0.85); color: #212529; }
.action-btn--kyc:hover   { background: rgba(255,193,7,1); transform: translateY(-1px); }

.account-alert {
    display: flex; align-items: center; gap: 7px;
    padding: 6px 12px; border-radius: 8px;
    font-size: 0.75rem; font-weight: 500;
    max-width: 260px; line-height: 1.35;
}
.account-alert--warn   { background: rgba(255,193,7,0.18);  color: #fde68a; border: 1px solid rgba(255,193,7,0.3); }
.account-alert--danger { background: rgba(220,53,69,0.18);  color: #fca5a5; border: 1px solid rgba(220,53,69,0.3); }

/* Modo apilado (ver ProfileHeader.vue): alinear a la izquierda, consistente
   con identidad/stats que ya se apilaron arriba. */
@media (max-width: 1180px) {
    .header-actions { align-items: flex-start; }
    .actions-row { justify-content: flex-start; }
}

@media (max-width: 720px) {
    .header-actions { align-items: stretch; width: 100%; }
    .actions-row { justify-content: flex-start; }
    .account-alert { max-width: 100%; }
    .action-btn { flex: 1 1 auto; justify-content: center; }
}
</style>
