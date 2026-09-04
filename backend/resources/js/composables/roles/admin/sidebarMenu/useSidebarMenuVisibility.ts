import { computed, reactive, ref } from 'vue'
import type { SidebarMenuItem, SidebarMenuRole, SidebarMenuSortOrderMatrix, SidebarMenuVisibilityMatrix } from '@/js/types/roles/admin/sidebar-menu'
import axios from 'axios'

export function useSidebarMenuVisibility(initialData?: {
    items?: SidebarMenuItem[]
    roles?: SidebarMenuRole[]
    visibility?: SidebarMenuVisibilityMatrix
    sortOrder?: SidebarMenuSortOrderMatrix
}) {
    const items = ref<SidebarMenuItem[]>(initialData?.items ?? [])
    const roles = ref<SidebarMenuRole[]>(initialData?.roles ?? [])
    const visibility = ref<SidebarMenuVisibilityMatrix>(initialData?.visibility ?? {})
    const sortOrder = ref<SidebarMenuSortOrderMatrix>(initialData?.sortOrder ?? {})
    const loading = ref(false)

    // pendingChanges: Record<itemKey, Record<roleId, boolean>>
    // boolean = estado deseado (true=visible, false=oculto)
    const pendingChanges = reactive<Record<string, Record<string, boolean>>>({})

    // pendingOrderChanges: Record<itemKey, Record<roleId, number|null>>
    // null = volver al orden natural (quitar el override)
    const pendingOrderChanges = reactive<Record<string, Record<string, number | null>>>({})

    const hasChanges = computed(() =>
        Object.keys(pendingChanges).length > 0 || Object.keys(pendingOrderChanges).length > 0
    )
    const pendingChangeCount = computed(() =>
        Object.values(pendingChanges).reduce((total, roleMap) => total + Object.keys(roleMap).length, 0) +
        Object.values(pendingOrderChanges).reduce((total, roleMap) => total + Object.keys(roleMap).length, 0)
    )

    // ── Helpers de pendingChanges ──────────────────────────────────────────

    function clearPending() {
        for (const k of Object.keys(pendingChanges)) {
            delete pendingChanges[k]
        }
        for (const k of Object.keys(pendingOrderChanges)) {
            delete pendingOrderChanges[k]
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

    // Ausencia de entrada = null (orden natural de $menuStructure).
    function getSortOrder(itemKey: string, roleId: string): number | null {
        if (pendingOrderChanges[itemKey]?.[roleId] !== undefined) {
            return pendingOrderChanges[itemKey][roleId]
        }
        return sortOrder.value[itemKey]?.[roleId] ?? null
    }

    function setSortOrder(itemKey: string, roleId: string, value: number | null) {
        const serverValue = sortOrder.value[itemKey]?.[roleId] ?? null

        if (value === serverValue) {
            if (pendingOrderChanges[itemKey]?.[roleId] !== undefined) {
                delete pendingOrderChanges[itemKey][roleId]
                if (Object.keys(pendingOrderChanges[itemKey]).length === 0) {
                    delete pendingOrderChanges[itemKey]
                }
            }
            return
        }

        if (!pendingOrderChanges[itemKey]) pendingOrderChanges[itemKey] = {}
        pendingOrderChanges[itemKey][roleId] = value
    }

    // ── API ───────────────────────────────────────────────────────────────

    const fetchAll = async () => {
        loading.value = true
        try {
            const { data } = await axios.get('/sidebar-menu/fetch-list')
            items.value = data.items
            roles.value = data.roles
            visibility.value = data.visibility
            sortOrder.value = data.sortOrder
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
        const changes: { item_key: string; role_id: string; is_visible?: boolean; sort_order?: number | null }[] = []
        const byKey = new Map<string, { item_key: string; role_id: string; is_visible?: boolean; sort_order?: number | null }>()

        function entryFor(itemKey: string, roleId: string) {
            const mapKey = `${itemKey}:${roleId}`
            let entry = byKey.get(mapKey)
            if (!entry) {
                entry = { item_key: itemKey, role_id: roleId }
                byKey.set(mapKey, entry)
                changes.push(entry)
            }
            return entry
        }

        for (const [itemKey, roleMap] of Object.entries(pendingChanges)) {
            for (const [roleId, isVisibleValue] of Object.entries(roleMap)) {
                entryFor(itemKey, roleId).is_visible = isVisibleValue
            }
        }

        for (const [itemKey, roleMap] of Object.entries(pendingOrderChanges)) {
            for (const [roleId, orderValue] of Object.entries(roleMap)) {
                entryFor(itemKey, roleId).sort_order = orderValue
            }
        }

        await axios.post('/sidebar-menu/save', { changes })
        clearPending()
        await fetchAll() // actualizar checkboxes/inputs con estado real del servidor
    }

    return {
        items,
        roles,
        visibility,
        sortOrder,
        loading,
        pendingChanges,
        pendingOrderChanges,
        hasChanges,
        pendingChangeCount,
        isVisible,
        toggleVisibility,
        getSortOrder,
        setSortOrder,
        reload,
        saveChanges,
    }
}
