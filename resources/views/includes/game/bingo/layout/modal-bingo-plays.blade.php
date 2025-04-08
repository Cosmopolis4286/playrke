<div class="modal fade" id="modal-bingo-plays" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">PlayRKE © {{ date('Y') }}
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card container-view-carton">
                    <div class="section-letter">
                        <div class="number-box-letter"><b>B</b></div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip0" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip1" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip2" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip3" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip4" class="number-box-number"></span>
                        </div>
                    </div>
                    <div class="section-letter">
                        <div class="number-box-letter"><b>I</b></div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip5" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip6" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip7" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip8" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip9" class="number-box-number"></span>
                        </div>
                    </div>
                    <div class="section-letter">
                        <div class="number-box-letter"><b>N</b></div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip10" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays" class="number-box-number">
                            <span id="ctn-vchip11"></span>
                        </div>
                        <div class="number-box-view-ctn">
                            <img class="img-fluid img-logo-ctn"
                                src="{{ isset($custLogo) ? asset('storage/customers/' . $custLogo) : asset('img/rke.png') }}">
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip12" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip13" class="number-box-number"></span>
                        </div>
                    </div>
                    <div class="section-letter">
                        <div class="box number-box-letter"><b>G</b></div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip14" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip15" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip16" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip17" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip18" class="number-box-number"></span>
                        </div>
                    </div>
                    <div class="section-letter">
                        <div class="number-box-letter"><b>O</b></div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip19" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip20" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip21" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip22" class="number-box-number"></span>
                        </div>
                        <div class="number-box-view-ctn ctn-plays">
                            <span id="ctn-vchip23" class="number-box-number"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" id="btn-confirm" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>
