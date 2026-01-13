// helpers/form/formRegister.ts
import { Notyf } from 'notyf';

// Referencias DOM
const form = document.getElementById('form_rg') as HTMLFormElement | null;
const inputs = form ? form.querySelectorAll<HTMLInputElement>('input') : [];

// Expresiones regulares para validación
const regexPatterns = {
    username: /^[a-zA-Z0-9_-]{4,16}$/, // Letras, números, guion y guion bajo
    password: /^.{8,12}$/,              // 8 a 12 caracteres
    email: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
};

// Estado de validación de campos
const fieldsValid: Record<string, boolean> = {
    username: false,
    email: false,
    password: false,
};

/**
 * Validate a specific field against its regex pattern
 * @param pattern - RegExp to test
 * @param input - HTMLInputElement to validate
 * @param fieldName - Name of the field for UI updates
 */
function validateField(pattern: RegExp, input: HTMLInputElement, fieldName: string) {
    const group = document.getElementById(`grupo__${fieldName}`);
    const errorMsg = group?.querySelector<HTMLElement>('.auth-form__error');

    if (!group || !errorMsg) return;

    if (pattern.test(input.value)) {
        group.classList.remove('formulario__grupo-incorrecto');
        group.classList.add('formulario__grupo-correcto');
        errorMsg.classList.remove('auth-form__error-activo');
        fieldsValid[fieldName] = true;
    } else {
        group.classList.add('formulario__grupo-incorrecto');
        group.classList.remove('formulario__grupo-correcto');
        errorMsg.classList.add('auth-form__error-activo');
        fieldsValid[fieldName] = false;
    }
}

/**
 * Event handler to validate input based on input's name attribute
 * @param event - InputEvent from the input field
 */
function handleInputValidation(event: Event) {
    const target = event.target as HTMLInputElement;
    switch (target.name) {
        case 'username':
            validateField(regexPatterns.username, target, 'username');
            break;
        case 'password':
            validateField(regexPatterns.password, target, 'password');
            break;
        case 'email':
            validateField(regexPatterns.email, target, 'email');
            break;
    }
}

/**
 * Show an error notification using Notyf or fallback alert
 * @param message - Text message to show
 */
function showError(message: string) {
    try {
        const notyf = new Notyf();
        notyf.error({
            message: `<span style="font-size:13px;">${message}</span>`,
            duration: 3450,
            ripple: true,
            position: { x: 'center', y: 'bottom' },
        });
    } catch {
        alert(message);
    }
}

/**
 * Validate form on submit: check all fields and terms acceptance,
 * check if passwords match, and submit or show error accordingly.
 */
function handleFormSubmit(event: Event) {
    event.preventDefault();

    const policiesCheckbox = document.getElementById('politicas') as HTMLInputElement | null;
    if (!policiesCheckbox) {
        showError('Policies checkbox not found.');
        return;
    }

    // Validate all fields and policies checkbox
    if (fieldsValid.username && fieldsValid.email && fieldsValid.password && policiesCheckbox.checked) {
        const password = form?.querySelector<HTMLInputElement>('#password')?.value;
        const password2 = form?.querySelector<HTMLInputElement>('#password2')?.value;

        if (password === password2) {
            // Hide password mismatch message
            document.getElementById('no_pass')?.classList.add('hidden');

            // Show loader button (assumes it exists)
            document.getElementById('loader-btn-register')?.classList.remove('hidden');

            // Disable submit button
            const submitBtn = document.getElementById('btn-register') as HTMLButtonElement | null;
            if (submitBtn) submitBtn.disabled = true;

            // Submit the form
            form?.submit();
        } else {
            document.getElementById('no_pass')?.classList.remove('hidden');
        }
    } else {
        showError('All information is required and terms must be accepted.');
    }
}

// Attach event listeners if form exists
if (form) {
    inputs.forEach((input) => input.addEventListener('input', handleInputValidation));
    document.getElementById('btn-register')?.addEventListener('click', handleFormSubmit);

    // Hide the password mismatch message on start
    document.getElementById('no_pass')?.classList.add('hidden');
}
