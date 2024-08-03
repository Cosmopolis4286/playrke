function codigo_banco_2() {
    const banco_codigo = document.querySelector(`select[name='banco']`).value;
    document.querySelector(`input[name='codigo_banco']`).value = banco_codigo;
}
document.getElementById('nro_cuenta').addEventListener('input', function (e) {
    var x = e.target.value.replace(/\D/g, '').match(/(\d{0,4})(\d{0,2})(\d{0,10})/);
    e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '');
});

const formulario = document.getElementById('dt-bco');
const inputs = document.querySelectorAll('#dt-bco input');

const expresiones = {
    usuario: /^[a-zA-Z0-9\_\-]{4,16}$/, // Letras, numeros, guion y guion_bajo
    nombre: /^[a-zA-ZÀ-ÿ\s]{1,40}$/, // Letras y espacios, pueden llevar acentos.
    password: /^.{4,12}$/, // 4 a 12 digitos.
    correo: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
    telefono: /^\d{7,14}$/, // 7 a 14 numeros.
    num_cuenta: /^\d{4}[-]\d{2}[-]\d{10}$/
}

const campos = {
    nro_cuenta: false
}

function validarFormulario(e) {
    switch (e.target.name) {
        case "nro_cuenta":
            validarCampo(expresiones.num_cuenta, e.target, 'nro_cuenta');
            break;
    }
}
const validarCampo = (expresion, input, campo) => {
    if (expresion.test(input.value)) {
        document.getElementById(`${campo}`).classList.remove('bno');
        document.getElementById('error-nc').classList.remove('e_show');
        campos[campo] = true;
    } else {
        document.getElementById(`${campo}`).classList.add('bno');
        document.getElementById('error-nc').classList.add('e_show');
        campos[campo] = false;
    }
}
inputs.forEach((input) => {
    input.addEventListener('keyup', validarFormulario);
    input.addEventListener('blur', validarFormulario);
});
formulario.addEventListener('submit', (e) => {
    e.preventDefault();
    if (campos.nro_cuenta) {
        formulario.submit();

    }
});