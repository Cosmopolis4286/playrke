<!-- resources/views/includes/card-header.blade.php -->
<div class="card-header">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            @if (isset($returnBtn) || !isset($returnBtn))
                <a href="{{ route('dashboard') }}" title="Regresar" class="mr-2 mb-n1">
                    <i class="fas fa-arrow-circle-left" style="font-size: 20px;"></i>
                </a>
            @endif
            @php $displayTitle = $cardTitle ?? $title; @endphp
            <div>
                <div class="d-flex @if (isset($subTitle)) mb-n3 @else mb-n2 @endif">
                    <h5>{!! $displayTitle !!}</h5>
                    @isset($btnCardHeader)
                        <a href="{{ $btnCardHeader['route'] }}" class="text-muted ml-2 mb-n1">
                            <i class="{{ $btnCardHeader['icon'] }}"></i>
                        </a>
                    @endisset
                </div>
                @isset($subTitle)
                    <small>{{ $subTitle }}</small>
                @endisset
            </div>
        </div>
        <div id="loader-sm" class="spinner-border spinner-border-sm text-primary" role="status" style="display: none;">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</div>
