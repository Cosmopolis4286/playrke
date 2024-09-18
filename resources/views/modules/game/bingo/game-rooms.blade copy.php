@extends('layouts.app_rke')
@section('title', 'BingoCash')
{{-- @section('styles')<link rel="stylesheet" href="{{ asset('css/bingocash.css') }}">@endsection --}}
<style>
    body {
        background: url(../img/body/space.png);
    }



     /*Fonts*/
 @import url('https://fonts.googleapis.com/css2?family=Righteous&display=swap');
 @import url('https://fonts.googleapis.com/css2?family=Alfa+Slab+One&display=swap');
 @import url('https://fonts.googleapis.com/css2?family=Anton&display=swap');
 @import url('https://fonts.googleapis.com/css2?family=Braah+One&display=swap');

 #tx_rm_triv {
     color: #DADADA;
     font-size: 29.5px;
     text-align: center;
     display: grid;
     width: 100% !important;
 }

 #tx_rm_triv>a {
     margin-top: -0.65rem;
     text-decoration: none;
     font-size: 15.5px !important;
     color: rgba(0, 138, 148, 0.945)
 }

 @media screen and (max-width: 339px) {
     #tx_rm_triv {
         font-size: 26.5px;
     }
 }

 .cont_mdl_inf_game {
     height: 80vh;
     position: relative !important;
 }

 #info_game {
     display: none;
     margin: 0 auto;
     padding: 10px;
     justify-content: left;
     position: absolute;
     background-color: aliceblue;
     border-radius: 7px;
     z-index: 99 !important;
     left: 50%;
     top: 50%;
     width: 100%;
     box-shadow: 0px 5px 13px 0px rgba(22, 76, 192, 0.395);
     max-width: 570px !important;
     transform: translate(-50%, -50%);
 }

 #info_game_body {
     color: black !important;
     overflow-y: scroll;
     max-height: 370px;
 }

 /*Modal Lista Jugadore*/
 #list_players_bingo {
     display: none;
     margin: 0 auto;
     padding: 10px;
     justify-content: left;
     position: absolute;
     background-color: aliceblue;
     border-radius: 7px;
     z-index: 9999 !important;
     left: 50%;
     top: 32%;
     width: 100%;
     box-shadow: 0px 5px 13px 0px rgba(22, 76, 192, 0.395);
     max-width: 270px !important;
     transform: translate(-50%, -50%);
 }

 #list_players_bingo_body {
     color: black !important;
     overflow-y: scroll;
     max-height: 370px;
 }

 .cont_inf_player_bing {
     margin: 15px auto !important;
     display: flex;
     align-items: center;
 }

 .cont_img_pj_bing img {
     display: flex;
     align-items: center;
     justify-content: center;
 }

 .name_player_bing_mdl {
     font-weight: 500;
     font-size: 15.75px;
     margin-left: 6.85px;
 }

 /* Modal Compra */
 #mc_buy {
     display: none;
     padding: 11px;
     justify-content: left;
     position: absolute;
     background-color: aliceblue;
     border-radius: 10px;
     z-index: 99 !important;
     left: 50%;
     top: 350%;
     width: 100%;
     box-shadow: 0px 5px 13px 0px rgba(22, 76, 192, 0.395);
     max-width: 360px;
     transform: translate(-50%, -50%);
 }

 .mdl_body_bg {
     border-top-left-radius: 8px;
     border-top-right-radius: 8px;
     padding: 11.5px;
     background-color: gray;
 }

 .bg_bot_dv {
     position: relative !important;
     display: flex;
     height: 55px;
     width: 100% !important;
 }

 .cont_cfre_bot_bg {
     position: relative !important;
     width: 69%;
     display: flex;
     padding: 1.15% !important;
     border-top-left-radius: 7px;
     background: linear-gradient(to bottom, #0b4cfde3, #124bc5);
 }

 .cont_img_cfre {
     width: 23%;
     height: 100% !important;
     display: flex;
     justify-content: center;
     align-items: center;
 }

 .img_cf {
     max-height: 53px;
 }

 .cont_coin_bot_bg {
     position: relative !important;
     display: flex;
     width: 77%;
     height: 100% !important;
     justify-content: center;
     flex-direction: column;
 }

 #prize_bg_mdl {
     display: flex;
     align-items: center;
     height: 50%;
 }

 .bot_tx {
     display: flex;
     align-items: center;
     height: 50%;
     font-size: 15.6px;
     margin-right: 3px;
     color: rgb(255, 255, 255);
     font-weight: 500;
 }

 #prize_bg_mdl small {
     display: flex;
     align-items: center;
     height: 50%;
     padding-top: 1px;
     font-size: 14px;
     color: rgb(219, 219, 219);
     font-weight: 600;
 }

 #bt_num_lt {
     height: 50%;
     display: flex;
     align-items: center;
     font-size: 23px;
     color: rgb(255, 230, 0);
 }

 #cont_coin_bot_bg small {
     margin-left: 1.5px;
     justify-content: flex-end;
     font-size: 16px;
 }

 .date_bg {
     position: relative;
     width: 31%;
     border-top-right-radius: 7px;
     height: 100% !important;
     padding: 1.15% !important;
     display: flex;
     align-items: center;
     flex-direction: column;
     color: rgb(224, 224, 224);
     font-weight: 400;
     background-color: #013bb9;
 }

 .cont_date_time {
     height: 50%;
     width: 100% !important;
     display: flex;
     justify-content: space-between;
     align-items: center;
 }

 .ico_cl {
     color: #008afc;
     margin-right: 2.75px;
 }

 .ico_cl_rlg {
     color: #008afc;
     font-size: 12.2px;
     margin-right: 2.75px;
 }

 #dat_f {
     font-size: 17px;
 }

 #da_tm {
     font-size: 17.3px;
 }

 #info_buy {
     margin-bottom: 0 !important;
 }

 .bg_body_mdl {
     position: relative !important;
     display: flex;
     flex-direction: column;
     width: 100%;
 }

 .cont_info_bing_mdl {
     display: flex;
     align-items: center;
     justify-content: space-between;
     padding-left: 2.5px;
     padding-right: 2.5px;
     line-height: 20px;
     border-bottom: 1px solid rgba(0, 75, 187, 0.521);
     border-left: 1px solid rgba(0, 75, 187, 0.521);
     border-right: 1px solid rgba(0, 75, 187, 0.521);
 }

 .inf_lg_bing_mdl {
     color: rgb(233, 161, 68) !important;
     margin-right: 1.5px;
 }

 .cont_pjs_bing {
     cursor: pointer;
     color: rgb(1, 64, 158)
 }

 .cont_inf_buy_bing_mdl {
     margin-top: 6px;
 }

 .content_line_bing {
     display: flex;
     justify-content: center;
     align-items: center;
     position: relative !important;
     width: 100%;
 }

 .colum_bg_left {
     width: 60%;
 }

 .buy_ctn {
     color: rgb(7, 60, 129);
     font-size: 16px;
     font-weight: 500;
 }

 .cant_ctns_mdl_bg {
     position: relative !important;
     display: flex;
     align-items: center;
     height: 100% !important;
 }


 .colum_bg_right {
     width: 40%;
     display: flex;
     justify-content: right;
 }

 .cont_automc_use {
     display: flex;
     justify-content: space-between;
     align-items: center;
     padding-top: 2px;
 }

 #txt_automc_use {
     font-size: 1.08em;
     margin-right: 3px;
 }

 .cont_bg_f {
     width: 100%;
     height: 45px !important;
     display: flex;
     justify-content: center;
     align-items: center;
 }

 .tx_cctn {
     display: flex;
     align-items: center;
     height: 100%;
     margin-right: 9px;
 }

 .quantity {
     display: flex;
     margin-top: -3px;
     justify-content: center;
     align-items: center;
     height: 100%;
 }

 .quant {
     background-color: #c4e7ff;
     display: flex;
     justify-content: center;
     align-items: center;
     cursor: pointer;
     border-radius: 50%;
     height: 24.5px;
     width: 24.5px;
     font-weight: 500;
 }

 .quantity-down {
     padding-top: 2.5px;
 }

 .quantity-up {
     padding-top: 1.5px;
     padding-left: 1px;
 }

 input[type=number]::-webkit-inner-spin-button,
 input[type=number]::-webkit-outer-spin-button {
     -webkit-appearance: none;
     margin: 0;
 }

 input[type=number] {
     -moz-appearance: textfield;
 }

 .quantity input {
     width: 45px;
     text-align: center;
     font-size: 24.5px;
     padding-top: 2px;
     background-color: transparent;
     border: transparent;
 }

 .quantity input:focus {
     outline: 0;
 }



 .hide_info_bg {
     display: none;
 }



 #txt_automc_sn {
     font-size: 0.87em;
 }

 @media screen and (max-width: 367px) {
     #bt_num_lt {
         font-size: 21.5px;
     }

     #dat_f {
         font-size: 15.35px;
     }

     #da_tm {
         font-size: 15.05px;
     }

     #txt_automc_sn {
         font-size: 0.78em !important;
     }
 }

 #aum_in_use {
     font-size: 18.5px;
 }

 .cont_tx_total {
     display: flex;
     align-items: center;
     justify-content: right;
 }

 .tot_tx {
     font-size: 1.05em;
     margin-right: 4px;
 }

 #cant_total_mdl_bg {
     display: flex;
     font-size: 1.35em;
     align-content: center;
 }

 #cant_total_mdl_bg small {
     font-size: 11.5px;
     margin-left: 1.5px;
 }



 .cont_img_ctn {
     width: 30px;
     margin: 0 2.15%;
 }

 .cont_img_ctn img {
     width: 100%;
 }

 /* Modal compra */

 /*Lista Sala Bingo*/
 .s_room {
     font-family: 'Righteous', cursive;
     font-size: 31px;
     font-weight: 500;
     margin-bottom: 8px;
     text-shadow: 2px 2px 0 #333333b9;
     color: white;
 }

 .ct_center {
     position: relative;
     display: grid;
     justify-content: center;
 }

 .jus_f {
     display: flex;
     justify-content: center;
 }

 .space_ctn {
     display: flex;
     justify-content: space-between;
 }



 .tx_time {
     margin-top: -8px;
     font-size: 9.4px;
 }

 .s_bgx {
     text-align: center;
     text-decoration: none;
     cursor: default;
     color: rgb(24, 24, 24);
 }

 .s_bgx:hover {
     color: rgb(24, 24, 24);
 }

 .comp_cartn {
     cursor: pointer;
 }

 .box_rbg {
     position: relative;
     width: 242px;
     padding: 5.5px;
     box-shadow: 0px 4px 14px 0px rgba(34, 88, 204, 0.493);
     border-radius: 20px;
     background: linear-gradient(180deg, #2af598 0%, #009efd 100%);
     margin: 11% 0;
 }

 /*Efecto-Pulso*/
 .box_rbg_ad {
     position: relative;
     width: 208px;
     padding: 5px;
     border-radius: 20px;
     animation: pulse;
     animation-duration: 1.6s;
     animation-iteration-count: infinite;
     background: linear-gradient(180deg, #2af598 0%, #009efd 100%);
     margin-bottom: 20.5%;
 }

 .cont_rbg {
     position: relative;
     display: grid;
     background: linear-gradient(to top, rgb(108, 214, 247), #ffe2e2 70%, #5bd9ff 25%);
     border-radius: 18px;
 }

 .time_bg {
     background-color: #333;
     color: whitesmoke;
     border-radius: 11px;
     height: 36px;
     font-size: 18.25px;
     font-weight: 700;
     width: 124px;
     position: absolute;
     padding: 0 5.75px;
     padding-bottom: 1px;
     top: -1.3%;
     left: 50%;
     transform: translate(-50%, -50%);
     display: flex;
     align-items: center;
     justify-content: center !important;
 }

 .time_dt {
     display: flex;
     align-items: center;
     justify-content: center;
     flex-direction: column;
     padding-bottom: 3px;
     margin: 0 4px;
 }

 .i_crt_full {
     color: rgb(255, 240, 240);
     font-weight: 500;
     border-radius: 8px;
     width: 82.5%;
     height: 25px;
     background: linear-gradient(to bottom, #941725e8, #631209e8);
     position: absolute;
     top: 14.5%;
     left: 50%;
     font-size: 14.15px;
     transform: translate(-50%, -50%);
 }

 .crtn_buy {
     display: flex;
     justify-content: center;
     align-items: center;
     position: absolute;
     color: #e0b700;
     top: 57.3%;
     left: 102%;
     transform: translate(-50%, -50%);
     background: linear-gradient(to bottom, rgb(1, 56, 160), rgba(0, 44, 126, 0.975));
     width: 20.2%;
     height: 25.8px;
     padding-top: 0.9px;
     padding-right: 1px;
     border-radius: 7px;
     font-size: 18px;
     text-transform: uppercase;
 }

 .num_ct {
     color: antiquewhite;
     font-size: 18px !important;
 }

 .ic_atm {
     position: absolute;
     top: 85.95%;
     left: 7.75%;
     color: rgb(0, 84, 240);
 }

 .img_rbg {
     border-top-left-radius: 10%;
     border-top-right-radius: 10%;
     width: 100%;
 }

 .b_te {
     margin-top: -3.25px;
     font-size: 15px;
     font-weight: 600;
     margin-bottom: 1%;
 }

 .bot_cant {
     font-weight: 700;
     margin-bottom: 2.3%;
     color: #0049cf;
     margin-top: -5%;
     margin-bottom: -0.5%;
     font-size: 1.92rem;
     text-shadow: 1.5px 1px 0 #0099ff;
 }

 .cont_price_bg {
     margin-top: 1.5%;
     display: flex;
     justify-content: center;
     align-items: center;
     height: 54px;
 }

 .bgs_bs {
     margin-right: -2%;
     font-size: 1.25rem !important;
 }

 .i_crt_bs {
     width: 56%;
     color: #000b24;
     font-weight: 500;
     font-size: 13.75px;
     margin: 0 auto;
     background-color: rgb(182, 235, 241);
     margin-bottom: 7.7%;
     border-radius: 4px;
 }

 .t_bg_hour {
     width: 75%;
     padding-top: 2px;
     background: linear-gradient(360deg, rgba(226, 192, 25, 0.966) 0%, rgba(243, 240, 54, 0.986)100.2%);
     border-radius: 9.5px;
     position: absolute;
     bottom: -9.25%;
     left: 50%;
     transform: translate(-50%, -50%);
     text-transform: uppercase;
     letter-spacing: 0.8px;
     font-weight: 600;
     font-size: 11.5px;
     color: rgb(0, 0, 0);
 }



 /*Lista Sala Bingo*/

 /*Sala Bingo*/

 .bing_cont_bt {
     position: relative !important;
     display: flex;
     align-items: center;
     justify-content: space-between;
     width: 100%;
     height: 80px;
     background: linear-gradient(to bottom, #0b4cfde3, #003bb9);
     border-radius: 17px;
     padding: 0 2.5%;
 }


 .cont_bt_rm {
     position: relative;
     display: flex;
     height: 100%;
     width: 60%;
     align-items: center;
 }

 .content_cfe_bg {
     position: relative;
     height: 100%;
 }

 .cfe_bg {
     padding-bottom: 2.5%;
     margin-left: -8px;
     height: 100%;
 }

 .cont_img_rm {
     position: relative;
     height: 100%;
     width: 40%;
 }

 #cont_tx_bot {
     display: grid;
     margin-left: -3.75px;
 }

 #cont_bot_bg_rm {
     position: relative;
     display: flex;
     align-items: center;
     justify-content: space-between;
 }

 .bot_bg_rm {
     color: rgb(255, 255, 255);
     font-size: 19.5px;
     font-weight: 400;
 }

 #cont_icon_sound {
     height: 100%;
     display: flex;
     justify-content: center;
     align-items: center;
 }

 #control_sound {
     font-size: 19.5px;
     color: #2de7ff;
     cursor: pointer;
 }

 .cont_img_bg {
     height: 180%;
     display: flex;
     flex-direction: column;
     align-items: center;
     position: absolute;
     top: -28%;
     right: 0;
     transform: translate(0%, -25%);
 }

 .img_bg {
     height: 100%;
 }

 .md_gm_rm {
     color: rgb(253, 250, 250);
     font-weight: 600;
     margin-top: -10%;
     border-radius: 8px;
     width: 81%;
     text-align: center;
     margin: 0 auto;
     z-index: 99999 !important;
     background: linear-gradient(to bottom, #c20c1be8, #790038e8);
     font-size: 11.75px;
 }

 #container_list_chips {
     position: relative;
     margin-top: 5px;
     padding: 0 7.5px;
     display: flex;
     align-items: center;
 }

 .arrow_list_chips {
     height: 100%;
     padding-top: 2.5px;
     display: flex;
     justify-content: center;
     align-items: center;
     cursor: pointer;
     color: rgb(83, 0, 151);
     font-size: 1.55em;
     transition: 3ms ease;
 }

 .arrow_list_chips:hover {
     transform: scale(1.10);
 }

 #arrow_left,
 #arrow_right {
     display: none;
 }

 #content_list_chips {
     background: rgb(17, 0, 48);
     border-radius: 12px;
     margin: 0 5px;
     padding: 4px;
     display: flex;
 }

 .content_num_list {
     position: relative;
     display: flex;
     justify-content: center;
     align-items: center;
     height: 30px;
     width: 30px;
     margin: 0 3px;
     border-radius: 50%;
     transition: 3ms ease;
 }

 .content_num_list img {
     width: 100%;
 }

 #cont_players {
     display: flex
 }

 .img_players {
     max-height: 60px;
     width: auto;
 }

 .bot_bg_rm {
     color: rgb(255, 255, 255);
     font-size: 19.5px;
     font-weight: 400;
 }

 .num_bt_rm {
     margin-top: -6%;
     font-size: 24px;
     letter-spacing: 1.5px;
     font-family: 'Alfa Slab One', cursive;
     color: rgb(255, 222, 34);
 }

 .num_bt_rm small {
     color: rgb(244, 255, 251)
 }

 #tx_tm_stop {
     height: 100%;
     padding-right: 5px;
     display: flex;
     justify-content: center;
     align-items: center;
     font-family: 'Righteous', cursive;
     font-size: 4.65vmax;
     font-weight: 500;
     color: rgb(0, 233, 89);
 }

 .sep_bg_rm {
     margin-bottom: 27px;
 }

 #container_fnd_bingo {
     position: relative;
     width: 100%;
     padding: 0 1.7%;
 }

 #content_gm_bg {
     position: relative;
     max-width: 1092px !important;
     margin: 0 auto;
 }

 #win_mdl {
     display: none;
     position: absolute;
     z-index: 999 !important;
     width: 100%;
     left: 50%;
     top: 140%;
     background-color: white;
     border-radius: 10px;
     box-shadow: 0px 5px 12px 0px rgba(14, 59, 156, 0.395);
     max-width: 330px;
     transform: translate(-50%, -50%);
 }

 .cont_img_uswin {
     margin: 0 auto;
     position: relative;
 }

 .eyevwctn {
     position: absolute;
     z-index: 999 !important;
     font-size: 14.5px;
     color: rgb(143, 143, 143);
     top: 12.15%;
     left: 2.5%;
     transform: translate(-50%, -50%);
 }

 .img_us {
     max-height: 36px !important;
 }

 .name_us {
     margin-top: 3.25px;
     padding-top: 4px !important;
     font-size: 14.75px !important;
 }

 .mont_bg {
     margin-top: 2px;
     margin-bottom: 2px;
     padding-top: 5px !important;
     font-size: 12px !important;
 }

 .center_tx_bg {
     display: flex;
     justify-content: center;
     align-items: center;
 }

 .btn_close_bg_md {
     cursor: pointer;
 }

 #cnt_wns {
     font-size: 17px;
     font-weight: 500;
 }

 #chp_wn_bing {
     width: 29px;
     height: 29px;
 }

 #chp_wn_bing:hover {
     transform: scale(1.1);
 }

 #conten_wns {
     margin-top: 1.5px;
     overflow-y: scroll;
     max-height: 200px
 }

 #sin_wn {
     display: none;
     flex-direction: column
 }

 .tx_swn_bg {
     text-transform: uppercase;
     font-size: 18px;
     margin-bottom: -4px;
     color: rgba(0, 0, 44, 0.925);
     font-weight: 500;
     display: flex;
     justify-content: center;
 }

 .txs_swn_bg {
     text-transform: uppercase;
     font-size: 12.75px;
     display: flex;
     justify-content: center;
     margin-bottom: -5%;
 }

 #mdl_view_ctn {
     display: none;
     position: absolute;
     z-index: 9999 !important;
     left: 50%;
     top: 50%;
     background-color: white;
     border-radius: 6px;
     box-shadow: 0px 5px 12px 0px rgba(14, 59, 156, 0.395);
     max-width: 350px;
     transform: translate(-50%, -50%);
 }

 #cont_nm_uctn {
     display: flex;
     justify-content: center;
     align-items: center;
     width: 100%;
 }

 #name_pj_view_ctn {
     font-weight: 500;
     font-size: 16px;
     margin-left: 3px;
 }

 .sz_ctn_bg {
     margin-top: 36px !important;
 }

 .cont_ctn_gm {
     display: flex;
     justify-content: center;
     align-items: center;
 }

 .b_go {
     text-align: center;
 }

 .br_tl_bg {
     border-top-left-radius: 5px;
 }

 .br_tr_bg {
     border-top-right-radius: 5px;
 }

 .br_bl_bg {
     border-bottom-left-radius: 5px;
 }

 .br_br_bg {
     border-bottom-right-radius: 5px;
 }

 .tx_bg {
     padding-top: 3px;
     display: flex;
     align-items: center;
     justify-content: center;
     height: 38px;
     font-size: 17.75px;
     font-family: 'Alfa Slab One', cursive;
     font-weight: 600;
     color: #120f20ef;
     background-color: rgb(255, 255, 255);
 }

 .num_bg {
     display: flex;
     justify-content: center;
     align-items: center;
     width: 48px;
     height: 48px;
     border: 1px solid rgb(255, 255, 255);
     background-color: rgb(222, 233, 255)
 }

 .ctn_lgo {
     padding-top: 1px;
     height: 30px;
 }

 .bt_num,
 .bt_num_vw {
     padding-top: 2.5px;
     background-color: transparent;
     border: transparent;
     font-size: 16.5px;
     letter-spacing: 0.5px;
     font-family: 'Anton', sans-serif;
     color: #3d3d3d;
 }

 .bt_bingo {
     margin-top: 4% !important;
     padding-top: 3.85px;
     height: 31px;
     width: 156px;
     border-radius: 7px;
     border: 2px solid #940025c7;
     background: radial-gradient(circle at 50.4% 50.5%, rgb(197, 8, 55) 0%, rgb(110, 0, 6) 90%);
     color: rgb(255, 255, 255);
     font-size: 18.5px;
     font-family: 'Alfa Slab One', cursive;
     letter-spacing: 3.85px;
     text-transform: uppercase;
     margin: 0 auto;
     display: flex;
     align-items: center;
     justify-content: center;
 }

 .circle_bg {
     height: 82%;
     width: 82%;
     border: 2px solid #ff843d;
     background: linear-gradient(to bottom, #e2122bf6, rgb(184, 0, 61));
     color: rgb(255, 255, 255) !important;
     display: flex;
     justify-content: center;
     align-items: center;
     border-radius: 50%;
 }

 .tx_mdl_fn {
     color: #001033;
     margin-top: -2.75%;
     font-size: 19px;
     text-transform: uppercase;
     font-weight: 500;
 }

 .tx_sub_bg {
     font-size: 14.25px;
     text-transform: uppercase;
     font-weight: 400;
     margin-bottom: -5.25%;
 }

 .btn_mdl_bg {
     font-size: 14.35px !important;
     letter-spacing: 0.5px;
     text-transform: uppercase;
 }

 @media (max-width:520px) {
     #container_list_chips {
         margin-top: 14px !important;
     }

     .content_num_list {
         margin: 0 2.75px;
         width: 27.5px !important;
         height: 27.5px !important;
     }
 }

 @media screen and (max-width: 565px) {
     .bing_cont_bt {
         max-height: 60px;
     }

     .cont_img_bg {
         height: 154%;
     }

     .md_gm_rm {
         width: 95% !important;
     }

     .num_bg {
         width: 8.52vw;
         height: 8.52vw;
     }

     .ctn_lgo {
         height: 5.25vw;
     }

     .bt_bingo {
         padding-top: 3px;
         width: 87.5%;
         height: 28px;
         font-size: 15.5px;
     }
 }

 @media screen and (min-width: 566px) {
     .md_gm_rm {
         margin-top: -1%;
         font-size: 14.5px;
     }
 }

 .chips {
     display: none !important;
 }

 #container_sup_chips {
     position: relative;
     display: flex;
     align-items: center;
     width: 100%;
     padding: 0 7.5px;
     justify-content: space-between;
     margin-bottom: 6px;
 }

 #cont_chips {
     position: relative;
     display: flex;
     height: 100%;
     gap: 5.15px;
     align-items: center;
 }

 .cont_loader {
     display: flex;
     justify-content: center;
     margin-bottom: 0.45%;
 }

 .lds-dual-ring {
     display: inline-block;
     width: 80px;
     height: 80px;
 }

 .lds-dual-ring:after {
     content: " ";
     display: block;
     width: 64px;
     height: 64px;
     margin: 8px;
     border-radius: 50%;
     border: 6px solid #fff;
     border-color: #fff transparent #fff transparent;
     animation: lds-dual-ring 1.2s linear infinite;
 }

 @keyframes lds-dual-ring {
     0% {
         transform: rotate(0deg);
     }

     100% {
         transform: rotate(360deg);
     }
 }

 .zoom {
     zoom: 119.15% !important;
 }

 .img_chips {
     width: 68px;
     height: 68px;
 }

 .chip_hide {
     opacity: 0;
 }

 .chip_show {
     opacity: 1 !important;
     transition-duration: 0.35s !important;
 }

 #cont_tx_check_bing {
     display: none;
     position: absolute;
     bottom: 0%;
     left: 1%;
 }

 #tx_check_bing {
     border-radius: 9px;
     display: flex;
     justify-content: center;
     background: #bbbbbbd0;
     padding: 0 6px;
 }

 @media screen and (max-width: 505px) {
     #cont_chips {
         gap: 2.8px;
     }

     .img_chips {
         width: 13.2vmin;
         height: 13.2vmin;
     }

     .bot_bg_rm {
         font-size: 14px;
     }

     .num_bt_rm {
         font-size: 4.25vmin;
     }

     .num_bt_rm small {
         color: rgb(241, 241, 241);
         line-height: 9px;
     }
 }

 #com_bin {
     color: #1a2f5c;
 }
</style>
@section('content')
    <div class="cont_mdl_inf_game">
        <div id="info_game">
            <div id="info_game_body"></div>
            <div class="cont_bg_f">
                <button id="cls_mdl_inf_gm" type="button" class="btn btn-secondary">Cerrar</button>
            </div>
        </div>
        <div id="list_players_bingo">
            <div id="list_players_bingo_body"></div>
            <div class="cont_bg_f mt-n1">
                <button id="cls_mdl_inf_players" type="button" class="btn btn-secondary">Cerrar</button>
            </div>
        </div>
        <div style="position: relative">
            <div id="tx_rm_triv">SALAS DE JUEGO
                @if ($wallet->coin > 0)
                    {{-- <a href="{{ route('bingo.rooms', ['coinSym' => $wallet->Coin->name]) }}">
                        <span>Cambiar a {{ $wallet->Coin->info }}</span>
                    </a> --}}
                @endif
            </div>
            <div id="mc_buy">
                <div class="bg_bot_dv">
                    <div class="cont_cfre_bot_bg">
                        <div class="cont_img_cfre">
                            <img class="img_cf" src="{{ asset('img/cofre.png') }}" alt="IMG">
                        </div>
                        <div class="cont_coin_bot_bg">
                            <div id="prize_bg_mdl">
                                <div class="bot_tx"></div>
                                <small>USDT</small>
                            </div>
                            <div id="bt_num_lt">
                                <span id="v_bot"></span>
                            </div>
                        </div>
                    </div>
                    <div class="date_bg">
                        <div class="cont_date_time">
                            <i class="ico_cl fa-regular fa-calendar-days"></i>
                            <span id="dat_f"></span>
                        </div>
                        <div class="cont_date_time">
                            <i class="ico_cl_rlg fa-solid fa-clock"></i>
                            <span id="da_tm"></span>
                        </div>
                    </div>
                </div>
                <form id="info_buy" action="#" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="bg_body_mdl">
                        <div class="cont_info_bing_mdl">
                            <a href="#" id="ck_inf_gm">
                                <i class="inf_lg_bing_mdl fa-solid fa-circle-info"></i>
                                Info. del Juego
                            </a>
                            <div class="cont_pjs_bing">
                                <span id="cant_pjs_bing"></span>
                                <input type="hidden" id="val_pjs_bing">
                                <i class="fa-solid fa-users"></i>
                            </div>
                        </div>
                        <div class="cont_inf_buy_bing_mdl">
                            <div class="content_line_bing">
                                <div class="colum_bg_left">
                                    <input type="hidden" id="id_bing" name="bingoid">
                                    <div class="buy_ctn">Compra tus cartones:</div>
                                </div>
                                <div class="colum_bg_right">
                                    <div class="cont_automc_use" id="aum_buy" style="display: none">
                                        <div id="txt_automc_use">Automarcar</div>
                                        <i style="color: #004bbbe0;" class="fa-solid fa-circle-check"></i>
                                    </div>
                                    <div class="hide_info_bg" id="aum_in_use">
                                        <input type="checkbox" id="buy_autom" name="auto_mc" value="0">
                                        <span id="txt_automc_sn">Automarcar</span>
                                    </div>
                                </div>
                            </div>
                            <div class="content_line_bing">
                                <div class="colum_bg_left cant_ctns_mdl_bg">
                                    <input type="hidden" id="type_bt" name="type_bt">
                                    <input type="hidden" id="precio">
                                    <div class="tx_cctn">Cantidad</div>
                                    <div class="quantity">
                                        <div class="quant quantity-down">
                                            <i class="fa-solid fa-minus"></i>
                                        </div>
                                        <input id="cantidad" name="cantidad" type="number" step="1" max="25"
                                            min="1" value="1">
                                        <div class="quant quantity-up">
                                            <i class="fa-solid fa-plus"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="colum_bg_right cont_tx_total">
                                    <span class="tot_tx">Total:</span>
                                    <div id="cant_total_mdl_bg">
                                        <span class="total"></span><small>USDT</small>
                                    </div>
                                    <input type="hidden" class="total" name="montop">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="cont_bg_f">
                    <button id="close_mdl" type="button" class="btn btn-secondary">CANCELAR</button>
                    <div class="cont_img_ctn">
                        <img src="{{ asset('img/rke.png') }}">
                    </div>
                    <button type="button" class="btn btn-primary" id="buy_bg">COMPRAR</button>
                </div>
            </div>
        </div>
        <div class="row jus_f">
            @foreach ($rooms as $key => $room)
                <div id="bg_rm{{ $room->id }}" class="ct_center col-sm-6 col-md-4 col-xl-3 mb-6 mr-4 ml-4">
                    <input type="hidden" id="bg_in{{ $room->id }}">
                    <input type="hidden" id="bg_in_sts{{ $room->id }}" value="0">
                    <a href="#" class="s_bgx @if ($room->status == 2) comp_cartn @endif"
                        data-date="{{ $room->date }}" data-time="{{ $room->time }}"
                        data-precio="{{ $room->price }}" data-idb="{{ $room->id }}"
                        data-bot="{{ number_format($room->jackpot, 2) }}" data-automc="{{ $room->val_automc }}"
                        data-type='{{ $room->type_boat }}' data-status='{{ $room->status }}'
                        data-user_automc="{{ $room->auto_mc }}" data-cantpj='{{ $room->players }}'>
                        <div class="box_rbg">
                            <div class="cont_rbg">
                                <div id="bg_tx{{ $room->id }}" class="time_bg">
                                    @if ($room->status == 2)
                                        <i class="fa-regular fa-clock"></i>
                                    @else
                                        EN JUEGO
                                    @endif
                                </div>
                                <span class="i_crt_full">Modo: Cartón Lleno</span>
                                @if ($room->ctns > 0)
                                    <div class="crtn_buy" title="Mis Cartones">
                                        +<span class="num_ct">{{ $room->ctns }}</span>
                                    </div>
                                @endif
                                @if ($room->auto_mc == 1)
                                    <div class="ic_atm" title="Tienes el Automarcador">
                                        <i class="fa-solid fa-check-to-slot"></i>
                                    </div>
                                @endif
                                <img class="img_rbg" src="{{ asset('img/bg/estelar_fd.png') }}" alt="IMG">
                                <span class="b_te">
                                    @if ($room->type_boat == 1)
                                        jackpot:
                                    @elseif($room->type_boat == 2)
                                        Premio:
                                    @endif
                                </span>
                                <span class="bot_cant">
                                    @if ($room->jackpot > 0)
                                        <div class="cont_price_bg">
                                            <img style="width:24px; margin-bottom:1px;"
                                                src="{{ asset('imgSystem/tether.svg') }}">
                                            <span id='montRoom{{ $room->id }}'>
                                                {{ number_format($room->jackpot, 2) }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="cont_price_bg">
                                            <h3>ESPERANDO</h3>
                                        </div>
                                    @endif
                                </span>
                                <span class="i_crt_bs">Cartón: {{ $room->price }} USDT</span>
                                <span class="t_bg_hour" id="t_bg_hour{{ $room->id }}">
                                    @if ($room->status == 2)
                                        Comprar cartones
                                    @else
                                        No disponible
                                    @endif
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true,
        })
    </script>
    @if (Session::has('message'))
        <script>
            if (@json(Session::has('buy'))) {
                Toast.fire({
                    icon: 'success',
                    html: '<div style="padding-top:1.6% !important; font-size:1rem;">{{ Session::get('message') }}</div>'
                })
            }
        </script>
    @endif
@endsection
{{-- @section('scripts')
    <script>
        const coinRoom = '<?php echo json_encode($coinRoom); ?>';

        $("#prueba").click(function() {
            $.ajax({
                url: "{{ route('bingo.time_serv') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                method: 'get',
                dataType: 'json',
                success: function(data) {
                    now = new Date(data);
                }
            });
        });

        Echo.channel('rmBingo.' + coinRoom)
            .listen('GetRoomsBingo', (res) => {
                const data = res.data;
                for (let key in data) {
                    const room = data[key];
                    for (let prop in room) {
                        if (prop === 'jackpot') {
                            $('#montRoom' + room.id).text(parseFloat(room.jackpot).toFixed(2));
                        }
                    }
                }
            });

        $(document).ready(() => @json($rooms->count()) > 0 && rm_timer());

        function sald_insf() {
            if (!$(".swal2-toast").html()) {
                Toast.fire({
                    icon: 'error',
                    title: '¡Saldo Insuficiente!'
                });
            }
        }

        if ($("#idmolda").val()) {
            $("#exampleModal").modal('show');
        }

        $('.quantity').each(function() {
            const spinner = $(this),
                input = spinner.find('input[type="number"]'),
                btnUp = spinner.find('.quantity-up'),
                btnDown = spinner.find('.quantity-down'),
                min = parseFloat(input.attr('min')) || 0,
                max = parseFloat(input.attr('max')) || Infinity,
                step = parseFloat(input.attr('step')) || 1;

            const updateInput = (value) => input.val(value).trigger('change');

            btnUp.click(() => {
                const newValue = Math.min(+input.val() + step, max);
                updateInput(newValue);
                btnDown.toggle(newValue !== 1);
            });

            btnDown.click(() => {
                const newValue = Math.max(+input.val() - step, min);
                updateInput(newValue);
                btnDown.toggle(newValue !== 1);
            });

            btnDown.toggle(+input.val() !== 1);
        });

        var van_pjs_bing = 0;

        function view_players(arr_info) {
            ar_size = arr_info['name'].length;
            content = '';
            for (i = 0; i < ar_size; i++) {
                img = arr_info['foto'][i];
                if (img == null) {
                    img = 'rke.png';
                }
                // name = arr_info['name'][i];
                // if (name == $("#tx_username").html().trim()) {
                //     name = 'Tú';
                // }
                data = '<div class="cont_inf_player_bing">' +
                    '<div class="cont_img_pj_bing">' +
                    '<img class="img_us" style="height:31px " src="' +
                    '{{ asset('') }}imgSystem/imgUsers/' + img + '">' + '</div>' +
                    '<div class="name_player_bing_mdl">' +
                    arr_info['name'][i] + '</div>' + '</div>'
                content += data;
            }
            $("#list_players_bingo_body").html(content);
            $("#list_players_bingo").show();
            $("#cant_pjs_bing").html(ar_size);
            van_pjs_bing = 0;
        }

        $(".cont_pjs_bing").click(function() {
            if (van_pjs_bing == 0 && $("#val_pjs_bing").val() >= 1) {
                van_pjs_bing = 1;
                $.ajax({
                    url: "{{ route('bingo.consult_players') }}",
                    headers: {
                        'X-CSRF-TOKEN': $("#token").val()
                    },
                    method: 'post',
                    data: {
                        room: $("#id_bing").val()
                    },
                    dataType: 'json',
                    success: function(data) {
                        view_players(data['players']);
                    }
                });
            }
        });

        $("#cls_mdl_inf_gm").click(function() {
            $("#info_game").hide();
        });

        $("#cls_mdl_inf_players").click(function() {
            $("#list_players_bingo").hide();
        });

        $("#ck_inf_gm").click(function() {
            $.post("{{ asset('info/bingo.blade.php') }}", function(ext) {
                $("#mc_buy").hide();
                $("#info_game_body").html(ext).parent().show();
            });
        });

        var total = auto = 0;
        $(".comp_cartn").click(function() {
            if (Number(@json($wallet->usdt)) >= Number($(this).data("precio"))) {
                $("#info_game").hide();
                if ($(this).data("status") == 2 && $("#bg_in_sts" + $(this).data("idb")).val() == 0) {
                    $("#mc_buy").show();
                    $("#dat_f").html(new Date($(this).data("date")).toLocaleDateString('en-GB').replace('/20',
                        '/'));
                    $("#da_tm").html(new Date(Date.parse(
                            $(this).data("date") + ' ' + $(this).data("time") + " " + "GMT-0400"))
                        .toLocaleString("en-US", {
                            hour: "2-digit",
                            minute: "2-digit",
                            hour12: true,
                        }));
                    auto = $(this).data("automc");
                    total = $(this).data("precio");
                    $("#precio").val($(this).data("precio"));
                    $("#val_pjs_bing").val($(this).data("cantpj"));
                    if ($(this).data("type") == 2) {
                        $("#cont_pjs_bing").hide();
                    } else {
                        $("#cant_pjs_bing").html($(this).data("cantpj"));
                    }
                    $("#type_bt").val($(this).data("type"));
                    if ($(this).data("type") == 2) {
                        type_bot = 'Premio:';
                    } else {
                        type_bot = 'jackpot:';
                    }
                    $('.bot_tx').html(type_bot);
                    $("#v_bot").html($(this).data("bot"));
                    $(".total").val($(this).data("precio")).html($(this).data("precio").toLocaleString("en-US"));
                    $("#cantidad").val(1);
                    $("#id_bing").val($(this).data("idb"));
                    if ($(this).data("user_automc") > 0) {
                        $('#aum_buy').show();
                    } else {
                        $('#aum_in_use').show();
                    }
                }
            } else {
                sald_insf();
            }
        });

        $("#cantidad").on("keyup change", function() {
            $(".quantity-up").show();
            operacion();
        });

        $("#buy_autom").click(function() {
            if ((Number(total) + Number(auto)) > Number(@json($wallet->usdt))) {
                sald_insf();
                $('#buy_autom').prop('checked', false).val('0');
            } else {
                $('#buy_autom').val('1');
                operacion();
            }
        })

        function operacion() {
            const cantidad = $("#cantidad"),
                precio = $("#precio"),
                buyAutom = $('#buy_autom');

            cantidad.val(Math.max(1, cantidad.val()));

            const montCtn = precio.val() * cantidad.val(),
                total = buyAutom.prop('checked') ? montCtn + Number(auto) : montCtn,
                usdt = Number(@json($wallet->usdt));

            $(".quantity-up").toggle(usdt >= total + Number(precio.val()));

            if (total > usdt) {
                sald_insf();
            } else {
                $(".total").val(total).html(total.toLocaleString("en-US"));
            }
        }

        $('input[type=number]').keypress(function(key) {
            return key.charCode >= 48 && key.charCode <= 57;
        });


        $("#close_mdl").click(function() {
            $("#mc_buy").hide();
        });

        $("#buy_bg").click(function() {
            if (@json($wallet->usdt) >= total) {
                $('#info_buy').submit();
            } else {
                sald_insf();
            }
        });

        var v_rtn = 0;
        var id_elems = [];
        var _second = 1000,
            _minute = _second * 60,
            _hour = _minute * 60,
            _day = _hour * 24;
        var now = new Date(@json($date));
        var c_timer = 0;

        function rm_timer() {
            $.getJSON("{{ asset('') }}bingocash/timer/1", {
                    'X-CSRF-TOKEN': $("#token").val()
                })
                .done(res => res.forEach(value => {
                    id_elems.push(value.id);
                    $(`#bg_in${value.id}`).val(`${value.date} ${value.time}`);
                }));
        }

        function c_tmsvr() {
            $.ajax({
                url: "{{ route('bingo.time_serv') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                method: 'get',
                dataType: 'json',
                success: function(data) {
                    now = new Date(data);
                }
            });
            c_timer = 1;
        }
        window.onblur = function() {
            v_rtn = 1;
        }
        window.onfocus = function() {
            if (v_rtn == 1) {
                c_timer = 0;
                c_tmsvr();
            }
        }

        function showRemaining() {
            if (id_elems.length > 0) {
                id_elems.forEach(function(elem, ind, array) {
                    var end = new Date($("#bg_in" + elem).val());
                    var bing_end = new Date($("#bg_in" + elem).val());
                    new Date(bing_end + (bing_end.setSeconds(bing_end.getSeconds() + 620)));
                    if (c_timer == 0) {
                        c_tmsvr();
                    }
                    distance = end - now;
                    var days = Math.floor(distance / _day);
                    var hours = Math.floor((distance % _day) / _hour);
                    var minutes = Math.floor((distance % _hour) / _minute);
                    var seconds = Math.floor((distance % _minute) / _second);

                    if (c_timer == 1) {
                        if (distance <= 0) {
                            if ($("#bg_in_sts" + elem) != 1) {
                                if (bing_end - now > 0) {
                                    $("#bg_tx" + elem).html(
                                        '<span style="font-size:15px; letter-spacing: 0.25px;">EN JUEGO</span>'
                                    );
                                } else {
                                    $("#bg_tx" + elem).html(
                                        '<span style="font-size:14px; letter-spacing: 0.25px;">FINALIZADA</span>'
                                    );
                                }
                                $("#t_bg_hour" + elem).html('No disponible');
                                $("#bg_in_sts" + elem).val(1);
                                status(elem);
                            }
                        } else {
                            if (days == 0) {
                                if (hours == 0) {
                                    if (minutes == 0) {
                                        if (seconds != 0) {
                                            $("#bg_tx" + elem).html('<div class="time_dt">' + '<span>' + seconds +
                                                '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                        }
                                    } else {
                                        $("#bg_tx" + elem).html(
                                            '<div class="time_dt">' + '<span>' + minutes +
                                            '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                            '<div class="time_dt">' + '<span>' + seconds +
                                            '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                    }
                                } else {
                                    $("#bg_tx" + elem).html(
                                        '<div class="time_dt">' + '<span>' + hours + '</span>' +
                                        '<span class="tx_time">HRS</span>' + '</div>' +
                                        '<div class="time_dt tm_minutes">' + '<span>' + minutes +
                                        '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                        '<div class="time_dt">' + '<span>' + seconds +
                                        '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                    if (minutes == 0) {
                                        $('.tm_minutes').hide()
                                    }
                                }
                            } else {
                                $("#bg_tx" + elem).html(
                                    '<div class="time_dt">' + '<span>' + days +
                                    '</span>' + '<span class="tx_time">DIAS</span>' + '</div>' +
                                    '<div class="time_dt tm_hours">' + '<span>' + hours + '</span>' +
                                    '<span class="tx_time">HRS</span>' + '</div>' +
                                    '<div class="time_dt  tm_minutes">' + '<span>' + minutes +
                                    '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                    '<div class="time_dt">' + '<span>' + seconds +
                                    '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                if (hours == 0) {
                                    $('.tm_hours').hide();
                                }
                                if (minutes == 0) {
                                    $('.tm_minutes').hide()
                                }
                            }
                        }
                    }
                });
                now.setMilliseconds(1000);
            }
        }
        let timer = setInterval(showRemaining, 1000);

        function status(id_sala) {
            $.ajax({
                url: "{{ asset('') }}bingocash/status/" + id_sala,
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                method: 'get',
                dataType: 'json',
                success: function(data) {
                    if (data['van'] == 1) {
                        window.location.href = 'room/' + id_sala;
                    }
                    if (data['rfs'].length > 0) {
                        $.each(data['rfs'], function(key, value) {
                            $("#bg_rm" + value).hide();
                            if ($("#id_bing").val() == value) {
                                $("#mc_buy").hide();
                            }
                        });
                    }
                }
            });
        }
    </script>
@endsection --}}
