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
                @if ($formData)
                    {!! $formData !!}
                @else
                    {{ $slot }}
                @endif
            </div>

            {{-- Footer --}}
            <div class="modal-footer">
                @isset($footer)
                    {{ $footer }}
                @else
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
                    @if ($formData)
                        <button type="submit" class="btn btn-sm btn-primary" id="btn-confirm">Guardar</button>
                    @endif
                @endisset
            </div>

        </div>
    </div>
</div>
