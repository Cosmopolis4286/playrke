<div class="modal fade" id="modal-{{ $id }}" tabindex="-1" role="dialog"
    aria-labelledby="modal-{{ $id }}-label" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-{{ $size }}" role="document" {!! $style ?? '' !!}>
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header">
                <h5 class="modal-title" id="modal-{{ $id }}-label">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
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
                        <button type="submit" class="btn btn-sm btn-primary" id="{{ $btnConfirm }}">Guardar</button>
                    @endif
                @endisset
            </div>

        </div>
    </div>
</div>

@once
    @push('stack-scripts')
        <script nonce="{{ csp_nonce() }}">
            // Fix para aria-hidden warning en modales de Bootstrap
            document.addEventListener('DOMContentLoaded', function() {
                // Escuchar todos los eventos hide.bs.modal
                document.querySelectorAll('.modal').forEach(function(modal) {
                    $(modal).on('hide.bs.modal', function() {
                        // Quitar el foco de cualquier elemento enfocado dentro del modal
                        const focusedElement = modal.querySelector(':focus');
                        if (focusedElement instanceof HTMLElement) {
                            focusedElement.blur();
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
