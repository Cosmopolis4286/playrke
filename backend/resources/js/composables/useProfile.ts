import { ref, computed } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import { showToast } from '@js/utils/toastNotify'
import type { KycState } from '@js/types/kyc'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface ProfileUser {
    id: string
    first_name: string | null
    last_name: string | null
    name: string
    username: string
    email: string
    avatar: string | null
    phone: string | null
    birth_date: string | null
    country_id: string | null
    country_iso: string | null
    bio: string | null
    is_verified: boolean
    is_two_factor_enabled: boolean
    kyc_level: 0 | 1 | 2
    kyc_status: 'NONE' | 'PENDING' | 'VERIFIED' | 'REJECTED'
    tos_accepted_at: string | null
    created_at: string
    last_active?: string
    roles: Array<{ id: string; name: string; description?: string | null }>
    wallet: { balance: number; currency: string; symbol: string } | null
}

export interface CountryOption {
    value: string
    label: string
    iso: string
}

export interface ProfilePreferences {
    language: string
    timezone: string
    notifications: { email: boolean; push: boolean; sms: boolean }
    privacy: { profile_visible: boolean; show_email: boolean; show_phone: boolean }
    game_settings: { sound_enabled: boolean; music_enabled: boolean; auto_dauber: boolean; card_animation: boolean }
}

export interface ProfileSession {
    id: string
    is_current: boolean
    ip_address: string | null
    user_agent: string
    last_activity: number
    device: string
}

export interface ProfileAchievement {
    id: string
    code: string
    name: string
    description: string | null
    icon: string | null
    badge_color: string
    badge_style: string
    xp_reward: number
    category: string
    unlocked_at: string | null
    is_seen: boolean
}

export interface ProfileReferral {
    id: string
    username: string
    full_name: string
    joined_at: string | null
}

// ─── Sync con navbar Blade ──────────────────────────────────────────────────────
// El navbar vive fuera del árbol de Vue (Blade); se notifica vía evento custom
// en vez de que este composable conozca su estructura DOM directamente.
function notifyAvatarChanged(avatarUrl: string | null) {
    window.dispatchEvent(new CustomEvent('profile:avatar-updated', { detail: { avatarUrl } }))
}

// ─── Composable ───────────────────────────────────────────────────────────────

export function useProfile(
    initialUser: ProfileUser,
    initialPreferences: ProfilePreferences,
    countries: CountryOption[],
    initialKyc: KycState | null = null,
) {
    // ── State ──────────────────────────────────────────────────────────────
    const saving        = ref(false)
    const editMode      = ref(false)
    const activeTab     = ref<'personal' | 'stats' | 'referrals' | 'preferences' | 'security'>('personal')
    const showPassword  = ref(false)
    const showAvatar    = ref(false)
    const showKycModal  = ref(false)
    const user          = ref<ProfileUser>({ ...initialUser })
    const kycState      = ref<KycState | null>(initialKyc)
    const sessions      = ref<ProfileSession[]>([])
    const sessionsLoading = ref(false)
    const achievements  = ref<ProfileAchievement[]>([])
    const achievementsLoading = ref(false)
    const referrals        = ref<ProfileReferral[]>([])
    const referralLink     = ref('')
    const totalReferrals   = ref(0)
    const referralsLoading = ref(false)
    const referralsLoaded  = ref(false)
    const errorMsg      = ref<string | null>(null)
    const savingPrefsKey = ref<string | null>(null)

    // ── Profile form ───────────────────────────────────────────────────────
    const profileForm = ref({
        first_name: initialUser.first_name ?? '',
        last_name:  initialUser.last_name  ?? '',
        username:   initialUser.username,
        email:      initialUser.email,
        phone:      initialUser.phone ?? '',
        birth_date: initialUser.birth_date ?? '',
        country_id: initialUser.country_id ?? '',
        bio:        initialUser.bio ?? '',
        errors:     {} as Record<string, string>,
        processing: false,
    })

    // ── Preferences ────────────────────────────────────────────────────────
    const prefsForm = ref<ProfilePreferences>({
        language:      initialPreferences.language,
        timezone:      initialPreferences.timezone,
        notifications: { ...initialPreferences.notifications },
        privacy:       { ...initialPreferences.privacy },
        game_settings: { ...initialPreferences.game_settings },
    })

    // ── Computed ───────────────────────────────────────────────────────────
    const selectedCountryName = computed(() =>
        countries.find(c => c.value === user.value.country_id)?.label ?? ''
    )

    const isBirthDateEditable = computed(() =>
        editMode.value || !user.value.birth_date
    )

    const kycLevelLabel = computed(() => {
        const map = { 0: 'Sin verificar', 1: 'Básico', 2: 'Completo' }
        return map[user.value.kyc_level] ?? 'Sin verificar'
    })

    // ── Actions ────────────────────────────────────────────────────────────
    function toggleEdit() {
        if (editMode.value) saveProfile()
        else editMode.value = true
    }

    function cancelEdit() {
        profileForm.value = {
            first_name: user.value.first_name ?? '',
            last_name:  user.value.last_name  ?? '',
            username:   user.value.username,
            email:      user.value.email,
            phone:      user.value.phone ?? '',
            birth_date: user.value.birth_date ?? '',
            country_id: user.value.country_id ?? '',
            bio:        user.value.bio ?? '',
            errors:     {},
            processing: false,
        }
        editMode.value = false
        errorMsg.value = null
    }

    async function saveProfile() {
        profileForm.value.processing = true
        profileForm.value.errors     = {}
        errorMsg.value               = null

        try {
            await getCsrfCookie()
            const { data } = await backendClient.put('/profile/update', {
                first_name: profileForm.value.first_name,
                last_name:  profileForm.value.last_name,
                username:   profileForm.value.username,
                email:      profileForm.value.email,
                phone:      profileForm.value.phone || null,
                birth_date: profileForm.value.birth_date || null,
                country_id: profileForm.value.country_id || null,
                bio:        profileForm.value.bio || null,
            })

            if (data.success) {
                user.value     = { ...user.value, ...data.data }
                editMode.value = false
                showToast('Perfil actualizado correctamente', 'success')
                if (user.value.avatar) notifyAvatarChanged(user.value.avatar)
            }
        } catch (err: any) {
            if (err?.response?.data?.errors) {
                profileForm.value.errors = Object.fromEntries(
                    Object.entries(err.response.data.errors).map(([k, v]) => [k, (v as string[])[0]])
                )
            }
            errorMsg.value = err?.response?.data?.message ?? 'Error al guardar los cambios.'
            showToast('Error al actualizar el perfil', 'error')
        } finally {
            profileForm.value.processing = false
        }
    }

    async function savePreferences(patch: Partial<ProfilePreferences>) {
        const key = Object.keys(patch)[0] ?? null
        savingPrefsKey.value = key
        try {
            await getCsrfCookie()
            const { data } = await backendClient.put('/profile/preferences', patch)
            if (data.success) {
                prefsForm.value = { ...prefsForm.value, ...patch }
                showToast('Preferencias guardadas', 'success')
            } else {
                showToast('Error al guardar preferencias', 'error')
            }
        } catch {
            showToast('Error al guardar preferencias', 'error')
        } finally {
            savingPrefsKey.value = null
        }
    }

    function onAvatarUpdated(newAvatar: string | undefined) {
        if (newAvatar !== undefined) {
            user.value.avatar = newAvatar
            showToast('Avatar actualizado', 'success')
            notifyAvatarChanged(newAvatar)
        }
        showAvatar.value = false
    }

    function onPasswordChanged(success: boolean) {
        if (success) showToast('Contraseña cambiada exitosamente', 'success')
        showPassword.value = false
    }

    function onKycUpdated(newState: KycState) {
        kycState.value       = newState
        user.value.kyc_level  = newState.kyc_level
        user.value.kyc_status = newState.kyc_status
        showKycModal.value   = false
    }

    // ── Achievements ───────────────────────────────────────────────────────
    async function loadAchievements() {
        achievementsLoading.value = true
        try {
            const { data } = await backendClient.get('/profile/achievements/unlocked')
            if (data.success) achievements.value = data.data
        } catch {
            // silencioso
        } finally {
            achievementsLoading.value = false
        }
    }

    // ── Referrals ──────────────────────────────────────────────────────────
    async function loadReferrals() {
        referralsLoading.value = true
        try {
            const { data } = await backendClient.get('/profile/referrals')
            if (data.success) {
                referrals.value      = data.referrals
                referralLink.value   = data.referral_link
                totalReferrals.value = data.total_referrals
                referralsLoaded.value = true
            }
        } catch {
            // silencioso
        } finally {
            referralsLoading.value = false
        }
    }

    // ── Sessions ───────────────────────────────────────────────────────────
    async function loadSessions() {
        sessionsLoading.value = true
        try {
            const { data } = await backendClient.get('/profile/sessions')
            if (data.success) sessions.value = data.data
        } catch {
            // silencioso
        } finally {
            sessionsLoading.value = false
        }
    }

    async function revokeSession(id: string) {
        try {
            await getCsrfCookie()
            const { data } = await backendClient.delete(`/profile/sessions/${id}`)
            if (data.success) {
                sessions.value = sessions.value.filter(s => s.id !== id)
                showToast('Sesión cerrada', 'success')
            } else {
                showToast(data.message ?? 'Error al cerrar la sesión', 'error')
            }
        } catch (err: any) {
            showToast(err?.response?.data?.message ?? 'Error al cerrar la sesión', 'error')
        }
    }

    async function revokeAllSessions() {
        try {
            await getCsrfCookie()
            const { data } = await backendClient.delete('/profile/sessions')
            if (data.success) {
                sessions.value = sessions.value.filter(s => s.is_current)
                showToast('Todas las otras sesiones han sido cerradas', 'success')
            }
        } catch {
            showToast('Error al cerrar las sesiones', 'error')
        }
    }

    // ── Formatters ─────────────────────────────────────────────────────────
    function formatDate(dateString: string | null | undefined, withTime = false): string {
        if (!dateString) return 'No disponible'
        return new Date(dateString).toLocaleDateString('es-VE', {
            year: 'numeric', month: 'long', day: 'numeric',
            ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
        })
    }

    function formatCurrency(amount: number | null, currency: unknown): string {
        if (!amount) return '0.00'
        const code = typeof currency === 'string' ? currency : 'USD'
        return new Intl.NumberFormat('es-VE', { style: 'currency', currency: code }).format(amount)
    }

    function formatUnixDate(ts: number): string {
        return new Date(ts * 1000).toLocaleDateString('es-VE', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        })
    }

    return {
        user, editMode, activeTab, showPassword, showAvatar, showKycModal,
        profileForm, prefsForm, kycState, sessions, sessionsLoading, errorMsg,
        achievements, achievementsLoading,
        referrals, referralLink, totalReferrals, referralsLoading, referralsLoaded,
        saving, savingPrefsKey,
        selectedCountryName, isBirthDateEditable, kycLevelLabel,
        toggleEdit, cancelEdit, saveProfile, savePreferences,
        onAvatarUpdated, onPasswordChanged, onKycUpdated,
        loadSessions, revokeSession, revokeAllSessions, loadAchievements, loadReferrals,
        formatDate, formatCurrency, formatUnixDate,
    }
}
