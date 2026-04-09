<template>
  <div class="profile-page">
    <div class="profile-body">
    <ProfileHeader
      :user="user"
      :stats="stats"
      :edit-mode="editMode"
      :format-currency="formatCurrency"
      @toggle-edit="toggleEdit"
      @open-password="showPassword = true"
      @open-avatar="showAvatar = true"
    />

      <div class="profile-card">
        <ProfileTabs v-model="activeTab" />

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
            :format-date="formatDate"
            :format-currency="formatCurrency"
            @cancel="cancelEdit"
            @submit="saveProfile"
          />
          <GameStatsTab
            v-else-if="activeTab === 'stats'"
            key="stats"
            :stats="stats"
          />
          <PreferencesTab
            v-else-if="activeTab === 'preferences'"
            key="preferences"
            :prefs-form="prefsForm"
            @save="savePreferences"
          />
          <SecurityTab
            v-else-if="activeTab === 'security'"
            key="security"
            :user="user"
            :format-date="formatDate"
            @open-password="showPassword = true"
          />
        </Transition>
        </div>
      </div>
    </div><!-- /profile-body -->

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
  </div>
</template>

<script setup lang="ts">
import { useProfile, type ProfileUser, type ProfilePreferences, type CountryOption } from '@js/composables/useProfile'
import ProfileHeader    from '@js/Components/Profile/ProfileHeader.vue'
import ProfileTabs      from '@js/Components/Profile/ProfileTabs.vue'
import PersonalInfoTab  from '@js/Components/Profile/PersonalInfoTab.vue'
import GameStatsTab     from '@js/Components/Profile/GameStatsTab.vue'
import PreferencesTab   from '@js/Components/Profile/PreferencesTab.vue'
import SecurityTab      from '@js/Components/Profile/SecurityTab.vue'
import PasswordModal    from '@js/Components/Profile/PasswordModal.vue'
import AvatarUploadModal from '@js/Components/Profile/AvatarUploadModal.vue'

const props = defineProps<{
    user: ProfileUser
    countries: CountryOption[]
    stats: Record<string, any>
    preferences: ProfilePreferences
}>()

const {
    user, editMode, activeTab, showPassword, showAvatar,
    profileForm, prefsForm,
    selectedCountryName, isBirthDateEditable,
    toggleEdit, cancelEdit, saveProfile, savePreferences,
    onAvatarUpdated, onPasswordChanged,
    formatDate, formatCurrency,
} = useProfile(props.user, props.preferences, props.countries)
</script>

<style scoped>
.profile-page {
    min-height: 100vh;
    background: #f0f2f7;
}

.profile-body {
    max-width: 1100px;
    margin: 0 auto;
    padding: 1.5rem 1.5rem 3rem;
}

.profile-card {
    background: #fff;
    border-radius: 0 0 16px 16px;
    box-shadow: 0 4px 24px rgba(102, 126, 234, 0.10);
    overflow: hidden;
}

.tab-content {
    padding: 2rem;
    min-height: 400px;
}

/* Tab transition */
.tab-fade-enter-active,
.tab-fade-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.tab-fade-enter-from   { opacity: 0; transform: translateY(6px); }
.tab-fade-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
