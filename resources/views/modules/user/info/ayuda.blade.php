@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ url('/home') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="margin-left:7px; font-size:26px;">{{ $title }}</span>
                    @if (Session::has('mensaje'))
                        <div class="col-4">
                            <caption>
                                <div class="alert alert-info alert-dismissible" role="alert">
                                    <strong></strong> {{ Session::get('mensaje') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            </caption>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5 class="sub-faq">questions Frecuentes</h5>
                            <li><a class="ps" href="##" id="p1">¿Qué es PlayRKE?</a></li>
                            <p id="r1"></p>
                            <li><a class="ps" href="##" id="p2">¿Qué es El Poder del Saber?</a></li>
                            <p id="r2"></p>
                            <li><a class="ps" href="##" id="p3">¿Hay que depositar dinero para utilizar
                                    los juegos o aplicaciones de PlayRKE?</a></li>
                            <p id="r3"></p>
                            <li><a class="ps" href="##" id="p4">¿Cómo puedo generar ganancias?</a></li>
                            <p id="r4"></p>
                            <li><a class="ps" href="##" id="p5">¿PlayRKE está disponible en mi
                                    regíon?</a></li>
                            <p id="r5"></p>
                            <li><a class="ps" href="##" id="p6">¿Cada cuánto se abren nuevas salas de
                                    juego?</a></li>
                            <p id="r6"></p>
                        </div>
                        <div class="col-md-4">
                            <li><a class="ps" href="##" id="p7">¿Cuánto puedo ganar en PlayRKE?</a></li>
                            <p id="r7"></p>
                            @if (Auth::user()->country == 234)
                                <li><a class="ps" href="##" id="p8">¿Cuáles son los métodos de pago
                                        disponibles?</a></li>
                                <p id="r8"></p>
                            @else
                                <li><a class="ps" href="##" id="p8c">¿Cuáles son los métodos de pago
                                        disponibles?</a></li>
                                <p id="r8c"></p>
                            @endif
                            @if (Auth::user()->country == 234)
                                <li><a class="ps" href="##" id="p9">¿Monto mínimo de depósito?</a></li>
                                <p id="r9"></p>
                            @else
                                <li><a class="ps" href="##" id="p9c">¿Monto mínimo de depósito?</a></li>
                                <p id="r9c"></p>
                            @endif
                            <li><a class="ps" href="##" id="p10">¿En cuánto tiempo se procesa mi
                                    depósito?</a></li>
                            <p id="r10"></p>
                            <li><a class="ps" href="##" id="p11">¿Monto mínimo de retiro?</a></li>
                            <p id="r11"></p>
                            <li><a class="ps" href="##" id="p12">¿Cuánto demora en llegar un retiro?</a>
                            </li>
                            <p id="r12"></p>
                            <li><a class="ps" href="##" id="p13">¿Cómo funciona el saldo congelado?</a>
                            </li>
                            <p id="r13"></p>
                            <hr>
                        </div>
                        <div class="col-md-4">
                            <h5 class="sub-faq">Infografías</h5>
                            <li><a class="ps" href="##" id="g1">¿Cómo depositar?</a></li>
                            <p id="f1"></p>
                            <li><a class="ps" href="##" id="g2">¿Cómo jugar/participar?</a></li>
                            <p id="f2"></p>
                            <li><a class="ps" href="##" id="g3">¿Cómo retirar?</a></li>
                            <p id="f3"></p>
                            <li><a class="ps" href="##" id="g4">¿Cómo transferir?</a></li>
                            <p id="f4"></p>
                            <hr />
                            <a href="#" style="color: #138D75;" data-toggle="modal" data-target="#report">
                                <div>
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    Reportar un problema
                                </div>
                            </a>
                            <a href="https://t.me/+rV3KWBuuKOtjYzkx" target="_blank" style="color: #138D75;">
                                <i class="fa-solid fa-comments"></i>
                                Comunidad PlayRKE</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layouts/modals/report')
    <style>
        .ps {
            margin-left: -10px;
            font-size: 15px;
        }

        .sub-faq {
            font-size: 20px;
        }
    </style>
@endsection
@section('scripts')
    <script src="{{ asset('js/faq.js') }}"></script>
@endsection
