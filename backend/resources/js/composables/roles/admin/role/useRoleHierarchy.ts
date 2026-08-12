import { ref } from 'vue'
import type { RoleHierarchyNode, RoleMetaUpdate } from '@/js/types/roles/admin/role'
import axios from 'axios'

function findNode(nodes: RoleHierarchyNode[], roleId: string): RoleHierarchyNode | null {
    for (const node of nodes) {
        if (node.id === roleId) return node
        const found = findNode(node.children, roleId)
        if (found) return found
    }
    return null
}

export function useRoleHierarchy() {
    const tree = ref<RoleHierarchyNode[]>([])
    const loading = ref(false)

    const fetchHierarchy = async () => {
        loading.value = true
        try {
            const { data } = await axios.get('/roles-admin/hierarchy')
            tree.value = data.tree
        } finally {
            loading.value = false
        }
    }

    const updateRoleMeta = async (roleId: string, attributes: RoleMetaUpdate) => {
        const { data } = await axios.patch(`/roles-admin/${roleId}`, attributes)
        const node = findNode(tree.value, roleId)
        if (node) Object.assign(node, data.role)
        return data.role
    }

    return {
        tree,
        loading,
        fetchHierarchy,
        updateRoleMeta,
    }
}
