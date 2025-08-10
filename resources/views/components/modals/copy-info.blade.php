@props([
    'name' => 'default',
    'title' => 'Título del modal',
    'value' => '',
])

<div class="modal fade" id="modal-{{ $name }}" tabindex="-1" role="dialog"
    aria-labelledby="{{ $name }}Label" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $name }}Label">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="input-group">
                    <input type="text" id="info-to-copy-{{ $name }}" class="form-control"
                        value="{{ $value }}" aria-label="Recipient's username"
                        aria-describedby="btn-copy-{{ $name }}" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" id="btn-copy-{{ $name }}">
                            Copiar
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>