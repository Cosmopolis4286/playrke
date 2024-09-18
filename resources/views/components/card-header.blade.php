<!-- resources/views/components/card-header.blade.php -->
<div class="card-header">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            @if (isset($returnBtn) || !isset($returnBtn))
                <a href="{{ route('dashboard') }}" title="Regresar" class="mr-1 mb-n1">
                    <i class="fas fa-arrow-circle-left" style="font-size: 20px;"></i>
                </a>
            @endif
            @php $displayTitle = $cardTitle ?? $title; @endphp
            <h5 class="m-0">{!! $displayTitle !!}</h5>
        </div>
        <div id="loader-sm" class="spinner-border spinner-border-sm text-primary" role="status" style="display: none;">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</div>
