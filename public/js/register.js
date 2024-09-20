const formulario = document.getElementById('form_rg');
const inputs = document.querySelectorAll('#form_rg input');

const expresiones = {
    username: /^[a-zA-Z0-9\_\-]{4,16}$/, // Letras, números, guion y guion bajo
    password: /^.{8,12}$/, // 8 a 12 caracteres
    email: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
}

const campos = {
    username: false,
    email: false,
    password: false,
}

const validarFormulario = (e) => {
    switch (e.target.name) {
        case "username":
            validarCampo(expresiones.username, e.target, 'username');
            break;
        case "password":
            validarCampo(expresiones.password, e.target, 'password');
            break;
        case "email":
            validarCampo(expresiones.email, e.target, 'email');
            break;
    }
}

const validarCampo = (expresion, input, campo) => {
    const grupo = document.getElementById(`grupo__${campo}`);
    const errorMsg = grupo.querySelector('.formulario__input-error');
    
    if (expresion.test(input.value)) {
        grupo.classList.remove('formulario__grupo-incorrecto');
        grupo.classList.add('formulario__grupo-correcto');
        errorMsg.classList.remove('formulario__input-error-activo');
        campos[campo] = true;
    } else {
        grupo.classList.add('formulario__grupo-incorrecto');
        grupo.classList.remove('formulario__grupo-correcto');
        errorMsg.classList.add('formulario__input-error-activo');
        campos[campo] = false;
    }
}

inputs.forEach((input) => {
    console.log(input);
    input.addEventListener('input', validarFormulario);
});

// Esconder el mensaje de error al iniciar
$("#no_pass").hide();

$("#btn-register").click(function (e) {
    e.preventDefault();
    
    const politicas = document.getElementById('politicas');
    
    if (campos.username && campos.email && campos.password && politicas.checked) {
        if ($("#password").val() === $("#password2").val()) {
            $("#no_pass").hide();
            $("#loader-btn-register").show();
            $('#form-register').submit();
            $("#btn-register").attr('disabled', true);
        } else {
            $("#no_pass").show();
        }
    } else {
        mostrarError('Toda la información es requerida');
    }
});

// Función para mostrar errores
function mostrarError(mensaje) {
    if ($(".notyf__toast").is(':empty')) {
        const notyf = new Notyf();
        notyf.error({
            message: `<span style="font-size:13px;">${mensaje}</span>`,
            duration: 3450,
            ripple: true,
            position: {
                x: 'center',
                y: 'bottom',
            }
        });
    }
}