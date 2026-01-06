// ui/crudModalUI.ts
import Swal from '@/helpers/toastNotify'

export async function showConfirm(caseData: any, count: number) {
    return Swal.fire({
        text:
            count > 1
                ? caseData.text.replace('este', `estos ${count}`)
                : caseData.text,
        icon: caseData.icon,
        showCancelButton: true,
        confirmButtonText: caseData.confirmButtonText
    }).then(r => r.isConfirmed)
}

export function showSuccess(count: number) {
    Swal.fire({
        icon: 'success',
        text:
            count > 1
                ? `Acción aplicada a ${count} registros`
                : 'Acción completada'
    })
}
