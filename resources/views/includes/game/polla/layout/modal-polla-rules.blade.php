<div class="modal fade" id="modal-polla-rules" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">PlayRKE © {{ date('Y') }}
                <button type="button" class="close" data-dismiss="modal" aria-label="close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if ($imagePollaRules)
                    <div class="d-flex justify-content-center">
                        <img style="max-height: 85px" src="{{ asset("storage/game/polla/$imagePollaRules") }}"
                            alt="Game Image">
                    </div>
                    <hr>
                @endif
                <div>{!! $pollaRules !!}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close">Cerrar</button>
            </div>
        </div>
    </div>
</div>
