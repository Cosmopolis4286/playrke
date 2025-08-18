<div x-data="{
    open: false,
    modalId: '{{ $id }}',
    init() {
        // Escuchar eventos globales para abrir/cerrar modal
        window.addEventListener('toggle-modal', (event) => {
            if (!event.detail.id || event.detail.id === this.modalId) {
                this.open = event.detail.state ?? !this.open;
            }
        });

        // Mantener Alpine sincronizado con Bootstrap
        $watch('open', (value) => {
            const modalEl = document.getElementById('modal-' + this.modalId);
            if (!modalEl) return;

            if (value) {
                $(modalEl).modal('show');
            } else {
                $(modalEl).modal('hide');
            }

            // Evento cuando Bootstrap cierra modal por backdrop o ESC
            $(modalEl).on('hidden.bs.modal', () => {
                this.open = false;
            });
        });
    }
}" x-init="init()">
    <template x-if="open">
        <div>
            <div class="modal fade" id="modal-{{ $id }}" tabindex="-1" role="dialog"
                aria-labelledby="modal-{{ $id }}-label" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-{{ $size }}" role="document" {!! $style ?? '' !!}>
                    <div class="modal-content">

                        {{-- Header --}}
                        <div class="modal-header">
                            <h5 class="modal-title" id="modal-{{ $id }}-label">{{ $title }}</h5>
                            <button type="button" class="close" @click="open = false" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
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
                                <button type="button" class="btn btn-secondary" @click="open = false">Cerrar</button>
                                @if ($dataForm)
                                    <button type="submit" class="btn btn-primary" id="btn-confirm">Guardar</button>
                                @endif
                            @endisset
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
