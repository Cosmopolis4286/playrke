<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useProfile, type ProfileUser, type ProfilePreferences, type CountryOption } from '@js/composables/useProfile'
import { useTos }          from '@js/composables/useTos'
import { useKyc }          from '@js/composables/useKyc'
import ProfileHeader       from '@js/Components/Profile/ProfileHeader.vue'
import ProfileTabs         from '@js/Components/Profile/ProfileTabs.vue'
import PersonalInfoTab     from '@js/Components/Profile/PersonalInfoTab.vue'
import GameStatsTab        from '@js/Components/Profile/GameStatsTab.vue'
import DailyMissionsTab    from '@js/Components/Profile/DailyMissionsTab.vue'
import ReferralsTab        from '@js/Components/Profile/ReferralsTab.vue'
import PreferencesTab      from '@js/Components/Profile/PreferencesTab.vue'
import SecurityTab         from '@js/Components/Profile/SecurityTab.vue'
import PasswordModal       from '@js/Components/Profile/PasswordModal.vue'
import AvatarUploadModal   from '@js/Components/Profile/AvatarUploadModal.vue'
import TosModal            from '@js/Components/Common/TosModal.vue'
import KycUploadModal      from '@js/Components/Common/KycUploadModal.vue'
import type { KycState }   from '@js/types/kyc'

const props = defineProps<{
    user: ProfileUser
    countries: CountryOption[]
    stats: Record<string, any>
    preferences: ProfilePreferences
    kyc: KycState | null
    tos: { needs_acceptance: boolean; accepted_version: string | null; accepted_at: string | null }
}>()

// ── Composable central del perfil ──────────────────────────────────────────
const {
    user, editMode, activeTab,
    showPassword, showAvatar, showKycModal,
    profileForm, prefsForm, savingPrefsKey,
    kycState, sessions, sessionsLoading, errorMsg,
    achievements, achievementsLoading,
    referrals, referralLink, totalReferrals, referralsLoading, referralsLoaded,
    dailyMissions, dailyMissionsLoading, dailyMissionsLoaded,
    selectedCountryName, isBirthDateEditable,
    toggleEdit, cancelEdit, saveProfile, savePreferences,
    onAvatarUpdated, onPasswordChanged, onKycUpdated,
    loadSessions, revokeSession, revokeAllSessions, loadAchievements, loadReferrals, loadDailyMissions,
    formatDate, formatCurrency, formatUnixDate,
} = useProfile(props.user, props.preferences, props.countries, props.kyc)

// ── TOS ────────────────────────────────────────────────────────────────────
const tosModalOpen = ref(false)
const { status: tosStatus, accepting: tosAccepting, error: tosError,
        needsAcceptance, accept: acceptTos } = useTos()

async function handleTosAccept() {
    const ok = await acceptTos()
    if (ok) {
        tosModalOpen.value = false
        user.value.tos_accepted_at = new Date().toISOString()
    }
}

// ── KYC ────────────────────────────────────────────────────────────────────
const { uploading: kycUploading, error: kycError, success: kycSuccess,
        submitDocument } = useKyc()

async function handleKycSubmit(form: Parameters<typeof submitDocument>[0]) {
    const ok = await submitDocument(form)
    if (ok && kycState.value) {
        onKycUpdated({ ...kycState.value, kyc_status: 'PENDING', has_pending: true })
    }
}

// ── Sesiones / logros: cargar al abrir el tab ──────────────────────────────
watch(activeTab, (tab) => {
    if (tab === 'security' && !sessions.value.length) loadSessions()
    if (tab === 'stats' && !achievements.value.length) loadAchievements()
    if (tab === 'referrals' && !referralsLoaded.value) loadReferrals()
    // missions NO es lazy como el resto: se carga en onMounted (abajo) para
    // que el badge de pendientes en ProfileTabs tenga dato real desde el
    // primer render, sin esperar a que el usuario abra el tab.
})

const missionsPending = computed(() =>
    dailyMissions.value.filter(m => !m.completed).length
)
const missionsTotal = computed(() => dailyMissions.value.length)

// Misiones/Estadísticas (logros) son exclusivas de 'player' — ver
// ProfileTabs.vue para el detalle de por qué (ambos sistemas están
// construidos sobre datos que solo un jugador genera). ProfileTabs ya
// oculta esos botones para otros roles, pero el hash de la URL puede
// forzar activeTab a un tab oculto igual (ej. un link viejo /profile#stats
// abierto por un admin) — se filtra acá también para que el contenido
// activo siempre sea consistente con lo que la barra de tabs ofrece.
const isPlayer = computed(() => user.value.roles?.some(r => r.name === 'player') ?? false)
const VALID_TABS = computed(() => {
    const tabs = ['personal', 'referrals', 'preferences', 'security']
    if (isPlayer.value) tabs.push('stats', 'missions')
    return tabs
})

onMounted(() => {
    const hashTab = window.location.hash.replace('#', '')
    if (VALID_TABS.value.includes(hashTab)) {
        activeTab.value = hashTab as typeof activeTab.value
    }

    if (props.tos.needs_acceptance) tosModalOpen.value = true

    if (isPlayer.value) loadDailyMissions()
})
</script>

<script lang="ts">
export default { name: 'ProfileIndex' }
</script>

<template>
  <div class="profile-page">
    <div class="profile-body">

      <ProfileHeader
        :user="user"
        :stats="stats"
        :edit-mode="editMode"
        :format-currency="formatCurrency"
        :missions-pending="missionsPending"
        :missions-total="missionsTotal"
        :is-player="isPlayer"
        :is-player="isPlayer"
        @toggle-edit="toggleEdit"
        @open-password="showPassword = true"
        @open-avatar="showAvatar = true"
        @open-kyc="showKycModal = true"
      />

      <div class="profile-card">
        <ProfileTabs v-model="activeTab" :user="user" :missions-pending="missionsPending" />

        <div class="tab-content">
          <Transition name="tab-fade" mode="out-in">

            <PersonalInfoTab
              v-if="activeTab === 'personal'"
              key="personal"
              :user="user"
              :countries="countries"
              :edit-mode="editMode"
              :profile-form="profileForm"
              :selected-country-name="selectedCountryName"
              :is-birth-date-editable="isBirthDateEditable"
              :error-msg="errorMsg"
              :format-date="formatDate"
              :format-currency="formatCurrency"
              @submit="saveProfile"
              @cancel="cancelEdit"
              @open-kyc="showKycModal = true"
            />

            <GameStatsTab
              v-else-if="activeTab === 'stats'"
              key="stats"
              :stats="stats"
              :achievements="achievements"
              :achievements-loading="achievementsLoading"
            />

            <DailyMissionsTab
              v-else-if="activeTab === 'missions'"
              key="missions"
              :missions="dailyMissions"
              :loading="dailyMissionsLoading"
            />

            <ReferralsTab
              v-else-if="activeTab === 'referrals'"
              key="referrals"
              :referral-link="referralLink"
              :referrals="referrals"
              :total-referrals="totalReferrals"
              :loading="referralsLoading"
            />

            <PreferencesTab
              v-else-if="activeTab === 'preferences'"
              key="preferences"
              :prefs-form="prefsForm"
              :saving-key="savingPrefsKey"
              :on-save="savePreferences"
              @save="savePreferences"
            />

            <SecurityTab
              v-else-if="activeTab === 'security'"
              key="security"
              :user="user"
              :sessions="sessions"
              :sessions-loading="sessionsLoading"
              :format-date="formatDate"
              :format-unix-date="formatUnixDate"
              @open-password="showPassword = true"
              @open-tos="tosModalOpen = true"
              @open-kyc="showKycModal = true"
              @load-sessions="loadSessions"
              @revoke-session="revokeSession"
              @revoke-all-sessions="revokeAllSessions"
            />

          </Transition>
        </div>
      </div>

    </div>

    <!-- Modales -->
    <PasswordModal
      v-if="showPassword"
      @close="showPassword = false"
      @password-changed="onPasswordChanged"
    />

    <AvatarUploadModal
      v-if="showAvatar"
      :current-avatar="user.avatar"
      @close="showAvatar = false"
      @avatar-updated="onAvatarUpdated"
    />

    <TosModal
      v-model="tosModalOpen"
      :status="tosStatus"
      :accepting="tosAccepting"
      :error="tosError"
      @accept="handleTosAccept"
    />

    <KycUploadModal
      v-model="showKycModal"
      :uploading="kycUploading"
      :error="kycError"
      :success="kycSuccess"
      @submit="handleKycSubmit"
    />

  </div>
</template>

<style scoped>
.profile-page  { min-height: 100vh; background: var(--ds-bg); }
.profile-body  { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1.5rem 3rem; }
.profile-card  { background: var(--ds-surface); border-radius: 0 0 16px 16px; box-shadow: 0 4px 24px rgba(102,126,234,0.10); overflow: hidden; }
.tab-content   { padding: 2rem; min-height: 400px; }

.tab-fade-enter-active, .tab-fade-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.tab-fade-enter-from { opacity: 0; transform: translateY(6px); }
.tab-fade-leave-to   { opacity: 0; transform: translateY(-4px); }
</style>
