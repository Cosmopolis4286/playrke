@extends('layouts.auth-layout')
@section('title', $title)
@section('content')
    <style>
        .ps {
            margin-left: -10px;
            font-size: 15px;
        }

        .sub-faq {
            font-size: 20px;
        }
    </style>
    <div class="col-md-12">
        <div class="card bg-rke">
            @include('includes.card-header')
            <div class="card-body">
                <div class="row">
                    <!-- Column 1: FAQ Section -->
                    <div class="col-md-4">
                        <h5 class="sub-faq">Preguntas Frecuentes</h5>
                        <ul class="faq-list">
                            <li><a class="ps" href="#" id="p1">¿Qué es PlayRKE?</a>
                                <p id="r1"></p>
                            </li>
                            <li><a class="ps" href="#" id="p2">¿Qué es El Poder del Saber?</a>
                                <p id="r2"></p>
                            </li>
                            <li><a class="ps" href="#" id="p3">¿Hay que depositar dinero para utilizar los
                                    juegos o aplicaciones de PlayRKE?</a>
                                <p id="r3"></p>
                            </li>
                            <li><a class="ps" href="#" id="p4">¿Cómo puedo generar ganancias?</a>
                                <p id="r4"></p>
                            </li>
                            <li><a class="ps" href="#" id="p5">¿PlayRKE está disponible en mi región?</a>
                                <p id="r5"></p>
                            </li>
                            <li><a class="ps" href="#" id="p6">¿Cada cuánto se abren nuevas salas de
                                    juego?</a>
                                <p id="r6"></p>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 2: Additional Questions Based on Country -->
                    <div class="col-md-4">
                        <ul class="faq-list">
                            <li><a class="ps" href="#" id="p7">¿Cuánto puedo ganar en PlayRKE?</a>
                                <p id="r7"></p>
                            </li>
                            @if (Auth::user()->country == 234)
                                <li><a class="ps" href="#" id="p8">¿Cuáles son los métodos de pago
                                        disponibles?</a>
                                    <p id="r8"></p>
                                </li>
                                <li><a class="ps" href="#" id="p9">¿Monto mínimo de depósito?</a>
                                    <p id="r9"></p>
                                </li>
                            @else
                                <li><a class="ps" href="#" id="p8c">¿Cuáles son los métodos de pago
                                        disponibles?</a>
                                    <p id="r8c"></p>
                                </li>
                                <li><a class="ps" href="#" id="p9c">¿Monto mínimo de depósito?</a>
                                    <p id="r9c"></p>
                                </li>
                            @endif
                            <li><a class="ps" href="#" id="p10">¿En cuánto tiempo se procesa mi
                                    depósito?</a>
                                <p id="r10"></p>
                            </li>
                            <li><a class="ps" href="#" id="p11">¿Monto mínimo de retiro?</a>
                                <p id="r11"></p>
                            </li>
                            <li><a class="ps" href="#" id="p12">¿Cuánto demora en llegar un retiro?</a>
                                <p id="r12"></p>
                            </li>
                            <li><a class="ps" href="#" id="p13">¿Cómo funciona el saldo congelado?</a>
                                <p id="r13"></p>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3: Infographics Section -->
                    <div class="col-md-4">
                        <h5 class="sub-faq">Infografías</h5>
                        <ul class="faq-list">
                            <li><a class="ps" href="#" id="g1">¿Cómo depositar?</a>
                                <p id="f1"></p>
                            </li>
                            <li><a class="ps" href="#" id="g2">¿Cómo jugar/participar?</a>
                                <p id="f2"></p>
                            </li>
                            <li><a class="ps" href="#" id="g3">¿Cómo retirar?</a>
                                <p id="f3"></p>
                            </li>
                            <li><a class="ps" href="#" id="g4">¿Cómo transferir?</a>
                                <p id="f4"></p>
                            </li>
                        </ul>
                        <div class="d-flex flex-column">
                            <a href="#" style="color: #138D75;" data-toggle="modal" data-target="#report">
                                <i class="fa-solid fa-circle-exclamation"></i> Reportar un problema
                            </a>
                            <a href="https://t.me/+rV3KWBuuKOtjYzkx" target="_blank" style="color: #138D75;">
                                <i class="fa-solid fa-comments"></i> Comunidad PlayRKE
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    @include('layouts/modals/report')
@endsection
@section('scripts')
    @vite('resources/js/helpers/faq.js')
@endsection
