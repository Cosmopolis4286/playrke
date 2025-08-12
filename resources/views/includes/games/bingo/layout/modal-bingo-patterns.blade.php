<div class="modal fade" id="modal-bingo-patterns" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">PlayRKE © {{ date('Y') }}
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-{{ $viewId }}" action="{{ route('bingo-patterns.handler') }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body d-flex justify-content-center">
                    <div id="container-carton">
                        <div class="mb-2 card container-view-carton">
                            <div class="section-letter">
                                <div class="number-box-letter"><b>B</b></div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-b1" class="number-box-number ctn-patterns">B1</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-b2" class="number-box-number ctn-patterns">B2</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-b3" class="number-box-number ctn-patterns">B3</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-b4" class="number-box-number ctn-patterns">B4</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-b5" class="number-box-number ctn-patterns">B5</span>
                                </div>
                            </div>
                            <div class="section-letter">
                                <div class="number-box-letter"><b>I</b></div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-i1" class="number-box-number ctn-patterns">I1</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-i2" class="number-box-number ctn-patterns">I2</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-i3" class="number-box-number ctn-patterns">I3</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-i4" class="number-box-number ctn-patterns">I4</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-i5" class="number-box-number ctn-patterns">I5</span>
                                </div>
                            </div>
                            <div class="section-letter">
                                <div class="number-box-letter"><b>N</b></div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-n1" class="number-box-number ctn-patterns">N1</span>
                                </div>
                                <div class="number-box-view-ctn" class="number-box-number">
                                    <span id="ctn-n2" class="number-box-number ctn-patterns">N2</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <img class="img-fluid img-logo-ctn"
                                        src="{{ isset($customerLogo) ? asset('storage/customers/' . $customerLogo) : asset('img/rke.png') }}">
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-n4" class="number-box-number ctn-patterns">N4</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-n5" class="number-box-number ctn-patterns">N5</span>
                                </div>
                            </div>
                            <div class="section-letter">
                                <div class="box number-box-letter"><b>G</b></div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-g1" class="number-box-number ctn-patterns">G1</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-g2" class="number-box-number ctn-patterns">G2</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-g3" class="number-box-number ctn-patterns">G3</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-g4" class="number-box-number ctn-patterns">G4</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-g5" class="number-box-number ctn-patterns">G5</span>
                                </div>
                            </div>
                            <div class="section-letter">
                                <div class="number-box-letter"><b>O</b></div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-o1" class="number-box-number ctn-patterns">O1</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-o2" class="number-box-number ctn-patterns">O2</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-o3" class="number-box-number ctn-patterns">O3</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-o4" class="number-box-number ctn-patterns">O4</span>
                                </div>
                                <div class="number-box-view-ctn">
                                    <span id="ctn-o5" class="number-box-number ctn-patterns">O5</span>
                                </div>
                            </div>
                        </div>
                        <div class="input-group">
                            <input type="text" id="name-pattern" name="name_play" class="form-control"
                                placeholder="Nombre de la Jugada" required>
                            <input type="hidden" id="bingo-boxes" name="bingo_boxes">
                            <div class="input-group-append">
                                <button id="btn-clean" class="btn btn-outline-secondary" type="button"
                                    title="Borrar Todo">
                                    <i class="fa-solid fa-eraser"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" id="btn-confirm" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>
