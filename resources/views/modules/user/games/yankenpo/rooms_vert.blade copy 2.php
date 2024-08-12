@extends('layouts.app_rke')
<title>Picachu - PlayRKE</title>
<style>
    #container_ppt_gm {
        height: 83.25vh;
        border-radius: 12px;
        display: flex;
        padding: 0 45px;
        align-items: center;
        justify-content: center;
    }

    #content_info_ppt_gm {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        max-width: 360px;
        height: 475px;
        width: 100%;
        border-radius: 7px;
        background-color: rgb(255, 255, 255);
        border: 2px gray solid;
    }

    #info_opponent_ppt {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 50%;
        background-color: blueviolet;
        border-bottom: 1px black solid;
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
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        position: absolute;
        width: 60px;
        height: 60px;
        background-color: rgb(0, 156, 218);
    }

    #info_player_ppt {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 50%;
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
        height: 22%;
        background-color: yellow;
    }

    .items_ppt_gm {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px red solid;
        background-color: green;
    }
    .rounds_won_ppt_gm{
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
</style>
@section('content')
    <div id="container_ppt_gm">
        <div id="content_info_ppt_gm">
            <div id="info_opponent_ppt">
                <div class="content_items_ppt_gm">
                    <div class="items_ppt_gm">Cosmos</div>
                    <div id="txvs_time_ppt_gm">VS</div>
                    <div class="items_ppt_gm">Luis</div>
                </div>
                <div class="rounds_won_ppt_gm">
                    <div class="" id="">1</div>
                    <div class="" id="">2</div>
                    <div class="" id="">3</div>
                </div>
                <div id="anim_opponent_ppt_gm"></div>
            </div>
            <div id="tx_time_ppt_gm">10s</div>
            <div id="info_player_ppt">
                <div>
                    <span>Apuesta</span>
                    <span>BS 50000</span>
                </div>
                <div id="anim_player_ppt_gm"></div>
                <div class="content_items_ppt_gm">
                    <button class="items_ppt_gm">
                        Piedra
                        <img src="" alt="">
                    </button>
                    <button class="items_ppt_gm">
                        Papel
                        <img src="" alt="">
                    </button>
                    <button class="items_ppt_gm">
                        Tijera
                        <img src="" alt="">
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
