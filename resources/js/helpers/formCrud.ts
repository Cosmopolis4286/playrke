import { showToast, showToastOptionBtns } from './toastNotify';
import { ajaxStart } from './loader';

export function formCRUD(
    elem: HTMLElement | [string, string],
    name: string,
    title: string,
    otherCasesMap: { [key: string]: (id: string) => void } = {}
): void {
    const modalLabel = document.getElementById(`modal-${name}-label`);
    const form = document.getElementById(`form-${name}`) as HTMLFormElement;
    if (!form || !modalLabel) return;

    form.reset();
    form.querySelectorAll('select:not(.not-empty)').forEach(select => {
        (select as HTMLSelectElement).innerHTML = '';
    });

    let action: string;
    let encryptedId = '';

    if (Array.isArray(elem)) {
        [action, encryptedId] = elem;
    } else if (elem instanceof HTMLElement) {
        const data = elem.dataset;
        action = data.action || '';
        if (action === 'edit' || action === 'delete') {
            encryptedId = data.id || '';
        }
    } else {
        console.error('elem no es un HTMLElement ni un array válido');
        return;
    }

    switch (action) {
        case 'create':
            modalLabel.textContent = `Registrar ${title}`;
            form.setAttribute('action', window.route(`${name}.create`));
            form.querySelectorAll('input[name="_method"]').forEach(i => i.remove());
            (window as any).insertInfo?.('create');
            break;

        case 'edit':
            modalLabel.textContent = `Editar ${title}`;
            form.setAttribute('action', window.route(`${name}.update`, encryptedId));
            form.querySelectorAll('input[name="_method"]').forEach(i => i.remove());

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = '_method';
            hiddenInput.value = 'PUT';
            form.appendChild(hiddenInput);

            getInfo(name, encryptedId);
            break;

        case 'delete':
            showToastOptionBtns(
                'El registro será eliminado definitivamente',
                'info',
                'Sí, eliminar',
                window.route(`${name}.delete`, encryptedId)
            );
            break;

        default:
            otherCasesMap[action]?.(encryptedId)
                ?? console.error(`Acción no reconocida: ${action}`);
    }
}

interface ErrorSessionData {
    error: string;
    encryptedId?: string;
}

export function handleFormCRUDEvents(
    name: string,
    title: string,
    otherCasesMap: { [key: string]: (id: string) => void } = {}
): void {
    const hasError = (window as any)._formCrudError as boolean;
    const errorData = (window as any)._formCrudErrorData as ErrorSessionData | undefined;

    if (hasError && errorData) {
        let elem: HTMLElement | [string, string] | null = null;

        if (errorData.error === 'create') {
            elem = document.querySelector(`#btn-${name}-insert`) as HTMLElement;
        } else if (errorData.error === 'edit' && errorData.encryptedId) {
            elem = ['edit', errorData.encryptedId];
        }

        if (elem) {
            formCRUD(elem, name, title, otherCasesMap);

            // Limpiar la sesión después
            fetch('/clear-form-crud-session', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content,
                    'Content-Type': 'application/json',
                },
            }).catch(error => console.error('Error al limpiar la sesión:', error));
        }
    }

    // Escucha clics para botones .btn-[name]
    document.addEventListener('click', function (e: Event) {
        const target = e.target as HTMLElement;
        if (target.matches(`.btn-${name}`)) {
            formCRUD(target, name, title, otherCasesMap);
        }
    });
}

// Manejar otherCases
export function handleOtherCases(route: string) {

}

// Validar y enviar formulario
export function submitForm(formName: string, requiredFields: string[] = []): void {
    const form = document.getElementById(`form-${formName}`) as HTMLFormElement;
    if (!form) return;

    if (!requiredFields.length) {
        requiredFields = Array.from(form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input[required], select[required]'))
            .map(el => {
                if (!el.id) console.warn('Campo requerido sin id:', el);
                return el.id;
            }).filter(Boolean) as string[];
    }

    const invalid = requiredFields.filter(id => {
        const el = document.getElementById(id) as HTMLInputElement | HTMLSelectElement;
        return !el?.value;
    });

    if (invalid.length) {
        showToast('Todos los campos son requeridos', 'warning');
        return;
    }

    ajaxStart();
    form.submit();
}

// Obtener información
export function getInfo(name: string, id: string): void {
    fetch(window.route(`${name}.getInfo`, id), {
        method: 'GET'
    })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            return res.json();
        })
        .then(data => (window as any).insertInfo?.(2, data))
        .catch(err => console.error('Error al obtener información:', err));
}
