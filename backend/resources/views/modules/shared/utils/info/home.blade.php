    <div class="col-md-8 col-lg-4 m-auto">
        <div class="info-box" title="¡Bienvenid@ a PlayRKE!">
            <div class="info-box-content" align="center">
                <span class="mt-n1 info-box-number wm-e">¡Bienvenid@ a PlayRKE!</span>
                <hr style="margin:1% auto;">
                <span class="" style="font-size: 13.15px;">Constantemente estamos actualizando nuestra
                    plataforma, para así brindar mejores servicios a todos nuestros usuarios
                    <a href="#" style="color: #00237A;">
                        ¿Quieres&nbspganar?</a>
                    Únete ahora a nuestras salas de juego disponibles para tí y demuestra tu capacidad, donde
                    solo los mejores ganan.
                </span>
                <div class="tb-center" style="width:100%;">
                    <div class="row " title="Redes Sociales" style="margin-top:6.75px;">
                        <a class="info-box-icon mr-n2" title="Facebook" href="https://www.facebook.com/my.play.rke"
                            target="_blank"><i class="fab fa-facebook rs-i"></i></a>
                        <a class="info-box-icon mr-n2" title="Instagram" href="https://www.instagram.com/my.play.rke"
                            target="_blank"><i class="fab fa-instagram rs-i"></i></a>
                        <a class="info-box-icon" title="Youtube" href="#"><i class="fab fa-youtube rs-i"></i></a>
                        <a class="info-box-icon ml-n2" title="Telegram" href="https://t.me/+rV3KWBuuKOtjYzkx"><i
                                class="fab fa-telegram-plane rs-i"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="info-box tb-center bg-rke">
            <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                <ol class="carousel-indicators">
                    <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
                    <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
                </ol>
                <div class="carousel-inner cold">
                    <div class="carousel-item active">
                        <a href="#" data-toggle="modal" data-target="#if_s1">
                            <img class="img-tbl" src="img/slider/z.png" alt="First slide">
                        </a>
                    </div>
                    {{-- <div class="carousel-item">
                    <img class="img-tbl" src="{{ asset('img/slider/games.png') }}" alt="Second slide"
                            style="margin:0 auto;max-width:100%;">
                    </div> --}}
                    <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only"></span>
                    </a>
                    <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only"></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="info-box bg-rke">
            <div class="info-box-content" align="center" style="max-height:372.3px;">
                <div style="display: flex; justify-content:center; margin:0px 5.5px;">
                    <a href="{{ route('info.pds') }}" class="cold info-box-number">
                        <img src="{{ asset('img/pds.png') }}" class="pds-if"></a>
                </div>
                <span class="info-text" style="font-size: 13.5px;">
                    <hr style="margin:1% auto;">
                    Es una trivia que consiste en una serie de 10 questions; donde cada una presenta
                    cuatro posibles respuestas de selección simple (A·B·C·D). Sin embargo, solo una es la respuesta
                    correcta.<a href="{{ route('info.pds') }}"> Más información</a>.
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="info-box tb-center bg-rke">
            <img class="img-tbl" src="{{ asset('img/mrke.png') }}" alt="PlayRKE">
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="info-box bg-rke">
            <div style="min-height:190px !important; margin:0 auto;">
                <span class="info-box-number" style="font-size: 17px;" align="center">¡Últimas Actualizaciones
                    PlayRKE!</span>
                <hr style="margin:1% auto;">
                <div class="info-text" style="font-size: 13.5px; overflow-y: scroll; max-height:142px;">
                    <li>Salas de juego por temas o categorias.</li>
                    <li>Modalidad de solo dos (2) ganadores.</li>
                    <li>Modalidad del 5% de ganadores aprox.</li>
                    <li>Entrada mín. en salas de juego $0.65.</li>
                    @if (Auth::user()->country == 234)
                        <li>Retiro máx. $600 cada 24 horas.</li>
                        <li>Depósito mínimo $0.70 aprox.</li>
                    @endif
                    <li>Monto mín. depósito en criptos 2 USDT.</li>
                    <li><a href="https://t.me/+-WdlujxenQ5lMGRh" target="_blank">Nuestra Comunidad y Soporte</a></li>
                </div>
            </div>
        </div>
    </div>
    @section('scripts')
        <script nonce="{{ csp_nonce() }}">
            $('.carousel').carousel()
        </script>
    @endsection
