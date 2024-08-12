<div class="mdl_triv" id="mdl_triv_multi">
    <div class="modal-header">
        <div id="tdx"></div>
        <button type="button" class="close" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <div id="dbr"></div>
    </div>
</div>

<div class="modal fade mdl_triv" id="mdl_triv_multi" tabindex="-1" role="dialog" aria-labelledby="mdl_triv_multiLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div id="tdx"></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="dbr"></div>
            </div>
        </div>
    </div>
</div>

<div class="mdl_triv" id="m_trinv">
    <form action="{{ route('Wallet.debit_triv_indv') }}" method="POST">@csrf
        <div class="modal-content bg-rke1">
            <div class="modal-header">
                <i class="mt-1 fa-solid fa-puzzle-piece" style="margin-right:2.5px;"></i>
                <b>EL PODER DEL SABER</b>
                <a class="btn_close_bg_md close_rm">X</a>
            </div>
            <div class="modal-body" style="text-align:center;">
                <h5 class="mb-1">¿Cuánto quieres multiplicar?</h5>
                <input type="hidden" name="coin" value="2">
                <div style="display: flex; justify-content:center;">
                    <div class="sym_coin form-control_d" style="max-width: 62px;"></div>
                    <input class="form-control col-6" type="number" name="aps_triv" placeholder="Monto" step="5"
                        value="5" min="5" max="130" data-val="true"
                        data-val-required="Monto requerido">
                </div>
                <button type="submit" class="bg-navy mt-2 form-control col-12" style="margin:0 auto;">
                    <b>JUGAR AHORA</b></button>
                <div data-valmsg-for="aps_triv" data-valmsg-replace="true"
                    style="color: red; margin-top:1.5% !important;"></div>
            </div>
        </div>
    </form>
</div>

<div class="mdl_triv" id="m_tripvp">
    <div class="modal-header">
        <div>
            <i class="mt-1 fa-solid fa-puzzle-piece" style="margin-right:2.5px;"></i>
            <b>EL PODER DEL SABER</b>
        </div> <a class="btn_close_bg_md close_rm">X</a>
    </div>
    <div class="modal-body" style="text-align:center;">
        <form action="#" method="POST">@csrf
            <h5 class="mb-1">¿Cuánto quieres apostar?</h5>
            <input type="hidden" name="coin" value="2">
            <div style="display: flex; justify-content:center;">
                <div class="sym_coin form-control_d" style="max-width: 62px;"></div>
                <input class="form-control col-6" type="number" name="aps_triv" placeholder="Monto" step="10"
                    value="10" min="10" max="1000" data-val="true" data-val-required="Monto requerido">
            </div>
            <button type="submit" class="bg-navy mb-n3 mt-2 form-control col-12" style="margin:0 auto;">
                <b>BUSCAR RIVAL</b></button>
            <div data-valmsg-for="aps_triv" data-valmsg-replace="true" style="color: red; margin-top:1.5% !important;">
            </div>
        </form>
    </div>
</div>
