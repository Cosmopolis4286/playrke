import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { showToast } from '@js/utils/toastNotify'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface ProfileUser {
    id: string
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
    created_at: string
    last_active?: string
    roles: Array<{ id: string; name: string; description?: string }>
    wallet: { balance: number; currency: string } | null
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

// ─── Composable ───────────────────────────────────────────────────────────────

export function useProfile(
    initialUser: ProfileUser,
    initialPreferences: ProfilePreferences,
    countries: CountryOption[],
) {
    // ── State ──────────────────────────────────────────────────────────────────
    const editMode      = ref(false)
    const activeTab     = ref<'personal' | 'stats' | 'preferences' | 'security'>('personal')
    const showPassword  = ref(false)
    const showAvatar    = ref(false)
    const user          = ref<ProfileUser>({ ...initialUser })

    // ── Profile form ───────────────────────────────────────────────────────────
    const profileForm = useForm({
        name:       initialUser.name,
        username:   initialUser.username,
        email:      initialUser.email,
        phone:      initialUser.phone ?? '',
        birth_date: initialUser.birth_date ?? '',
        country_id: initialUser.country_id ?? '',
    })

    // ── Preferences form ───────────────────────────────────────────────────────
    const prefsForm = useForm({
        language:      initialPreferences.language,
        timezone:      initialPreferences.timezone,
        notifications: { ...initialPreferences.notifications },
        privacy:       { ...initialPreferences.privacy },
        game_settings: { ...initialPreferences.game_settings },
    })

    // ── Computed ───────────────────────────────────────────────────────────────
    const selectedCountryName = computed(() => {
        if (!user.value.country_id) return ''
        return countries.find(c => c.value === user.value.country_id)?.label ?? ''
    })

    const isBirthDateEditable = computed(() =>
        editMode.value || !user.value.birth_date
    )

    // ── Actions ────────────────────────────────────────────────────────────────
    function toggleEdit() {
        if (editMode.value) {
            saveProfile()
        } else {
            editMode.value = true
        }
    }

    function cancelEdit() {
        profileForm.reset()
        editMode.value = false
    }

    function saveProfile() {
        profileForm.put('/profile/api/profile', {
            onSuccess: () => {
                showToast('Perfil actualizado exitosamente', 'success')
                editMode.value = false
                // sync local user ref
                user.value = {
                    ...user.value,
                    name:       profileForm.name,
                    username:   profileForm.username,
                    email:      profileForm.email,
                    phone:      profileForm.phone || null,
                    birth_date: profileForm.birth_date || null,
                    country_id: profileForm.country_id || null,
                }
            },
            onError: () => {
                showToast('Error al actualizar el perfil', 'error')
            },
        })
    }

    async function savePreferences(patch: Partial<ProfilePreferences>) {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
        try {
            const res = await fetch('/profile/api/preferences', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(patch),
            })
            const data = await res.json()
            if (data.success) {
                showToast('Preferencias guardadas', 'success')
            } else {
                showToast('Error al guardar preferencias', 'error')
            }
        } catch {
            showToast('Error al guardar preferencias', 'error')
        }
    }

    function onAvatarUpdated(newAvatar: string | undefined) {
        if (newAvatar !== undefined) {
            user.value.avatar = newAvatar
            showToast('Avatar actualizado', 'success')
            // Update Blade navbar images (rendered server-side, outside Vue root)
            document.querySelectorAll<HTMLImageElement>('.user-img').forEach(img => {
                img.src = newAvatar
            })
        }
        showAvatar.value = false
    }

    function onPasswordChanged(success: boolean) {
        if (success) showToast('Contraseña cambiada exitosamente', 'success')
        showPassword.value = false
    }

    // ── Formatters (shared) ────────────────────────────────────────────────────
    function formatDate(dateString: string | null | undefined, withTime = false): string {
        if (!dateString) return 'No disponible'
        return new Date(dateString).toLocaleDateString('es-VE', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
        })
    }

    function formatCurrency(amount: number | null, currency: unknown): string {
        if (!amount) return '$0.00'
        const code = typeof currency === 'string' ? currency : 'USD'
        return new Intl.NumberFormat('es-VE', { style: 'currency', currency: code }).format(amount)
    }

    return {
        // state
        user,
        editMode,
        activeTab,
        showPassword,
        showAvatar,
        // forms
        profileForm,
        prefsForm,
        // computed
        selectedCountryName,
        isBirthDateEditable,
        // actions
        toggleEdit,
        cancelEdit,
        saveProfile,
        savePreferences,
        onAvatarUpdated,
        onPasswordChanged,
        // formatters
        formatDate,
        formatCurrency,
    }
}
