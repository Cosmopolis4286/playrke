<style>
    @page {
        margin: 0%;
    }

    hr {
        border: 0.5px rgba(65, 179, 255, 0.651)solid !important;
    }

    #container_img_header_ctlg {
        margin: 0 !important;
        position: relative !important;
    }

    #img_header_ctlg {
        width: 100%;
        height: 100%;
    }

    #date_ctlg_products_header {
        position: absolute;
        font-size: 32px;
        color: white;
        bottom: 5.25%;
        left: 17.75%;
        transform: translate(-50%, 50%);
    }

    .container_all_info_ctlg {
        position: relative;
        min-height: 100%;
        padding: 45px !important;
        font-family: Arial, Helvetica, sans-serif !important
    }

    .container_header_name_emp {
        width: 100%;
        height: 39px;
    }

    .items_header_name_emp {
        width: 50%;
    }

    .items_hdr_lft {
        color: rgb(10, 10, 87);
        font-weight: normal;
        font-size: 13.5px;
        text-align: left !important
    }

    .items_hdr_rgt {
        text-align: right !important
    }

    .container_header_client_vend {
        margin-top: 15px !important;
        width: 100%;
        height: 39px;
    }

    .header_client_vend {
        border: 1px solid rgba(0, 135, 224, 0.897);
        padding-top: 16px;
        padding-left: 20px;
        text-align: left;
        float: left;
        width: 47%;
        height: 38px;
        font-size: 18px;
        color: rgba(17, 160, 255, 0.877);
    }

    .name_client_ctlg {
        border-right: transparent;
    }

    .container_info_product {
        text-align: center;
        width: 100%;
    }

    .items_img_product {
        margin: 38px auto;
    }

    .colum_derh_info {
        width: 527px;
    }

    .resume_description_product {
        padding-top: 11px;
        height: 28px;
        font-size: 16.25px;
        text-transform: uppercase;
        color: rgb(255, 255, 255);
        background-color: rgb(60, 107, 238);
    }

    .title_description_product_ctlg {
        text-transform: uppercase;
        font-size: 15px;
        color: #008ac5;
        padding-top: 8px;
        height: 24px;
        background-color: #e7e7e7ea;
    }

    .description_product_ctlg {
        font-size: 14px;
        padding-top: 33px;
        padding-left: 14px;
        padding-right: 14px;
        height: 97px;
        border: 2px solid #e7e7e7ea;
    }

    .table_second_info_ctlg {
        border: 2px solid #e7e7e7ea;
        border-top: transparent !important;
    }

    .items_info_product {
        font-size: 15px;
        color: #028eca;
        padding: 10px auto;
        background-color: #e4e4e4ea;
    }

    .txt_items_info_product {
        font-size: 11.75px;
        height: 42px;
    }

    .footer_ctlg_products {
        position: fixed;
        width: 100%;
        height: 30px;
        padding: 0 45px;
        font-weight: normal;
        color: #0294d3;
        bottom: 35;
    }

    .date_ctlg_products_footer{
        color: rgb(22, 22, 68);
        font-size: 16px;
    }

    .num_questag_ctlg {
        color: rgb(22, 22, 68);
        float: right;
    }
</style>
<div id="container_img_header_ctlg">
    <img id="img_header_ctlg" src="{{ asset('/img/header_45.jpg') }}" alt="">
    <div id="date_ctlg_products_header">Octubro 2023</div>
</div>
<div class="container_all_info_ctlg">
    <table class='container_header_name_emp'>
        <thead>
            <tr>
                <th class='items_header_name_emp items_hdr_lft'>sonepar.cl</th>
                <th class='items_header_name_emp items_hdr_rgt'>
                    <img src="{{ asset('/img/sonepar3.png') }}" alt="">
                </th>
            </tr>
        </thead>
    </table><br>
    <hr><br>
    <div class='container_header_client_vend'>
        <div class='header_client_vend name_client_ctlg'>Nombre de cliente</div>
        <div class='header_client_vend'>Vendedor asignado</div>
    </div><br><br><br>
    <table class="container_info_product" cellspacing="2">
        <thead>
            <tr>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="content_items_img_product">
                    <div class="items_img_product">
                        <img src="{{ asset('/img/sonepar2.png') }}" alt="">
                    </div>
                    <div class="items_img_product" style="padding-top: 25px;">
                        <img src="{{ asset('img/sonepar1.png') }}" alt="">
                    </div>
                </td>
                <td class="colum_derh_info">
                    <div class="resume_description_product">ENCHUFE MACHO VOLANTE 2P+T 16A 220V AZUL IP44 555124</div>
                    <div class="title_description_product_ctlg">Descripción</div>
                    <div class="description_product_ctlg">Enchufe Industrial | Conexión: Macho | Tipo: Volante |
                        Material:
                        Poliamida
                        | Número Polos: 2 Polos + Tierra | Corriente Nominal: 16A | Tensión Nominal:
                        200/250V | Color: Azul | Norma: IEC 60309/IEC 60529 | Grado Protección: IP44
                        | Modelo: P17 Tempra Pro | Marca: Legrand | Ref: 555124</div>
                    <table class="container_info_product table_second_info_ctlg">
                        <thead style="">
                            <tr>
                                <th class="items_info_product">Código</th>
                                <th class="items_info_product">Datos Cliente</th>
                                <th class="items_info_product">Part.Number</th>
                                <th class="items_info_product">Cód. Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="txt_items_info_product">[IT_CODIGO]</td>
                                <td class="txt_items_info_product">[COD_CLIENTE]</td>
                                <td class="txt_items_info_product">[PARTNUMBER]</td>
                                <td class="txt_items_info_product">[REFERENCIA]</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
        </tbody>
    </table><br><br><br>
    <table class='footer_ctlg_products'>
        <hr style="width: 105%;">
        <thead>
            <tr>
                <th class='date_ctlg_products_footer items_hdr_lft'>
                    <b>Catalago de productos</b> | Octubre 2023
                </th>
                <th class='num_questag_ctlg items_hdr_rgt'>1</th>
            </tr>
        </thead>
    </table>
</div>