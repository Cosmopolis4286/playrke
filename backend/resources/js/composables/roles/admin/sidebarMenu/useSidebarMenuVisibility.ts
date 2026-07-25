import { computed, reactive, ref } from 'vue'
import type { SidebarMenuItem, SidebarMenuRole, SidebarMenuVisibilityMatrix } from '@/js/types/roles/admin/sidebar-menu'
import axios from 'axios'

export function useSidebarMenuVisibility(initialData?: {
    items?: SidebarMenuItem[]
    roles?: SidebarMenuRole[]
    visibility?: SidebarMenuVisibilityMatrix
}) {
    const items = ref<SidebarMenuItem[]>(initialData?.items ?? [])
    const roles = ref<SidebarMenuRole[]>(initialData?.roles ?? [])
    const visibility = ref<SidebarMenuVisibilityMatrix>(initialData?.visibility ?? {})
    const loading = ref(false)

    // pendingChanges: Record<itemKey, Record<roleId, boolean>>
    // boolean = estado deseado (true=visible, false=oculto)
    const pendingChanges = reactive<Record<string, Record<string, boolean>>>({})

    const hasChanges = computed(() => Object.keys(pendingChanges).length > 0)
    const pendingChangeCount = computed(() =>
        Object.values(pendingChanges).reduce((total, roleMap) => total + Object.keys(roleMap).length, 0)
    )

    // ── Helpers de pendingChanges ──────────────────────────────────────────

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

    // ── API ───────────────────────────────────────────────────────────────

    const fetchAll = async () => {
        loading.value = true
        try {
            const { data } = await axios.get('/sidebar-menu/fetch-list')
            items.value = data.items
            roles.value = data.roles
            visibility.value = data.visibility
        } finally {
            loading.value = false
        }
    }

    // Refresco completo: descarta pendientes y trae datos frescos del servidor
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

        await axios.post('/sidebar-menu/save', { changes })
        clearPending()
        await fetchAll() // actualizar checkboxes con estado real del servidor
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
