import { computed, reactive, ref } from 'vue'
import type { Permission, RolePermission, RoleWithPermissions } from '@/js/types/roles/admin/permission'
import axios from 'axios'

export function usePermissions(initialData?: {
    permissions?: { rows: Permission[]; pagination: any }
    roles?: RoleWithPermissions[]
}) {
    const permissions = ref<{ rows: Permission[]; pagination: any }>(
        initialData?.permissions ?? {
            rows: [],
            pagination: { current_page: 1, per_page: 15, total: 0, last_page: 1, from: 1, to: 0 },
        }
    )
    const roles = ref<RoleWithPermissions[]>(initialData?.roles ?? [])
    const loading = ref(false)

    // pendingChanges: Record<roleId, Record<permId, boolean>>
    // boolean = estado deseado (true=asignar, false=revocar)
    const pendingChanges = reactive<Record<string, Record<string, boolean>>>({})

    const hasChanges = computed(() => Object.keys(pendingChanges).length > 0)
    const pendingChangeCount = computed(() =>
        Object.values(pendingChanges).reduce((total, permMap) => total + Object.keys(permMap).length, 0)
    )

    // ── Helpers de pendingChanges ──────────────────────────────────────────

    function clearPending() {
        for (const k of Object.keys(pendingChanges)) {
            delete pendingChanges[k]
        }
    }

    function hasPermission(roleId: string, permissionId: string): boolean {
        if (pendingChanges[roleId]?.[permissionId] !== undefined) {
            return pendingChanges[roleId][permissionId]
        }
        return roles.value.find(r => r.id === roleId)?.permissions.includes(permissionId) ?? false
    }

    function togglePermission(roleId: string, permissionId: string) {
        const serverHas = roles.value.find(r => r.id === roleId)?.permissions.includes(permissionId) ?? false

        if (pendingChanges[roleId]?.[permissionId] !== undefined) {
            delete pendingChanges[roleId][permissionId]
            if (Object.keys(pendingChanges[roleId]).length === 0) {
                delete pendingChanges[roleId]
            }
        } else {
            if (!pendingChanges[roleId]) pendingChanges[roleId] = {}
            pendingChanges[roleId][permissionId] = !serverHas
        }
    }

    // ── API ───────────────────────────────────────────────────────────────

    const fetchPermissions = async () => {
        loading.value = true
        try {
            const { data } = await axios.get('/permissions/fetch-list')
            permissions.value = { rows: data.permissions.rows, pagination: null }
        } finally {
            loading.value = false
        }
    }

    const fetchRoles = async () => {
        const { data } = await axios.get('/permissions/roles-permissions')
        roles.value = data.roles
    }

    // Refresco completo: descarta pendientes y trae datos frescos del servidor
    const reload = async () => {
        clearPending()
        await Promise.all([fetchPermissions(), fetchRoles()])
    }

    const saveChanges = async () => {
        const promises: Promise<any>[] = []

        for (const [roleId, permMap] of Object.entries(pendingChanges)) {
            const toAssign = Object.entries(permMap).filter(([, v]) => v).map(([k]) => k)
            const toRevoke = Object.entries(permMap).filter(([, v]) => !v).map(([k]) => k)

            if (toAssign.length) {
                promises.push(axios.post(`/permissions/roles/${roleId}/assign`, { permission_ids: toAssign }))
            }
            if (toRevoke.length) {
                promises.push(axios.post(`/permissions/roles/${roleId}/revoke`, { permission_ids: toRevoke }))
            }
        }

        await Promise.all(promises)
        clearPending()
        await fetchRoles() // actualizar checkboxes con estado real del servidor
    }

    const createPermission = async (data: { name: string; guard_name?: string; description?: string }) => {
        const response = await axios.post('/permissions', data)
        await Promise.all([fetchPermissions(), fetchRoles()])
        return response.data.permission
    }

    const deletePermission = async (permissionId: string) => {
        await axios.delete(`/permissions/${permissionId}`)
        await fetchPermissions()
    }

    return {
        permissions,
        roles,
        loading,
        pendingChanges,
        hasChanges,
        pendingChangeCount,
        hasPermission,
        togglePermission,
        reload,
        saveChanges,
        createPermission,
        deletePermission,
    }
}
