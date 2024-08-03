const formulario = document.getElementById('form_rg');
const inputs = document.querySelectorAll('#form_rg input');

const expresiones = {
	username: /^[a-zA-Z0-9\_\-]{4,16}$/, // Letras, numeros, guion y guion_bajo
	password: /^.{4,12}$/, // 4 a 12 digitos.
	email: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
}

const campos = {
	username: false,
	email: false,
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
	if (expresion.test(input.value)) {
		document.getElementById(`grupo__${campo}`).classList.remove('formulario__grupo-incorrecto');
		document.getElementById(`grupo__${campo}`).classList.add('formulario__grupo-correcto');
		document.querySelector(`#grupo__${campo} .formulario__input-error`).classList.remove(
			'formulario__input-error-activo');
		campos[campo] = true;
	} else {
		document.getElementById(`grupo__${campo}`).classList.add('formulario__grupo-incorrecto');
		document.getElementById(`grupo__${campo}`).classList.remove('formulario__grupo-correcto');
		document.querySelector(`#grupo__${campo} .formulario__input-error`).classList.add(
			'formulario__input-error-activo');
		campos[campo] = false;
	}
}

inputs.forEach((input) => {
	input.addEventListener('input', validarFormulario);
	$("#no_pass").hide();
});

$("#btn-register").click(function (e) {
	e.preventDefault();
	const politicas = document.getElementById('politicas');
	if (campos.username && campos.email && politicas.checked) {
		if ($("#password").val() === $("#password2").val()) {
			$("#no_pass").hide();
			$('#form_rg').submit();
			$("#loader-btn-register").show();
			$("#btn-register").attr('disabled', true);
		} else {
			$("#no_pass").show();
		}
	} else {
		if ($(".notyf__toast").html() == null || $(".notyf__toast").html() == '') {
			var notyf = new Notyf();
			notyf.error({
				message: '<span style="font-size:13px;">Toda la información es requerida</span>',
				duration: 3450,
				ripple: true,
				position: {
					x: 'center',
					y: 'bottom',
				}
			})
		}
	}
});