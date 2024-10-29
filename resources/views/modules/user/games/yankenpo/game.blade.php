@extends('layouts.app_rke')
<title>
    Picachu - PlayRKE</title>
<style>
    body {
        margin: 0 auto !important;
    }

    #container_ppt_gm_htz {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #content_info_ppt_gm_hzt {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        max-width: 480px;
        width: 100%;
        height: 290px;
        border-radius: 8px;
        background-color: rgb(255, 255, 255);
        border: 2px gray solid;
    }

    #info_opponent_ppt {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 30%;
        border-bottom: 1px black solid;
    }

    #container_anims_ppt {
        width: 100%;
        height: 70%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .anims_players {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 50%;
        height: 100%;
    }

    #anim_opponent,
    .bdr_won_opponent {
        border-right: 1px solid red;
    }

    #anim_player,
    .bdr_won_player {
        border-left: 1px solid red;
    }

    #txvs_time_ppt_gm {
        position: absolute;
        background-color: rgb(238, 238, 238);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 30px;
        width: 30px;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    #tx_time_ppt_gm {
        border-radius: 3px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: absolute;
        width: 50px;
        height: 26px;
        background-color: rgb(0, 156, 218);
    }

    #info_player_ppt {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 20.5%;
        background-color: rgb(226, 43, 43);
        border-top: 1px black solid;
    }

    #anim_player_ppt_gm,
    #anim_opponent_ppt_gm {
        background-color: rgb(52, 0, 119);
        width: 100%;
        height: 78%;
    }

    .content_items_ppt_gm {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        height: 100%;
        background-color: yellow;
    }

    .ctn_itms_gm_frt {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        height: 50%;
        background-color: yellow;
    }

    .items_ppt_gm {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px red solid;
    }

    .content_rounds_won_ppt_gm {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 50%;
        width: 100%;
    }

    .sub_container_rounds_won {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 50%;
        height: 82%;
    }

    .small_container_rounds_won {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 51.1%;
        background-color: blue;
    }

    .rounds_won {
        display: flex;
        justify-content: center;
        align-content: center;
        background-color: brown;
        margin: 0 1.3%;
        width: 33.3333%;
    }
</style>
@section('content')
    <div id="container_ppt_gm_htz">
        <div id="content_info_ppt_gm_hzt">
            <div id="info_opponent_ppt">
                <div class="ctn_itms_gm_frt">
                    <div class="items_ppt_gm">
                        @if ($name_user->user == auth()->user()->id)
                            {{ $name_user->username }}
                        @endif
                    </div>
                    <div id="txvs_time_ppt_gm">VS</div>
                    <div class="items_ppt_gm">
                        @if ($name_user->user != auth()->user()->id)
                            {{ $name_user->username }}
                        @endif
                    </div>
                </div>
                <div class="content_rounds_won_ppt_gm">
                    <div class="sub_container_rounds_won bdr_won_opponent">
                        <div class="small_container_rounds_won">
                            <div class="rounds_won" id="opp_rnd_1">1</div>
                            <div class="rounds_won" id="opp_rnd_2">2</div>
                            <div class="rounds_won" id="opp_rnd_3">3</div>
                        </div>
                    </div>
                    <div class="sub_container_rounds_won bdr_won_player">
                        <div class="small_container_rounds_won">
                            <div class="rounds_won" id="ply_rnd_1">1</div>
                            <div class="rounds_won" id="ply_rnd_2">2</div>
                            <div class="rounds_won" id="ply_rnd_3">3</div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="container_anims_ppt">
                <div class="anims_players" id="anim_opponent"></div>
                <div id="tx_time_ppt_gm">10</div>
                <div class="anims_players" id="anim_player"></div>
            </div>
            <div id="info_player_ppt">
                <div class="content_items_ppt_gm">
                    <button class="btn_ppt items_ppt_gm" value="1">
                        Piedra
                        <img src="{{ asset('img/rke.png') }}" alt="Piedra">
                    </button>
                    <button class="btn_ppt items_ppt_gm" value="2">
                        Papel
                        <img src="{{ asset('img/rke.png') }}" alt="Papel">
                    </button>
                    <button class="btn_ppt items_ppt_gm" value="3">
                        Tijera
                        <img src="{{ asset('img/rke.png') }}" alt="Tijera">
                    </button>
                    <button class="btn_ppt items_ppt_gm" value="4" title="Cancelar">
                        <i class="fa-solid fa-circle-xmark" style="color: #b02121;"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        ///Aleatorio==''  ///Piedra==1  ///Papel==2  ///Tijera==3 ///Cancelar==4
        var time_round = @json($room->tm_close_round);
        var tm_serv = Math.floor(Date.now() / 1000);
        if (time_round <= tm_serv) {
            process_round();
        } else {
            ///Contador de tiempo para mostrar la eleccon de los jugadores
            let init = setTimeout(function c_time() {
                tm_stop = time_round - tm_serv;
                $("#tx_time_ppt_gm").html(tm_stop);
                tm_stop--;
                let stop = setTimeout(c_time, 1000);
                if (tm_stop <= 0) {
                    process_round();
                    $("#tx_time_ppt_gm").html('VS');
                    $('.btn_ppt').prop("disabled", false);
                    clearTimeout(stop);
                }
            }, 1000);
            ///Contador de tiempo para mostrar la eleccon de los jugadores
        }

        ///Enviar Eleccion
        $(".btn_ppt").click(function() {
            $.ajax({
                url: "{{ route('yankenpo.select_ppt') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                },
                method: 'post',
                dataType: 'json',
                data: {
                    room: @json($room->id),
                    ppt: $(this).val()
                },
                success: function(data) {}
            });
        })

        ///Procesar Ronda y Comprobar Ganador
        function process_round() {
            $.ajax({
                url: "{{ route('yankenpo.check_ppt') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                },
                method: 'post',
                dataType: 'json',
                data: {
                    room: @json($room->id)
                },
                success: function(data) {
                    mark_round(data['rnd_vn']);
                }
            });
        }

        function mark_round(rnd_vn) {
            ///Funcion para marcar rondas a nivel de vista
            if (rnd_vn == 1) {

            } else {

            }
        }
    </script>
@endsection
