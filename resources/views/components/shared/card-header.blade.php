@props([
    'subtitle' => '',
    'btnCardHeader' => null,
])

<div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="card-subtitle">{{ $subtitle }}</h5>
    
    @if ($btnCardHeader && isset($btnCardHeader['route'], $btnCardHeader['icon']))
        <a href="{{ $btnCardHeader['route'] }}" class="btn btn-sm btn-light">
            <i class="{{ $btnCardHeader['icon'] }}"></i>
        </a>
    @endif
</div>