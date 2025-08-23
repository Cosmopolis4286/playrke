{{-- resources/views/components/shared/btn-group.blade.php --}}
@props(['buttons' => collect(), 'moduleId' => ''])

<div class="btn-group" role="group" aria-label="Acciones">
    @foreach ($buttons as $btn)
        <button type="button" class="btn btn-sm {{ $btn->color }} btn-{{ $btnGroupId }}" {{-- Estado inicial desde PHP --}}
            @if ($btn->disabled) disabled @endif {{-- Luego Alpine.js controla dinámicamente si aplica --}}
            x-bind:disabled="{{ $btn->disabled ? 'selected.length === 0' : 'false' }}"
            @click="$dispatch('btn-action', ['{{ $btn->action }}', selected])">
            <i class="{{ $btn->icon }}"></i> {{ $btn->titleIcon ?? '' }}
        </button>
    @endforeach
</div>
