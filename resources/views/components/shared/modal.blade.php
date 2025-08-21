<div class="modal fade" id="modal-{{ $id }}" tabindex="-1" role="dialog"
    aria-labelledby="modal-{{ $id }}-label" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-{{ $size }}" role="document" {!! $style ?? '' !!}>
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header">
                <h5 class="modal-title" id="modal-{{ $id }}-label">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span>&times;</span>
                </button>
            </div>

            {{-- Body --}}
            <div class="modal-body">
                @if ($dataForm)
                    {!! $dataForm !!}
                @else
                    {{ $slot }}
                @endif
            </div>

            {{-- Footer --}}
            <div class="modal-footer">
                @isset($footer)
                    {{ $footer }}
                @else
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    @if ($dataForm)
                        <button type="submit" class="btn btn-primary" id="btn-confirm">Guardar</button>
                    @endif
                @endisset
            </div>

        </div>
    </div>
</div>
