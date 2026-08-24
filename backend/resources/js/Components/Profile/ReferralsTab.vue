<template>
  <div class="referrals-layout">

    <div class="section-card">
      <div class="section-header">
        <span class="section-icon link-icon"><i class="fas fa-link"></i></span>
        <h6>Tu enlace de invitación</h6>
      </div>

      <p class="link-hint">Comparte este enlace — cuando alguien se registre a través de él, ganarás XP.</p>

      <div class="link-row">
        <input class="link-input" type="text" :value="referralLink" readonly @focus="($event.target as HTMLInputElement)?.select()" />
        <button class="copy-btn" type="button" @click="copyLink">
          <i class="fas" :class="copied ? 'fa-check' : 'fa-copy'"></i>
          {{ copied ? 'Copiado' : 'Copiar' }}
        </button>
      </div>
    </div>

    <div class="section-card">
      <div class="section-header">
        <span class="section-icon people-icon"><i class="fas fa-user-friends"></i></span>
        <h6>Tus referidos <span class="badge-count">{{ totalReferrals }}</span></h6>
      </div>

      <div v-if="loading" class="referrals-loading">
        <i class="fas fa-spinner fa-spin"></i> Cargando referidos...
      </div>

      <ul v-else-if="referrals.length" class="referral-list">
        <li v-for="r in referrals" :key="r.id" class="referral-item">
          <span class="referral-avatar">{{ (r.full_name || r.username).charAt(0).toUpperCase() }}</span>
          <span class="referral-body">
            <span class="referral-name">{{ r.full_name || r.username }}</span>
            <span class="referral-meta">@{{ r.username }} · {{ formatDate(r.joined_at) }}</span>
          </span>
        </li>
      </ul>

      <div v-else class="empty-state">
        <div class="empty-icon"><i class="fas fa-user-plus"></i></div>
        <p>Todavía no tienes referidos</p>
        <small>Comparte tu enlace para empezar a ganar XP</small>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

export interface ProfileReferral {
    id: string
    username: string
    full_name: string
    joined_at: string | null
}

defineProps<{
    referralLink: string
    referrals: ProfileReferral[]
    totalReferrals: number
    loading?: boolean
}>()

const copied = ref(false)

async function copyLink() {
    const input = document.querySelector('.link-input') as HTMLInputElement | null
    if (!input) return
    try {
        await navigator.clipboard.writeText(input.value)
    } catch {
        input.select()
        document.execCommand('copy')
    }
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}

function formatDate(dateString: string | null): string {
    if (!dateString) return 'Fecha no disponible'
    return new Date(dateString).toLocaleDateString('es-VE', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<style scoped>
.referrals-layout { display: flex; flex-direction: column; gap: 1rem; }

.section-card {
  background: var(--ds-surface);
  border-radius: 14px;
  border: 1px solid var(--ds-border);
  padding: 1.1rem 1.25rem;
}

.section-header { display: flex; align-items: center; gap: .6rem; margin-bottom: 1rem; }
.section-header h6 { margin: 0; font-size: .88rem; font-weight: 600; color: var(--ds-text); display: flex; align-items: center; gap: .4rem; }

.section-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; }
.link-icon   { background: #eef2ff; color: #667eea; }
.people-icon { background: #e8f5e9; color: #11998e; }

.link-hint { font-size: .82rem; color: var(--ds-text-2); margin: 0 0 .85rem; }

.link-row { display: flex; gap: .6rem; }
.link-input {
  flex: 1;
  padding: .6rem .8rem;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface-2);
  color: var(--ds-text);
  font-size: .82rem;
}
.copy-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .6rem 1rem;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  font-size: .82rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}
.copy-btn:hover { opacity: .92; }

.badge-count {
  display: inline-flex; align-items: center; justify-content: center;
  background: #667eea; color: #fff; font-size: .65rem; font-weight: 600;
  border-radius: 99px; padding: 1px 7px; margin-left: 4px;
}

.referrals-loading { text-align: center; padding: 1.5rem 0; font-size: .82rem; color: var(--ds-text-2); }

.referral-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .5rem; }
.referral-item {
  display: flex; align-items: center; gap: .7rem;
  padding: .6rem .7rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
}
.referral-avatar {
  width: 34px; height: 34px; flex-shrink: 0; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; color: #fff; font-size: .9rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
}
.referral-body { display: flex; flex-direction: column; gap: .1rem; min-width: 0; }
.referral-name { font-weight: 600; color: var(--ds-text); font-size: .85rem; }
.referral-meta { font-size: .74rem; color: var(--ds-text-2); }

.empty-state { text-align: center; padding: 1.5rem 0; color: var(--ds-text-muted); }
.empty-icon {
  width: 56px; height: 56px; border-radius: 50%;
  background: var(--ds-surface-2);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; margin: 0 auto .75rem;
}
.empty-state p { font-size: .88rem; margin: 0 0 .2rem; color: var(--ds-text-2); }
.empty-state small { font-size: .76rem; }
</style>
