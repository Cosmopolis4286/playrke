// ==== UTILS ====
const $ = (selector, parent = document) => parent.querySelector(selector);
const $$ = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));

const notyf = new Notyf({
    duration: 3500,
    ripple: true,
    position: { x: 'center', y: 'bottom' },
});

const showError = (message) => {
    // Evita mostrar error si ya hay uno activo
    if (!document.querySelector('.notyf__toast:not(.notyf__toast--dismissed)')) {
        notyf.error(`<span style="font-size:13px;">${message}</span>`);
    }
};

const isEmailValid = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());

const togglePasswordVisibility = () => {
    $$(`.toggle-password`).forEach(el => {
        el.addEventListener('click', () => {
            const target = $(`#${el.dataset.target}`);
            if (!target) return;
            const isPassword = target.type === 'password';
            target.type = isPassword ? 'text' : 'password';
            el.classList.toggle('fa-eye');
            el.classList.toggle('fa-eye-slash');
        });
    });
};

const validateForm = (form) => {
    const email = $('[name="email"]', form);
    if (!email.value.trim() || !isEmailValid(email.value)) {
        showError('Por favor ingresa un correo válido.');
        return false;
    }

    const username = $('[name="username"]', form);
    if (!username.value.trim() || username.value.trim().length < 3) {
        showError('El nombre de usuario debe tener al menos 3 caracteres.');
        return false;
    }

    const country = $('[name="country"]', form);
    if (!country.value) {
        showError('Por favor selecciona tu país.');
        return false;
    }

    const password = $('[name="password"]', form);
    if (!password.value || password.value.length < 8) {
        showError('La contraseña debe tener al menos 8 caracteres.');
        return false;
    }

    const passwordConfirm = $('[name="password_confirmation"]', form);
    if (passwordConfirm.value !== password.value) {
        showError('Las contraseñas no coinciden.');
        return false;
    }

    const terms = $('[name="terms"]', form);
    if (!terms || !terms.checked) {
        showError('Debes aceptar los Términos y Condiciones.');
        return false;
    }

    return true;
};

const handleSubmit = () => {
    const form = document.getElementById('form-register');
    const btn = $('button[type="submit"]', form);
    const loader = $('.spinner-border', btn);

    form.addEventListener('submit', e => {
        e.preventDefault();

        if (!validateForm(form)) return;

        btn.disabled = true;
        loader.classList.remove('d-none');
        form.submit();
    });
};

// ==== INIT ====
document.addEventListener('DOMContentLoaded', () => {
    togglePasswordVisibility();
    handleSubmit();
});
