// services/crudOtherCasesService.ts
import axios from '@/plugins/axios'
import { formCrudState as crud } from '../state/formCrudState'
import { showConfirm, showSuccess } from '../ui/crudModalUI'
import type { OtherCase } from '@/helpers/module/types/formModule.types'
import { getSelectedIds } from '@/alpine/tableSelectionListener'

export async function handleOtherCases(
    action: string,
    encryptId: string,
    elem?: HTMLElement
) {
    const caseData = resolveCase(action)
    if (!caseData) return

    const ids = resolveIds(encryptId, elem)
    if (!ids.length) return

    const confirmed = await showConfirm(caseData, ids.length)
    if (!confirmed) return

    await Promise.all(
        ids.map(id =>
            axios.request({
                method: caseData.method!.toLowerCase(),
                url: crud.routeFn(caseData.routeName, { id }),
                data: elem?.dataset?.status
                    ? { status: elem.dataset.status }
                    : undefined
            })
        )
    )

    showSuccess(ids.length)
}

function resolveCase(action: string): OtherCase | null {
    return (
        {
            delete: {
                routeName: 'destroy',
                text: '¿Deseas eliminar este registro?',
                icon: 'warning',
                confirmButtonText: 'Sí, eliminar',
                method: 'DELETE'
            }
        }[action] || crud.otherCasesMap[action] || null
    )
}

function resolveIds(encryptId: string, elem?: HTMLElement): string[] {
    const tableId = elem?.closest('[data-table-id]')?.getAttribute('data-table-id')
    return tableId ? getSelectedIds(tableId) : encryptId ? [encryptId] : []
}
