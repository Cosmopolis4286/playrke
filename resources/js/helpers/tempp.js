function run_timer() {
    var
        hc = $('#cat').val(),
        wrapper = $('#roxz'),
        ahora = new Date(),
        ftime = hc + " GMT-0400",
        limite = new Date(ftime).getTime(),
        restante = limite - ahora,
        dias = Math.floor(restante / (1000 * 60 * 60 * 24)),
        horas = Math.floor((restante % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutos = Math.floor((restante % (1000 * 60 * 60)) / (1000 * 60)),
        segundos = Math.floor((restante % (1000 * 60)) / 1000),
        texto = '';

    if (restante < 0) {
        wrapper.html('¡La partida ya inició!');
        return;
    }

    if (dias > 0) {
        texto += dias + 'd&nbsp';
    }
    if (horas > 0) {
        texto += horas + 'h&nbsp';
    }
    if (minutos > 0) {
        texto += minutos + 'm&nbsp';
    }
    if (segundos > 0) {
        texto += segundos + 's';
    }

    wrapper.html(texto);
    setTimeout(run_timer, 1000);
}