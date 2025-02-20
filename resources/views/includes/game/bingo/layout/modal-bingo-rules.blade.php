<div class="modal fade" id="modal-bingo-rules" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content bg-rke1">
            <div class="modal-header">PlayRKE © {{ date('Y') }}
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">{!! $bingoRules !!}</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close">Cerrar</button>
            </div>
        </div>
    </div>
</div>
