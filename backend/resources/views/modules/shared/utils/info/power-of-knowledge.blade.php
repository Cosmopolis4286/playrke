@extends ('layouts.layout-auth')
@section('title', 'El Poder del Saber')
@section('content')
    <div class="card mb-3 m-auto" style="max-width: 825px;">
        <div class="card-body">
            <div class="d-flex justify-content-center">
                <img src="{{ asset('img/pds.png') }}" class="cold pds-if">
            </div>
            <p class="text-center">PlayRKE se reserva el derecho de realizar cualquier tipo de modificación en el sitio
                web en cualquier momento y sin previo aviso, el usuario acepta dichas modificaciones una vez creada su
                cuenta. Esta sección definirá las funcionalidades y el alcance que tendrá la aplicación o juego EL PODER
                DEL SABER, propiedad intelectual de PlayRKE.</p>
            <hr>
            <h4>REGLAMENTO</h4>
            <p><b>El Poder del Saber</b>, es una trivia que consiste en una serie de 10 questions; donde cada una
                presenta
                cuatro posibles respuestas de selección simple (A·B·C·D). Sin embargo, solo una es la respuesta
                correcta. A continuación, se definen las reglas que rigen a este juego:</p>
            <p><b>1.</b> Es necesario que se cumpla la fecha y hora establecida. Una vez logrado esto, se habilita la
                sala de juego correspondiente para jugar, esto para todos los participantes de la misma. Por lo tanto,
                deben iniciar sesión y estar al pendiente de la fecha y hora en la sala de juego que corresponda.</p>
            <p><b>2.</b> No existe ningún tipo de reembolso o devolución. Esto en caso, que el jugador desee
                retirarse del juego o presente algún tipo de inconveniente ajeno a la plataforma, tales como: retraso en
                su ingreso al juego, pérdida de conexión, mal funcionamiento de su equipo, conexión a Internet lenta,
                etc.
            </p>
            <p><b>3.</b> El prize_pool acumulado será igual a la suma del pago de inscripción de todos los jugadores, y
                este a su vez, se expresa en la moneda correspondiente.</p>
            <p><b>4.</b> El prize_pool acumulado se reparte equitativamente entre todos los ganadores.</p>
            <p><b>5.</b> El ganador o los ganadores, serán aquellos que respondan todas las questions
                correctamente, y en el menor tiempo posible; logrando así ingresar al porcentaje ganador.
            <p><b>6.</b> Cada question tiene un límite de 25 segundos para ser respondida. En caso de que no se
                responda, se tomará como incorrecta y finaliza el juego para ese jugador. </p>
            <p><b>7.</b> Solo un porcentaje de jugadores podrá avanzar en cada question, y así sucesivamente,
                hasta llegar a la question final. Es decir, sólo los más rápidos en responder correctamente, estarán más
                cerca de ganar.</p>
            <p><b>8.</b> El uso de comodines estará restringido a partir de la séptima question.</p>
            <p><b>9.</b> El nombre de usuario de los primeros tres ganadores será publicado al final del juego.</p>
            <hr>
            <h5>COMODINES</h5>
            <p><i class="fas fa-balance-scale"></i> Se mostrarán 2 posibles respuestas (la correcta y una incorrecta).
            </p>
            <p><i class="fas fa-frog"></i> El usuario automáticamente “salta” o pasa a la siguiente question, sin
                necesidad de responder.</p>
            <li>Regla N° 8. <b>“</b>El uso de comodines está restringido a partir de la séptima question<b>”</b>.</li>
            <hr>
            <h6><b>¿CÓMO PARTICIPAR?</b></h6>
            <p>Para unirse o participar en algún juego con dinero real, el usuario tiene que utilizar saldo de
                su Wallet. En caso de no tener saldo disponible, este tendrá que realizar una recarga o depósito a
                través de cualquier método disponible en la plataforma. [<a
                    href="{{ route('info.terms') }}">Ver&nbsp<b>7.3</b></a>]</p>
            <hr>
            <h6><b>PREMIOS Y MÁS</b></h6>
            <p>Después de terminado el juego, los premios y bonificaciones serán cargados al Wallet de los usuarios
                ganadores. Luego de esto, podrán retirar saldo (dinero digital) a través de cualquiera de los métodos de
                pago disponibles en la plataforma.</p>
            <li>Regla N° 9. <b>“</b>El nombre de usuario de los primeros tres ganadores será publicado al final del
                juego<b>”</b>.</li>
            <hr>
            <div class="mt-4 text-center">
                <span class="end_pds">“Las reglas y condiciones de uso, están sujetas a cambios en el futuro incluso sin
                    previo aviso”</span><br>
                <span class="endf_pds">Este documento se actualizó por última vez el 20 de febrero del 2023.</span>
            </div>
        </div>
    </div>
@endsection
