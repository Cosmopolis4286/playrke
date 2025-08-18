// resources/js/helpers/user/changePassw.ts

// Logout con CSRF token dinámico
export function initLogout(): void {
    document.querySelectorAll<HTMLButtonElement>('.logout-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const { logoutUrl = '/logout', loginRedirectUrl = '/login', csrfToken = '' } = window as any;

            fetch(logoutUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                },
            })
                .then((response) => {
                    if (response.ok) {
                        window.location.href = loginRedirectUrl;
                    } else {
                        alert('Error al cerrar sesión');
                    }
                })
                .catch((error) => console.error('Error en logout:', error));
        });
    });
}

// Ejecutar automáticamente si DOM ya está listo o agregar listener
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLogout);
} else {
    initLogout();
}