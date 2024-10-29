@extends('layouts.app_rke')
<title>Ruleta - PlayRKE</title>
@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap');

        .fd_rlt {
            position: relative;
            padding-bottom: 1.6rem;
            background-image: url(../img/body/fd_vd1.png);
            background-repeat: no-repeat;
            background-size: cover;
            /*background: radial-gradient(circle at 10% 20%, rgb(221, 49, 49) 0%, rgb(94, 0, 0) 90%);   */
        }

        .fd_ms {
            margin-top: -26px;
            border-top-left-radius: 18px;
            position: absolute;
            z-index: 0 !important;
            background: linear-gradient(to top, #4d2600, #2c190d);
            width: 260px;
            height: 80.1%;
        }

        .cont_gm {
            padding: 2%;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .cont_opc {
            position: absolute;
            z-index: 99 !important;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            padding: 0.3% 2%;
            background: linear-gradient(to bottom, #131313, #050000);
        }

        .c_rd {
            background: linear-gradient(to right, #ca201a, #8f0005);
        }

        .c_bk {
            background: linear-gradient(to right, #131313, #050000);
        }

        .color_ap {
            background-color: #292929 !important;
            border-radius: 50px;
            height: 40px;
            width: 40px;
        }

        .cont_bt_z {
            display: grid;
        }

        .bt_z {
            margin-left: -4px;
            width: 47px;
            margin-top: 0.25px;
            height: 78px;
            border: 1px solid rgb(255, 255, 255);
            font-size: 1.52rem;
            font-weight: 400;
            color: rgb(255, 255, 255);
            background: linear-gradient(to bottom, #009124, #007e26);
        }

        .bt_z:hover {
            opacity: 0.7 !important;
        }

        .rw_num {
            color: aliceblue;
            font-size: 1.25rem !important;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .num_quest,
        .bt_st,
        .bt_st_in,
        .rw_num {
            font-family: 'Jost', sans-serif;
        }

        .bt_rult {
            width: 53px;
            height: 53px;
            margin-right: -1px;
            margin-bottom: -1px;
            font-size: 1.52rem;
            font-weight: 400;
            color: rgb(255, 255, 255);
            background-color: transparent;
            border: 1px solid rgb(255, 255, 255);
        }

        .bt_st_in:hover,
        .bt_st:hover,
        .bt_rult:hover {
            opacity: 0.8 !important;
            color: rgb(235, 219, 0);
        }

        .bt_st {
            width: 208.2px;
            height: 45px;
            border: 1px solid rgb(255, 255, 255);
            background-color: transparent;
            color: rgb(255, 255, 255);
            font-size: 1.3rem;
        }

        .bt_st_in {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 104.2px;
            height: 45px;
            color: rgb(255, 255, 255);
            font-weight: 200;
            border: 1px solid rgb(201, 201, 201);
            background-color: transparent;
            font-size: 1.3rem;
        }

        .carousel {
            overflow: hidden;
        }

        .carousel__slider {
            position: relative;
            display: flex;
            align-items: center;
            width: 460px;
            height: 65px;
        }

        .carousel__list {
            position: absolute;
            width: 100%;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
        }

        .chip_ap {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 50px;
            width: 50px;
            margin: 0 2.75%;
            border: 2px solid #01003a;
            border-radius: 50px;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.3);
            cursor: pointer;
        }

        .chip_rst {
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgb(57, 44, 240);
            font-size: 1.2rem;
            height: 42px;
            width: 42px;
            margin-right: 1.75%;
            border: 2px solid #12116b;
            box-sizing: border-box;
            background-color: rgb(0, 0, 0);
        }

        .chip_ap:hover {
            transform: scale(1.1);
            font-size: 18px;
            background-color: rgba(255, 255, 255, 0.7);
        }

        .rt {
            height: 20px;
            width: 25px;
        }

        .cnt_chip {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .arrow {
            color: #9ec7d3;
            display: flex;
            align-items: center;
            font-size: 1.5rem;
        }

        .cont_vd {
            margin-left: -3% !important;
            z-index: 99 !important;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 454px;
            /*background-image: url(../img/body/wook.png);
                                                                                            background-repeat: no-repeat;
                                                                                            background-size: cover;*/
        }

        .rn_srt {
            max-height: 255px !important;
            width: 100%;
            border-top-right-radius: 136px;
            border-bottom-right-radius: 128px;
            box-shadow: 0px 0px 14px #272727de;
            backdrop-filter: blur(10px);
        }

        .fd_wook {
            position: absolute;
            height: 100%;
            width: 37.5%;
            z-index: 0;
            background-image: url(../img/body/wook.png);
            background-repeat: no-repeat;
            background-size: cover;
            /*border-top-right-radius: 290px;
                                                                                            border-bottom-right-radius: 290px;*/
        }

        .marco {
            margin-left: -3% !important;
            z-index: 99 !important;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 450px;
        }

        .st_ap {
            margin-left: -1.15%;
        }

        .br_tr {
            border-top-right-radius: 10px;
        }

        .br_tl {
            border-top-left-radius: 10px;
        }

        .br_br {
            border-bottom-right-radius: 10px;
        }

        .br_bl {
            border-bottom-left-radius: 10px;
        }

        .red_form,
        .black_form {
            width: 30px;
            height: 30px;
            background-color: #252525;
        }

        .to_lt {
            margin-left: 3.25px;
        }

        .cont_sup_if {
            display: flex;
            width: 100%;
            background: linear-gradient(to right, #0f0c29, #302b63, #24243e);
        }

        .last_chps {
            width: 40%;
        }

        .cont_info_ap {
            display: flex;
            width: 60%;
        }
    </style>
    <div class="col-12 mt-2">
        <div class="fd_rlt">
            <div class="cont_sup_if">
                <div class="last_chps">
                    <div style="display:flex; justify-content:center;">
                        <div class="chip_rst">17</div>
                        <div class="chip_rst">33</div>
                        <div class="chip_rst">10</div>
                        <div class="chip_rst">23</div>
                        <div class="chip_rst">0</div>
                        <div class="chip_rst">11</div>
                        <div class="chip_rst">14</div>
                        <div class="chip_rst">35</div>
                    </div>
                </div>
                <div class="cont_info_ap">
                    <div class="form-control">Cantidad de Jugadores: 325</div>
                    <div class="form-control" style="margin-left: 7%;">Apuesta Total: 2437,52 Bs</div>
                </div>
            </div>
            <h3 class="timer" style="text-align: center; color:aliceblue;"></h3>
            <div class="cont_gm">
                <div style="display: grid;">
                    <div class="marco">
                        <video class="rn_srt" id="srt_v">
                            <source src="{{ asset('srt/0.mp4') }}">
                        </video>
                    </div>
                    {{-- <button style="position: absolute;" id="probar">Prueba</button> --}}
                </div>
                <div class="cont_tbl">
                    <div class="cnt_chip st_ap">
                        <button class="bt_st br_tl">1st 12</button>
                        <button class="bt_st">2nd 12</button>
                        <button class="bt_st br_tr">3rd 12</button>
                    </div>
                    <div style="display: flex; align-items:center;">
                        <div class="cont_bt_z">
                            <button class="bt_z num_quest br_tl" value="00">00</button>
                            <button class="bt_z num_quest br_bl" value="0">0</button>
                        </div>
                        <div class="cont_fich">
                            <div class="cnt_chip">
                                <button class="bt_rult c_rd num_quest" value="3">3</button>
                                <button class="bt_rult c_bk num_quest" value="6">6</button>
                                <button class="bt_rult c_rd num_quest" value="9">9</button>
                                <button class="bt_rult c_rd num_quest" value="12">12</button>
                                <button class="bt_rult c_bk num_quest" value="15">15</button>
                                <button class="bt_rult c_rd num_quest" value="18">18</button>
                                <button class="bt_rult c_rd num_quest" value="21">21</button>
                                <button class="bt_rult c_bk num_quest" value="24">24</button>
                                <button class="bt_rult c_rd num_quest" value="27">27</button>
                                <button class="bt_rult c_rd num_quest" value="30">30</button>
                                <button class="bt_rult c_bk num_quest" value="33">33</button>
                                <button class="bt_rult c_rd num_quest" value="36">36</button>
                                <button class="bt_rult rw_num br_tr" value="336">2
                                    <small class="to_lt">to</small>1</button>
                            </div>
                            <div class="cnt_chip">
                                <button class="bt_rult c_bk num_quest" value="2">2</button>
                                <button class="bt_rult c_rd num_quest" value="5">5</button>
                                <button class="bt_rult c_bk num_quest" value="8">8</button>
                                <button class="bt_rult c_bk num_quest" value="11">11</button>
                                <button class="bt_rult c_rd num_quest" value="14">14</button>
                                <button class="bt_rult c_bk num_quest" value="17">17</button>
                                <button class="bt_rult c_bk num_quest" value="20">20</button>
                                <button class="bt_rult c_rd num_quest" value="23">23</button>
                                <button class="bt_rult c_bk num_quest" value="26">26</button>
                                <button class="bt_rult c_bk num_quest" value="29">29</button>
                                <button class="bt_rult c_rd num_quest" value="32">32</button>
                                <button class="bt_rult c_bk num_quest" value="35">35</button>
                                <button class="bt_rult rw_num" value="235">2
                                    <small class="to_lt">to</small>1</button>
                            </div>
                            <div class="cnt_chip">
                                <button class="bt_rult c_rd num_quest" value="3">1</button>
                                <button class="bt_rult c_bk num_quest" value="4">4</button>
                                <button class="bt_rult c_rd num_quest" value="7">7</button>
                                <button class="bt_rult c_bk num_quest" value="10">10</button>
                                <button class="bt_rult c_bk num_quest" value="13">13</button>
                                <button class="bt_rult c_rd num_quest" value="16">16</button>
                                <button class="bt_rult c_bk num_quest" value="19">19</button>
                                <button class="bt_rult c_bk num_quest" value="22">22</button>
                                <button class="bt_rult c_rd num_quest" value="25">25</button>
                                <button class="bt_rult c_rd num_quest" value="28">28</button>
                                <button class="bt_rult c_bk num_quest" value="31">31</button>
                                <button class="bt_rult c_rd num_quest" value="34">34</button>
                                <button class="bt_rult rw_num br_br" value="134">2
                                    <small class="to_lt">to</small>1</button>
                            </div>
                        </div>
                    </div>
                    <div class="cnt_chip st_ap">
                        <button class="bt_st_in br_bl">1<small>to</small>18</button>
                        <button class="bt_st_in">PAR</button>
                        <button class="bt_st_in c_rd">
                            <div class="red_form c_rd"></div>
                        </button>
                        <button class="bt_st_in c_bk">
                            <div class="black_form c_bk"></div>
                        </button>
                        <button class="bt_st_in">IMPAR</button>
                        <button class="bt_st_in br_br">19<small>to</small>36</button>
                    </div>
                </div>
            </div>
            <div class="cont_opc mt-2" style="z-index: 99;">
                <div style="width:40%; display:flex; justify-content:center;">
                    <i class="arrow fa-solid fa-caret-left"></i>
                    <button class="chip_ap" value="0.50">0.50</button>
                    <button class="chip_ap" value="1">1</button>
                    <button class="chip_ap" value="5">5</button>
                    <button class="chip_ap" value="10">10</button>
                    <button class="chip_ap" value="25">25</button>
                    <i class="arrow fa-solid fa-caret-right"></i>
                </div>
                <div style="width: 60%; display:flex;">
                    <button class="form-control mr-3" onclick="vidPlay()" id="spin_rt">Girar</button>
                    <button class="form-control mr-3" id="x2_ap">Duplicar</button>
                    <button class="form-control mr-3 "id="">Ultima AP</button>
                    <button class="form-control mr-3" id="clear_ap">Borrar</button>
                    <button class="form-control" id="">Repetir</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $("#probar").click(function(e) {
            val_st = $("#cont_car").val();
            if ($("#cont_car").html() == '') {
                val_st = 1;
                $("#cont_car").html('<li class="carousel__item cp_st">33</li>');
            } else {
                $("#cont_car").val = val_st + 1;
                cont = $("#cont_car").html();
                $("#cont_car").html('<li class="carousel__item cp_st">hh</li>' + cont);
            }
        });
        let arr = [],
            num_bet = [],
            coin = 0;
        $("#clear_ap").click(function(e) {
            e.stopPropagation();
            arr = [], num_bet = [], coin = 0;
        });

        $("#x2_ap").click(function(e) {
            let array_x2 = arr.map((num) => num * num);
            arr = array_x2;
        });

        $(".rw_num").click(function(e) {
            /*Apuesta Por Filas*/
            e.stopPropagation();
            if ($(this).val() == 134) {
                alert('134');
            }
            if ($(this).val() == 235) {
                alert('235');
            }
            if ($(this).val() == 336) {
                alert('336');
            }
        });

        $(".chip_ap").click(function(e) {
            e.stopPropagation();
            coin = parseFloat($(this).val(), 2);
        });

        $(".num_quest").click(function(e) {
            e.stopPropagation();
            let num_val = $(this).val();
            if (coin > 0) {
                if (arr == '') {
                    arr.push(num_val);
                    num_bet.push(coin);
                } else {
                    arr.map(function(element, key) {
                        if (element == num_val) {
                            num_bet[key] = num_bet[key] + coin;
                            console.log(arr[key], num_bet[key], 'suma');
                        } else {
                            if (arr.includes(num_val)) {
                                return true;
                            } else {
                                arr.push(num_val);
                                num_bet.push(coin);
                                return true;
                            }
                            console.log(num_val, coin, 'diferente');
                        }
                    });
                }
                ap_total = num_bet.reduce((a, b) => a + b, 0);
                alert(ap_total);
                console.log(arr, num_bet, 'arreglo');
            } else {
                alert("Selecione el valor de su apuesta");
            }
        });

        $("#spin_rt").click(function(e) {
            if (arr != '' && num_bet != '') {
                $.ajax({
                    url: "{{ route('ruleta.store') }}",
                    headers: {
                        'X-CSRF-TOKEN': $("#5K3_70K").val()
                    },
                    type: 'post',
                    dataType: 'json',
                    data: {
                        chips: arr,
                        chips_ap: num_bet
                    },
                    beforeSend: function() {},
                    success: function(data) {},
                    error: function() {}
                });
            }
        });

        window.onload = start_cont();

        function start_cont() {
            let contador1 = 11;
            let init = setTimeout(function incremento() {
                contador1--;
                $(".timer").html(contador1);
                let stop = setTimeout(incremento, 1000);
                if (contador1 <= 0) {
                    vidPlay();
                    clearTimeout(stop);
                }
            }, 1000);
        }

        var srt = document.getElementById('srt_v');

        function vidPlay() {
            srt.play();
        }
        srt.onended = function() {
            srt.load();
            srt.controls = false;
        };
    </script>
@endsection
