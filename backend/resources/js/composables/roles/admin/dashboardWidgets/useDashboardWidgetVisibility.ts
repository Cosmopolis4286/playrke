import { computed, reactive, ref } from 'vue'
import type { DashboardWidgetItem, DashboardWidgetRole, DashboardWidgetVisibilityMatrix } from '@/js/types/roles/admin/dashboard-widgets'
import axios from 'axios'

export function useDashboardWidgetVisibility(initialData?: {
    items?: DashboardWidgetItem[]
    roles?: DashboardWidgetRole[]
    visibility?: DashboardWidgetVisibilityMatrix
}) {
    const items = ref<DashboardWidgetItem[]>(initialData?.items ?? [])
    const roles = ref<DashboardWidgetRole[]>(initialData?.roles ?? [])
    const visibility = ref<DashboardWidgetVisibilityMatrix>(initialData?.visibility ?? {})
    const loading = ref(false)

    // pendingChanges: Record<itemKey, Record<roleId, boolean>>
    const pendingChanges = reactive<Record<string, Record<string, boolean>>>({})

    const hasChanges = computed(() => Object.keys(pendingChanges).length > 0)
    const pendingChangeCount = computed(() =>
        Object.values(pendingChanges).reduce((total, roleMap) => total + Object.keys(roleMap).length, 0)
    )

    function clearPending() {
        for (const k of Object.keys(pendingChanges)) {
            delete pendingChanges[k]
        }
    }

    // Ausencia de entrada = visible=true (semántica de "lista de excepciones").
    function isVisible(itemKey: string, roleId: string): boolean {
        if (pendingChanges[itemKey]?.[roleId] !== undefined) {
            return pendingChanges[itemKey][roleId]
        }
        return visibility.value[itemKey]?.[roleId] ?? true
    }

    function toggleVisibility(itemKey: string, roleId: string) {
        const serverValue = visibility.value[itemKey]?.[roleId] ?? true

        if (pendingChanges[itemKey]?.[roleId] !== undefined) {
            delete pendingChanges[itemKey][roleId]
            if (Object.keys(pendingChanges[itemKey]).length === 0) {
                delete pendingChanges[itemKey]
            }
        } else {
            if (!pendingChanges[itemKey]) pendingChanges[itemKey] = {}
            pendingChanges[itemKey][roleId] = !serverValue
        }
    }

    const fetchAll = async () => {
        loading.value = true
        try {
            const { data } = await axios.get('/dashboard-widgets/fetch-list')
            items.value = data.items
            roles.value = data.roles
            visibility.value = data.visibility
        } finally {
            loading.value = false
        }
    }

    const reload = async () => {
        clearPending()
        await fetchAll()
    }

    const saveChanges = async () => {
        const changes: { item_key: string; role_id: string; is_visible: boolean }[] = []

        for (const [itemKey, roleMap] of Object.entries(pendingChanges)) {
            for (const [roleId, isVisibleValue] of Object.entries(roleMap)) {
                changes.push({ item_key: itemKey, role_id: roleId, is_visible: isVisibleValue })
            }
        }

        await axios.post('/dashboard-widgets/save', { changes })
        clearPending()
        await fetchAll()
    }

    return {
        items,
        roles,
        visibility,
        loading,
        pendingChanges,
        hasChanges,
        pendingChangeCount,
        isVisible,
        toggleVisibility,
        reload,
        saveChanges,
    }
}
