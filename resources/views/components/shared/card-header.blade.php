@props([
    'title' => '',
    'cardTitle' => null,
    'subtitle' => null,
    'returnBtn' => true,
    'btnCardHeader' => null,
])

<div class="card-header">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            @if ($returnBtn)
                <a href="{{ route('dashboard') }}" title="Regresar" class="mr-2 mb-n1">
                    <i class="fas fa-arrow-circle-left" style="font-size: 20px;"></i>
                </a>
            @endif

            <div>
                <div class="d-flex {{ $subtitle ? 'mb-n3' : 'mb-n2' }}">
                    <h5>{!! $cardTitle ?? $title !!}</h5>

                    @if (!empty($btnCardHeader['route']) && !empty($btnCardHeader['icon']))
                        <a href="{{ $btnCardHeader['route'] }}" class="text-muted ml-2 mb-n1">
                            <i class="{{ $btnCardHeader['icon'] }}"></i>
                        </a>
                    @endif
                </div>

                @if ($subtitle)
                    <small>{{ $subtitle }}</small>
                @endif
            </div>
        </div>

        <div id="loader-sm" class="spinner-border spinner-border-sm text-primary" role="status" style="display: none;">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</div>
