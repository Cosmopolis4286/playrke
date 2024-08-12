@extends('layouts.app_rke')
<title>Bingoa - PlayRKE</title>
<style>
    #content_trik {
        background-color: red;
        position: relative;
    }

    .content_squares {
        display: flex;
        justify-content: center;
    }

    .square_trik {
        height: 100px;
        width: 100px;
        display: flex;
        justify-content: center;
        align-items: center;
        border: 2px solid black;
        background-color: green;
    }

    .container_header_client_vend {
        display: flex;
        position: relative;
        margin-bottom: 30px;
    }

    .header_client_vend {
        display: flex;
        align-items: center;
        padding-left: 15px;
        width: 50%;
        height: 57px;
        font-size: 17px;
        font-weight: 600;
        color: rgb(54, 117, 253);
        border: rgb(54, 117, 253) 1px solid;
    }

    .header_client_vend:first-child {
        border-right: transparent !important;
    }

    .container_info_product {
        position: relative;
        height: 300px;
        display: flex;
    }

    .content_img_product {
        display: grid;
        position: relative;
        width: 25%;
    }

    .content_img_product img {
        width: 100%;
    }

    .imagen_product_ctlg {
        padding: 1%;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 220px;
    }

    .brand_product_ctlg {
        padding: 1%;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 80px;
    }

    .brand_product_ctlg img {
        height: 100%;
    }

    .imagen_product_ctlg img {
        max-width: 100%;
    }

    .content_description_product {
        position: relative;
        width: 100%;
    }

    .resumen_description_product_ctlg {
        display: flex;
        justify-content: center;
        align-items: center;
        text-transform: uppercase;
        font-size: 19px;
        color: white;
        width: 100%;
        height: 50px;
        background-color: rgb(32, 89, 211);
    }

    .header_description_product_ctlg {
        height: 45px;
        font-size: 18px;
        font-weight: 600;
        color: rgb(32, 89, 211);
        background-color: rgb(211, 211, 211);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .description_product_ctlg {
        padding: 0 80px;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 125px;
        border-left: rgb(196, 196, 196) 1.5px solid;
        border-right: rgb(196, 196, 196) 1.5px solid;
    }

    .table_products_ctlg {
        border: rgb(214, 214, 214) 1px solid;
        text-align: center;
        width: 100%;
    }

    .header_table_description {
        padding: 8px 15px;
        background-color: rgb(212, 212, 212);
        border: rgb(196, 196, 196) 1.5px solid;
    }
</style>
@section('content')
    <div style="display: flex; justify-content:space-between; align-items:center">
        <span>sonepar.cl</span>
        <div><img src="{{ asset('/img/sonepar3.png') }}" alt=""></div>
    </div>
    <hr>
    <div class="container_header_client_vend">
        <div class="header_client_vend">Nombre de cliente</div>
        <div class="header_client_vend">Vendedor asignado</div>
    </div>
    <div class="container_info_product">
        <div class="content_img_product">
            <div class="imagen_product_ctlg">
                <img src="{{ asset('/img/sonepar2.png') }}" alt="">
            </div>
            <div class="brand_product_ctlg">
                <img src="{{ asset('img/sonepar1.png') }}" alt="">
            </div>
        </div>
        <div class="content_description_product">
            <div class="resumen_description_product_ctlg">
                ENCHUFE MACHO VOLANTE 2P+T 16A 220V AZUL IP44 555124
            </div>
            <div class="header_description_product_ctlg">Descripcion</div>
            <div class="description_product_ctlg">Enchufe Industrial | Conexión: Macho | Tipo: Volante | Material: Poliamida
                |
                Número Polos: 2 Polos + Tierra | Corriente Nominal: 16A | Tensión Nominal:
                200/250V | Color: Azul | Norma: IEC 60309/IEC 60529 | Grado Protección: IP44
                | Modelo: P17 Tempra Pro | Marca: Legrand | Ref: 555124</div>
            <table class="table_products_ctlg">
                <thead>
                    <tr>
                        <th class="header_table_description">Codigo</th>
                        <th class="header_table_description">Datos Cliente</th>
                        <th class="header_table_description">Part. Number</th>
                        <th class="header_table_description">Cod. Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>LG555124</td>
                        <td>AM1918309</td>
                        <td>555124</td>
                        <td>555124</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    {{--   <div id="content_trik" class="col-12">
        <div class="content_squares">
            <div class="square_trik">1</div>
            <div class="square_trik">2</div>
            <div class="square_trik">3</div>
        </div>
        <div class="content_squares">
            <div class="square_trik">1</div>
            <div class="square_trik">2</div>
            <div class="square_trik">3</div>
        </div>
        <div class="content_squares">
            <div class="square_trik">1</div>
            <div class="square_trik">2</div>
            <div class="square_trik">3</div>
        </div>
    </div> --}}
@endsection
@section('scripts')
    {{-- <script src="{{ asset('js/phaser.min.js') }}"></script>

    <script>
        const Phaser = require('phaser');
        var config = {
            type: Phaser.AUTO,
            width: 800,
            height: 600,
            scene: {
                preload: preload,
                create: create,
                update: update
            }
        };

        var game = new Phaser.Game(config);

        function preload() {

        }

        function create() {

        }

        function update() {

        }
    </script> --}}
@endsection
<!--<div class="container_info_product">
        <div class="content_img_product">
            <div class="imagen_product_ctlg">
                <img src="{{ asset('/img/bg/estelar.png') }}" alt="">
            </div>
            <div class="brand_product_ctlg">
                <img src="{{ asset('img/cofre.png') }}" alt="">
            </div>
        </div>
        <div class="content_description_product">
            <div class="resumen_description_product_ctlg">ENCHUFE MACHO</div>
            <div class="header_description_product_ctlg">Descripcion</div>
            <div class="description_product_ctlg">111||!!!||!|!|!||!|!|!|!|!|!|!</div>
            <table class="table_products_ctlg">
                <thead>
                    <tr>
                        <th class="header_table_description">Codigo</th>
                        <th class="header_table_description">Datos Cliente</th>
                        <th class="header_table_description">Part. Number</th>
                        <th class="header_table_description">Cod. Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Prueba</td>
                        <td>Prueba</td>
                        <td>Prueba</td>
                        <td>Prueba</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>-->
