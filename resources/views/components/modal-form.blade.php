 {{-- resources/views/components/modal-form.blade.php --}}
<div class="modal fade" id="modal-{{ $name }}" tabindex="-1" role="dialog"
    aria-labelledby="modal-{{ $name }}-label" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-{{ $size ?? 'sm' }}" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-{{ $name }}-label"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-{{ $name }}" method="POST">@csrf
                <div class="modal-body">{!! $dataForm !!}</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" id="btn-confirm" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
